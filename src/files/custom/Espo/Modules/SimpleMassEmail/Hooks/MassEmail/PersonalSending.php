<?php

namespace Espo\Modules\SimpleMassEmail\Hooks\MassEmail;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Hook\Hook\BeforeSave;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Entities\EmailAccount;
use Espo\Modules\Crm\Entities\MassEmail;
use Espo\Modules\SimpleMassEmail\Tools\PersonalSending\Runner;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * Keeps the mass emails of personal accounts out of the native sending, on every save. If the
 * extension is removed, their status leaves them untouched instead of sending them all at once.
 *
 * @implements BeforeSave<MassEmail>
 */
class PersonalSending implements BeforeSave
{
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

        if (!$accountId) {
            $entity->set('dailyLimit', null);

            if ($entity->getStatus() === Runner::STATUS_THROTTLED) {
                $entity->setStatus(MassEmail::STATUS_DRAFT);
            }

            return;
        }

        if (in_array($entity->getStatus(), [MassEmail::STATUS_PENDING, MassEmail::STATUS_IN_PROCESS], true)) {
            $entity->setStatus(Runner::STATUS_THROTTLED);
        }

        $entity->set('inboundEmailId', null);

        $account = $this->entityManager->getRDBRepositoryByClass(EmailAccount::class)->getById($accountId);

        // SMTP servers refuse another sender than the account.
        if ($account?->getEmailAddress()) {
            $entity->set('fromAddress', $account->getEmailAddress());
        }
    }
}
