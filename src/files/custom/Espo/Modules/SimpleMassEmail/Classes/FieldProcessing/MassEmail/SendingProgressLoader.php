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

namespace Espo\Modules\SimpleMassEmail\Classes\FieldProcessing\MassEmail;

use Espo\Core\FieldProcessing\Loader;
use Espo\Core\FieldProcessing\Loader\Params;
use Espo\Modules\Crm\Entities\EmailQueueItem;
use Espo\Modules\Crm\Entities\MassEmail;
use Espo\Modules\SimpleMassEmail\Tools\PersonalSending\SendLog;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

/**
 * @implements Loader<MassEmail>
 */
class SendingProgressLoader implements Loader
{
    public function __construct(
        private EntityManager $entityManager,
        private SendLog $sendLog,
    ) {}

    public function process(Entity $entity, Params $params): void
    {
        if ($params->hasSelect() && !$params->hasInSelect('sendingProgress')) {
            return;
        }

        $pending = $this->countItems($entity, EmailQueueItem::STATUS_PENDING);
        $sent = $this->countItems($entity, EmailQueueItem::STATUS_SENT);
        $failed = $this->countItems($entity, EmailQueueItem::STATUS_FAILED);
        $accountId = $entity->get('emailAccountId');

        if (!$accountId && $pending + $sent + $failed === 0) {
            return;
        }

        $progress = (object) [
            'sent' => $sent,
            'failed' => $failed,
            'pending' => $pending,
        ];

        if ($accountId) {
            $limit = max(1, (int) $entity->get('dailyLimit'));

            $progress->limit = $limit;
            $progress->usedInWindow = $this->sendLog->countInWindow($accountId);
            $progress->nextSlotAt = $this->sendLog->getNextSlotAt($accountId, $limit)?->toString();
            $progress->daysLeft = (int) ceil($pending / $limit);
        }

        $entity->set('sendingProgress', $progress);
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
}
