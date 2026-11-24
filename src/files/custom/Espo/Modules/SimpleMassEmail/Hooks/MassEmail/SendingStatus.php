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

namespace Espo\Modules\SimpleMassEmail\Hooks\MassEmail;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Field\DateTime;
use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Entities\EmailAccount;
use Espo\Modules\Crm\Entities\MassEmail;
use Espo\Modules\SimpleMassEmail\Tools\PersonalSending\Runner;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * Hands every mass email that is sent, now or at a planned date, to the extension's job, which runs
 * every minute: Sending through the system or a group account, Throttled through a personal account.
 * The native job only knows Pending and In Process, so it never sends them at the same time, and
 * if the extension is removed they stay untouched instead of leaving all at once.
 *
 * @implements BeforeSave<MassEmail>
 */
class SendingStatus implements BeforeSave
{
    private const SENT_STATUSES = [
        MassEmail::STATUS_PENDING,
        MassEmail::STATUS_IN_PROCESS,
        Runner::STATUS_SENDING,
        Runner::STATUS_THROTTLED,
    ];

    public function __construct(private EntityManager $entityManager) {}

    /**
     * @throws Forbidden
     */
    public function beforeSave(Entity $entity, SaveOptions $options): void
    {
        $accountId = $entity->get('emailAccountId');

        if (
            $options->get(SaveOption::MASS_UPDATE) &&
            ($accountId || $entity->getFetched('emailAccountId'))
        ) {
            // A mass update skips the record checks on who may use the account.
            throw new Forbidden("No mass update for a mass email of a personal account.");
        }

        if (in_array($entity->getStatus(), self::SENT_STATUSES, true)) {
            // "Send now" leaves the date empty.
            if (!$entity->get('startAt')) {
                $entity->set('startAt', DateTime::createNow()->toString());
            }

            $entity->setStatus($accountId ? Runner::STATUS_THROTTLED : Runner::STATUS_SENDING);
        }

        if (!$accountId) {
            $entity->set('dailyLimit', null);

            return;
        }

        $entity->set('inboundEmailId', null);

        $account = $this->entityManager->getRDBRepositoryByClass(EmailAccount::class)->getById($accountId);

        // SMTP servers refuse another sender than the account.
        if ($account?->getEmailAddress()) {
            $entity->set('fromAddress', $account->getEmailAddress());
        }
    }
}
