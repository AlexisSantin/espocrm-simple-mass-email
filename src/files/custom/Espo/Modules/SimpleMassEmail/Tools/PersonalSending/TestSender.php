<?php

namespace Espo\Modules\SimpleMassEmail\Tools\PersonalSending;

use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Error\Body;
use Espo\Modules\Crm\Entities\EmailQueueItem;
use Espo\Modules\Crm\Entities\MassEmail;
use Espo\Modules\Crm\Tools\MassEmail\QueueCreator;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

/**
 * Sends a test through the personal account. Tests count towards its limit: for the email
 * provider, they are emails like the others.
 */
class TestSender
{
    public function __construct(
        private EntityManager $entityManager,
        private Accounts $accounts,
        private SendLog $sendLog,
        private ProcessorFactory $processorFactory,
        private QueueCreator $queueCreator,
    ) {}

    /**
     * @param Entity[] $targetList
     * @throws Conflict
     */
    public function send(MassEmail $massEmail, array $targetList): void
    {
        $account = $this->accounts->findUsable((string) $massEmail->get('emailAccountId'));
        $smtpParams = $account ? $this->accounts->getSmtpParams($account) : null;

        if (!$account || !$smtpParams) {
            throw Conflict::createWithBody('accountNotUsable', $this->createBody('accountNotUsable'));
        }

        $sentCount = $this->sendLog->countInWindow($account->getId());
        $available = max(1, (int) $massEmail->get('dailyLimit')) - $sentCount;

        if (count($targetList) > $available) {
            throw Conflict::createWithBody('dailyLimitReached', $this->createBody('dailyLimitReached'));
        }

        $this->queueCreator->create($massEmail, true, $targetList);

        $this->processorFactory
            ->create($smtpParams, $available)
            ->process($massEmail, true);

        // A test that failed is not retried by the job.
        $isComplete = $this->failPendingTestItems($massEmail) === 0;

        if (!$isComplete || $this->sendLog->countInWindow($account->getId()) === $sentCount) {
            throw Conflict::createWithBody('testNotSent', $this->createBody('testEmailError'));
        }
    }

    private function failPendingTestItems(MassEmail $massEmail): int
    {
        $items = $this->entityManager
            ->getRDBRepositoryByClass(EmailQueueItem::class)
            ->where([
                'massEmailId' => $massEmail->getId(),
                'status' => EmailQueueItem::STATUS_PENDING,
                'isTest' => true,
            ])
            ->find();

        $count = 0;

        foreach ($items as $item) {
            $item->setStatus(EmailQueueItem::STATUS_FAILED);
            $this->entityManager->saveEntity($item);
            $count++;
        }

        return $count;
    }

    private function createBody(string $label): Body
    {
        return Body::create()->withMessageTranslation($label, 'SimpleMassEmail');
    }
}
