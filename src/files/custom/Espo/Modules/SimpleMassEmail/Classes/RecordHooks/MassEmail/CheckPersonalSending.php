<?php

namespace Espo\Modules\SimpleMassEmail\Classes\RecordHooks\MassEmail;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\Hook\SaveHook;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Campaign;
use Espo\Modules\Crm\Entities\MassEmail;
use Espo\Modules\SimpleMassEmail\Tools\PersonalSending\Accounts;
use Espo\ORM\Entity;

/**
 * A personal account sends in the name of its owner: only the owner can use it. Anyone else who
 * can edit the mass email can only stop it, by setting it back to Draft.
 *
 * @implements SaveHook<MassEmail>
 */
class CheckPersonalSending implements SaveHook
{
    private const IGNORED_ATTRIBUTES = ['status', 'modifiedAt', 'modifiedById', 'modifiedByName'];
    private const LIST_ATTRIBUTES = ['targetListsIds', 'excludingTargetListsIds'];

    public function __construct(
        private User $user,
        private Accounts $accounts,
    ) {}

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    public function process(Entity $entity): void
    {
        $previousAccountId = $entity->isNew() ? null : $entity->getFetched('emailAccountId');

        if ($previousAccountId && !$this->accounts->isOwnedBy($previousAccountId, $this->user)) {
            if (!$this->isOnlyStopped($entity)) {
                throw new Forbidden("Only the owner of the email account can change this mass email.");
            }

            return;
        }

        $accountId = $entity->get('emailAccountId');

        if (!$accountId) {
            return;
        }

        if (!$this->accounts->isOwnedBy($accountId, $this->user) || !$this->accounts->findUsable($accountId)) {
            throw new Forbidden("The email account cannot be used.");
        }

        if ((int) $entity->get('dailyLimit') < 1) {
            throw new BadRequest("A daily limit is required with a personal email account.");
        }

        // An informational campaign has no unsubscribe link and also reaches opted-out addresses.
        if ($entity->getCampaign()?->getType() === Campaign::TYPE_INFORMATIONAL_EMAIL) {
            throw new Forbidden("A personal email account cannot send an informational campaign.");
        }
    }

    private function isOnlyStopped(MassEmail $entity): bool
    {
        if ($entity->getStatus() !== MassEmail::STATUS_DRAFT) {
            return false;
        }

        foreach (self::LIST_ATTRIBUTES as $attribute) {
            if ($entity->has($attribute) && $entity->isAttributeChanged($attribute)) {
                return false;
            }
        }

        foreach ($entity->getAttributeList() as $attribute) {
            if (
                !in_array($attribute, self::IGNORED_ATTRIBUTES, true) &&
                !$entity->getAttributeParam($attribute, 'notStorable') &&
                $entity->isAttributeChanged($attribute)
            ) {
                return false;
            }
        }

        return true;
    }
}
