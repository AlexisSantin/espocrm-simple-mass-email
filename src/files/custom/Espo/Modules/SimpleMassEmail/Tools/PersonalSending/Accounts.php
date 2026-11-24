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

use Espo\Core\Mail\Account\PersonalAccount\AccountFactory;
use Espo\Core\Mail\SmtpParams;
use Espo\Entities\EmailAccount;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Throwable;

/**
 * The personal email accounts that can send a mass email.
 */
class Accounts
{
    public function __construct(
        private EntityManager $entityManager,
        private AccountFactory $accountFactory,
    ) {}

    /**
     * @return EmailAccount[]
     */
    public function getListForUser(User $user): array
    {
        $collection = $this->entityManager
            ->getRDBRepositoryByClass(EmailAccount::class)
            ->where([
                'assignedUserId' => $user->getId(),
                'status' => EmailAccount::STATUS_ACTIVE,
                'useSmtp' => true,
                'smtpHost!=' => null,
                'emailAddress!=' => null,
            ])
            ->order('emailAddress')
            ->find();

        return iterator_to_array($collection);
    }

    public function isOwnedBy(string $accountId, User $user): bool
    {
        $account = $this->entityManager->getRDBRepositoryByClass(EmailAccount::class)->getById($accountId);

        return $account && $account->getAssignedUser()?->getId() === $user->getId();
    }

    /**
     * The account, when it can send now: active, with SMTP, and its owner is still active.
     */
    public function findUsable(string $accountId): ?EmailAccount
    {
        $account = $this->entityManager->getRDBRepositoryByClass(EmailAccount::class)->getById($accountId);

        if (
            !$account ||
            !$account->isAvailableForSending() ||
            !$account->get('smtpHost') ||
            !$account->getEmailAddress()
        ) {
            return null;
        }

        $ownerId = $account->getAssignedUser()?->getId();
        $owner = $ownerId ? $this->entityManager->getRDBRepositoryByClass(User::class)->getById($ownerId) : null;

        return $owner && $owner->isActive() ? $account : null;
    }

    public function getSmtpParams(EmailAccount $account): ?SmtpParams
    {
        try {
            return $this->accountFactory->create($account->getId())->getSmtpParams();
        } catch (Throwable) {
            return null;
        }
    }
}
