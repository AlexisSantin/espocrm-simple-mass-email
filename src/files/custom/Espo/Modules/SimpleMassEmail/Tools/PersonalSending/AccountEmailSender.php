<?php

namespace Espo\Modules\SimpleMassEmail\Tools\PersonalSending;

use Espo\Core\Mail\EmailSender;
use Espo\Core\Mail\Sender;
use Espo\Core\Mail\SmtpParams;
use LogicException;

/**
 * An email sender bound to the SMTP server of one account.
 */
class AccountEmailSender extends EmailSender
{
    private ?SmtpParams $accountSmtpParams = null;

    public function setAccountSmtpParams(SmtpParams $smtpParams): void
    {
        $this->accountSmtpParams = $smtpParams;
    }

    public function create(): Sender
    {
        if (!$this->accountSmtpParams) {
            throw new LogicException("No account SMTP parameters.");
        }

        return parent::create()->withSmtpParams($this->accountSmtpParams);
    }
}
