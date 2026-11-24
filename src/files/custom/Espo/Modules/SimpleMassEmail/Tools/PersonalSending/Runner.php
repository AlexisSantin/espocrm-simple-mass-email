<?php
/************************************************************************
 * This file is part of Simple Mass Email for EspoCRM.
 *
 * Copyright (C) 2026 Alexis SANTIN
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 ************************************************************************/

namespace Espo\Modules\SimpleMassEmail\Tools\PersonalSending;

use Espo\Core\Field\DateTime;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Metadata;
use Espo\Entities\EmailAccount;
use Espo\Entities\Notification;
use Espo\Modules\Crm\Entities\Campaign;
use Espo\Modules\Crm\Entities\EmailQueueItem;
use Espo\Modules\Crm\Entities\MassEmail;
use Espo\Modules\Crm\Tools\MassEmail\QueueCreator;
use Espo\Modules\Crm\Tools\MassEmail\SendingProcessor;
use Espo\ORM\EntityManager;
use Throwable;

/**
 * Sends the mass emails handed to the extension, once their date has come:
 * - Sending: through the system or a group account, like the native job;
 * - Throttled: through a personal account, within the limit of the account.
 *
 * Their status keeps them out of the native job, so that both never send the same mass email.
 */
class Runner
{
    public const STATUS_THROTTLED = 'Throttled';
    public const STATUS_SENDING = 'Sending';

    private const DEFAULT_MAX_ATTEMPT_COUNT = 3;

    public function __construct(
        private EntityManager $entityManager,
        private Accounts $accounts,
        private SendLog $sendLog,
        private ProcessorFactory $processorFactory,
        private QueueCreator $queueCreator,
        private SendingProcessor $sendingProcessor,
        private Metadata $metadata,
        private Config $config,
        private Language $defaultLanguage,
        private Log $log,
    ) {}

    public function run(): void
    {
        $this->sendLog->prune();

        $massEmails = $this->entityManager
            ->getRDBRepositoryByClass(MassEmail::class)
            ->where([
                'status' => [self::STATUS_SENDING, self::STATUS_THROTTLED],
                'startAt<=' => DateTime::createNow()->toString(),
            ])
            ->order('startAt')
            ->find();

        foreach ($massEmails as $massEmail) {
            try {
                $this->process($massEmail);
            } catch (Throwable $e) {
                $this->log->error("Simple Mass Email: sending error. {id}.", [
                    'id' => $massEmail->getId(),
                    'exception' => $e,
                ]);
            }
        }
    }

    private function process(MassEmail $massEmail): void
    {
        if ($massEmail->getStatus() === self::STATUS_THROTTLED) {
            $this->processPersonal($massEmail);

            return;
        }

        if (!$this->hasQueue($massEmail) && !$this->createQueue($massEmail)) {
            return;
        }

        $this->failOptedOutItems($massEmail);

        // The native processor: system or group SMTP, hourly limit, completion.
        $this->sendingProcessor->process($massEmail);
    }

    private function processPersonal(MassEmail $massEmail): void
    {
        $account = $this->accounts->findUsable((string) $massEmail->get('emailAccountId'));
        $smtpParams = $account ? $this->accounts->getSmtpParams($account) : null;

        if (!$account || !$smtpParams) {
            $this->suspend($massEmail, $account, 'sendingSuspendedAccount');

            return;
        }

        // Such a campaign has no unsubscribe link and also reaches opted-out addresses.
        if ($massEmail->getCampaign()?->getType() === Campaign::TYPE_INFORMATIONAL_EMAIL) {
            $this->suspend($massEmail, $account, 'sendingSuspendedInformational');

            return;
        }

        if (!$this->hasQueue($massEmail) && !$this->createQueue($massEmail)) {
            return;
        }

        $this->failOptedOutItems($massEmail);

        $limit = max(1, (int) $massEmail->get('dailyLimit'));
        // One email at a time: the quota is checked before each one, and a failure stops the run.
        $processor = $this->processorFactory->create($smtpParams, 1);

        while ($massEmail->getStatus() === self::STATUS_THROTTLED) {
            $pendingCount = $this->countItems($massEmail, EmailQueueItem::STATUS_PENDING);

            if ($pendingCount === 0) {
                $massEmail->setStatus(MassEmail::STATUS_COMPLETE);
                $this->entityManager->saveEntity($massEmail);

                return;
            }

            $sentCount = $this->sendLog->countInWindow($account->getId());

            if ($sentCount >= $limit) {
                return;
            }

            if (!$this->sendNext($processor, $massEmail, $account, $sentCount, $pendingCount)) {
                return;
            }
        }
    }

