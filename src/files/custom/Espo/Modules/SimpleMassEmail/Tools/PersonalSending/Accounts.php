<?php

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
