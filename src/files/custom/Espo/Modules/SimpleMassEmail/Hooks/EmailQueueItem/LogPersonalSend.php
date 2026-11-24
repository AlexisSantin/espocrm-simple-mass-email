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

namespace Espo\Modules\SimpleMassEmail\Hooks\EmailQueueItem;

use Espo\Core\Hook\Hook\AfterSave;
use Espo\Modules\Crm\Entities\EmailQueueItem;
use Espo\Modules\Crm\Entities\MassEmail;
use Espo\Modules\SimpleMassEmail\Tools\PersonalSending\SendLog;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * Counts each email sent through a personal account.
 *
 * @implements AfterSave<EmailQueueItem>
 */
class LogPersonalSend implements AfterSave
{
    public function __construct(
        private EntityManager $entityManager,
        private SendLog $sendLog,
    ) {}

    public function afterSave(Entity $entity, SaveOptions $options): void
    {
        if ($entity->getStatus() !== EmailQueueItem::STATUS_SENT || !$entity->isAttributeChanged('status')) {
            return;
        }

        $massEmailId = $entity->get('massEmailId');

        $massEmail = $massEmailId ?
            $this->entityManager->getRDBRepositoryByClass(MassEmail::class)->getById($massEmailId) : null;

        $accountId = $massEmail?->get('emailAccountId');

        if ($accountId) {
            $this->sendLog->add($accountId, $massEmailId);
        }
    }
}