    /**
     * @return bool Whether to go on with the next email.
     */
    private function sendNext(
        SendingProcessor $processor,
        MassEmail $massEmail,
        EmailAccount $account,
        int $sentCount,
        int $pendingCount,
    ): bool {

        $exhaustedCount = $this->countExhaustedItems($massEmail);

        $processor->process($massEmail);

        if ($this->sendLog->countInWindow($account->getId()) > $sentCount) {
            return true;
        }

        if ($this->countExhaustedItems($massEmail) > $exhaustedCount) {
            // The server kept refusing this email: the next ones would fail the same way.
            $this->suspend($massEmail, $account, 'sendingSuspendedSmtp');

            return false;
        }

        // Fewer pending items without a send: an invalid or opted-out address was skipped.
        // Otherwise the attempt failed, and it is retried at the next run.
        return $this->countItems($massEmail, EmailQueueItem::STATUS_PENDING) < $pendingCount;
    }

    private function hasQueue(MassEmail $massEmail): bool
    {
        return $this->entityManager
            ->getRDBRepositoryByClass(EmailQueueItem::class)
            ->where([
                'massEmailId' => $massEmail->getId(),
                'isTest' => false,
            ])
            ->count() > 0;
    }

    /**
     * @return bool Whether the sending can go on.
     */
    private function createQueue(MassEmail $massEmail): bool
    {
        // The native queue creator only accepts this status. It is not stored: the save at the
        // end of the creation goes through the hook, which restores the extension's status.
        $massEmail->setStatus(MassEmail::STATUS_PENDING);

        $this->queueCreator->create($massEmail);

        if ($massEmail->getStatus() === MassEmail::STATUS_PENDING) {
            // Skipped by the native code: the campaign is inactive.
            $massEmail->setStatus($massEmail->get('emailAccountId') ? self::STATUS_THROTTLED : self::STATUS_SENDING);

            return false;
        }

        return in_array($massEmail->getStatus(), [self::STATUS_SENDING, self::STATUS_THROTTLED], true);
    }

    /**
     * The queue is built once and lasts several days: whoever has since unsubscribed from one of
     * the target lists must not receive the email.
     */
    private function failOptedOutItems(MassEmail $massEmail): void
    {
        /** @var string[] $linkList */
        $linkList = $this->metadata->get(['scopes', 'TargetList', 'targetLinkList']) ?? [];

        foreach ($massEmail->getTargetLists() as $targetList) {
            foreach ($linkList as $link) {
                $idsByType = [];

                $records = $this->entityManager
                    ->getRelation($targetList, $link)
                    ->select(['id'])
                    ->sth()
                    ->where(['@relation.optedOut' => true])
                    ->find();

                foreach ($records as $record) {
                    $idsByType[$record->getEntityType()][] = $record->getId();
                }

                foreach ($idsByType as $entityType => $ids) {
                    $this->failPendingItems($massEmail, $entityType, $ids);
                }
            }
        }
    }

    /**
     * @param string[] $targetIds
     */
    private function failPendingItems(MassEmail $massEmail, string $targetType, array $targetIds): void
    {
        $query = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in(EmailQueueItem::ENTITY_TYPE)
            ->set(['status' => EmailQueueItem::STATUS_FAILED])
            ->where([
                'massEmailId' => $massEmail->getId(),
                'status' => EmailQueueItem::STATUS_PENDING,
                'isTest' => false,
                'targetType' => $targetType,
                'targetId' => $targetIds,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query);
    }

    private function countItems(MassEmail $massEmail, string $status): int
    {
        return $this->entityManager
            ->getRDBRepositoryByClass(EmailQueueItem::class)
            ->where([
                'massEmailId' => $massEmail->getId(),
                'status' => $status,
                'isTest' => false,
            ])
            ->count();
    }

    /**
     * Failed after all their attempts, as opposed to the items skipped without any attempt.
     */
    private function countExhaustedItems(MassEmail $massEmail): int
    {
        return $this->entityManager
            ->getRDBRepositoryByClass(EmailQueueItem::class)
            ->where([
                'massEmailId' => $massEmail->getId(),
                'status' => EmailQueueItem::STATUS_FAILED,
                'isTest' => false,
                'attemptCount>=' => $this->config->get('massEmailMaxAttemptCount', self::DEFAULT_MAX_ATTEMPT_COUNT),
            ])
            ->count();
    }

    /**
     * Stops the sending and warns the owner. The queue is kept: sending the mass email again
     * resumes with the remaining recipients.
     */
    private function suspend(MassEmail $massEmail, ?EmailAccount $account, string $messageKey): void
    {
        $massEmail->setStatus(MassEmail::STATUS_FAILED);
        $this->entityManager->saveEntity($massEmail);

        $userId = $account?->getAssignedUser()?->getId() ?? $massEmail->get('createdById');

        if (!$userId) {
            return;
        }

        $link = "[{$massEmail->get('name')}](#MassEmail/view/{$massEmail->getId()})";
        $message = $this->defaultLanguage->translateLabel($messageKey, 'messages', 'SimpleMassEmail');

        $notification = $this->entityManager->getRDBRepositoryByClass(Notification::class)->getNew();

        $notification
            ->setType(Notification::TYPE_MESSAGE)
            ->setUserId($userId)
            ->setMessage(str_replace('{link}', $link, $message));

        $this->entityManager->saveEntity($notification);
    }
}
