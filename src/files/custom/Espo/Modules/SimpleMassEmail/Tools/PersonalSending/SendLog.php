<?php

namespace Espo\Modules\SimpleMassEmail\Tools\PersonalSending;

use Espo\Core\Field\DateTime;
use Espo\ORM\EntityManager;

/**
 * What each personal account has sent. It is kept apart from the email queue, which is deleted
 * with its mass email: deleting a mass email must not give the quota back.
 */
class SendLog
{
    public const ENTITY_TYPE = 'SimpleMassEmailSend';

    /** The quota applies to any period of this length, whatever way the provider counts a day. */
    public const WINDOW_HOURS = 24;

    private const KEPT_HOURS = 48;

    public function __construct(private EntityManager $entityManager) {}

    public function add(string $accountId, string $massEmailId): void
    {
        $this->entityManager->createEntity(self::ENTITY_TYPE, [
            'emailAccountId' => $accountId,
            'massEmailId' => $massEmailId,
            'sentAt' => DateTime::createNow()->toString(),
        ]);
    }

    public function countInWindow(string $accountId): int
    {
        return $this->entityManager
            ->getRDBRepository(self::ENTITY_TYPE)
            ->where([
                'emailAccountId' => $accountId,
                'sentAt>' => $this->getWindowStart()->toString(),
            ])
            ->count();
    }

    /**
     * When the account can send again, once its limit is reached.
     */
    public function getNextSlotAt(string $accountId, int $limit): ?DateTime
    {
        $count = $this->countInWindow($accountId);

        if ($count < $limit) {
            return null;
        }

        $entry = $this->entityManager
            ->getRDBRepository(self::ENTITY_TYPE)
            ->where([
                'emailAccountId' => $accountId,
                'sentAt>' => $this->getWindowStart()->toString(),
            ])
            ->order('sentAt')
            ->limit($count - $limit, 1)
            ->findOne();

        $sentAt = $entry?->get('sentAt');

        return $sentAt ? DateTime::fromString($sentAt)->addHours(self::WINDOW_HOURS) : null;
    }

    public function prune(): void
    {
        $query = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from(self::ENTITY_TYPE)
            ->where(['sentAt<' => DateTime::createNow()->addHours(-self::KEPT_HOURS)->toString()])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query);
    }

    private function getWindowStart(): DateTime
    {
        return DateTime::createNow()->addHours(-self::WINDOW_HOURS);
    }
}
