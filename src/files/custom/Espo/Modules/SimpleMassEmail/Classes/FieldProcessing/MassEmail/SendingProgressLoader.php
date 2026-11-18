<?php

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
        $accountId = $entity->get('emailAccountId');

        if (!$accountId || ($params->hasSelect() && !$params->hasInSelect('sendingProgress'))) {
            return;
        }

        $limit = max(1, (int) $entity->get('dailyLimit'));
        $pending = $this->countItems($entity, EmailQueueItem::STATUS_PENDING);

        $entity->set('sendingProgress', (object) [
            'sent' => $this->countItems($entity, EmailQueueItem::STATUS_SENT),
            'failed' => $this->countItems($entity, EmailQueueItem::STATUS_FAILED),
            'pending' => $pending,
            'limit' => $limit,
            'usedInWindow' => $this->sendLog->countInWindow($accountId),
            'nextSlotAt' => $this->sendLog->getNextSlotAt($accountId, $limit)?->toString(),
            'daysLeft' => (int) ceil($pending / $limit),
        ]);
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
