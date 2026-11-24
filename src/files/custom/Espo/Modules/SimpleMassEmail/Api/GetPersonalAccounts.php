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

namespace Espo\Modules\SimpleMassEmail\Api;

use Espo\Core\Acl;
use Espo\Core\Acl\Table;
use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\Forbidden;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\MassEmail;
use Espo\Modules\SimpleMassEmail\Tools\PersonalSending\Accounts;

/**
 * The personal email accounts of the current user only.
 */
class GetPersonalAccounts implements Action
{
    public function __construct(
        private Acl $acl,
        private User $user,
        private Accounts $accounts,
    ) {}

    public function process(Request $request): Response
    {
        if (
            !$this->acl->checkScope(MassEmail::ENTITY_TYPE, Table::ACTION_CREATE) &&
            !$this->acl->checkScope(MassEmail::ENTITY_TYPE, Table::ACTION_EDIT)
        ) {
            throw new Forbidden();
        }

        $list = [];

        foreach ($this->accounts->getListForUser($this->user) as $account) {
            $list[] = (object) [
                'id' => $account->getId(),
                'emailAddress' => $account->getEmailAddress(),
                'fromName' => $this->user->getName(),
            ];
        }

        return ResponseComposer::json($list);
    }
}
