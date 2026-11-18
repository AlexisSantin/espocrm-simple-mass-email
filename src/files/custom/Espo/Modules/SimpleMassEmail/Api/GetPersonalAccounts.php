<?php

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
