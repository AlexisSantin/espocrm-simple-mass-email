<?php

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
