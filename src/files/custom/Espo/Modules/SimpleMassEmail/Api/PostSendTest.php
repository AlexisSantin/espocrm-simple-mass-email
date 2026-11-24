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
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error\Body;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\MassEmail;
use Espo\Modules\Crm\Tools\MassEmail\Service;
use Espo\Modules\SimpleMassEmail\Tools\PersonalSending\Accounts;
use Espo\Modules\SimpleMassEmail\Tools\PersonalSending\TestSender;
use Espo\ORM\EntityManager;

/**
 * Replaces the native test action: the native one would use the system SMTP. Mass emails without
 * a personal account are handed over to it unchanged.
 */
class PostSendTest implements Action
{
    public function __construct(
        private Acl $acl,
        private User $user,
        private EntityManager $entityManager,
        private Accounts $accounts,
        private Service $service,
        private TestSender $testSender,
    ) {}

    public function process(Request $request): Response
    {
        $id = $request->getParsedBody()->id ?? null;
        $targetDataList = $request->getParsedBody()->targetList ?? null;

        if (!is_string($id) || !is_array($targetDataList) || $targetDataList === []) {
            throw new BadRequest();
        }

        $massEmail = $this->entityManager->getRDBRepositoryByClass(MassEmail::class)->getById($id);
        $accountId = $massEmail?->get('emailAccountId');

        if (!$accountId) {
            $this->service->processTest($id, $targetDataList);

            return ResponseComposer::json(true);
        }

        if (!$this->acl->check($massEmail, Table::ACTION_READ)) {
            throw new Forbidden();
        }

        if (!$this->accounts->isOwnedBy($accountId, $this->user)) {
            throw Forbidden::createWithBody(
                'Only the owner of the email account can send from it.',
                Body::create()->withMessageTranslation('notAccountOwner', 'SimpleMassEmail')
            );
        }

        $this->testSender->send($massEmail, $this->getTargets($targetDataList));

        return ResponseComposer::json(true);
    }

    /**
     * @param mixed[] $targetDataList
     * @return \Espo\ORM\Entity[]
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     */
    private function getTargets(array $targetDataList): array
    {
        $list = [];

        foreach ($targetDataList as $item) {
            if (empty($item->id) || empty($item->type) || !is_string($item->type)) {
                throw new BadRequest();
            }

            $target = $this->entityManager->getEntityById($item->type, $item->id);

            if (!$target) {
                throw new NotFound();
            }

            if (!$this->acl->check($target, Table::ACTION_READ)) {
                throw new Forbidden();
            }

            $list[] = $target;
        }

        return $list;
    }
}
