<?php

namespace Espo\Modules\SimpleMassEmail\Tools\PersonalSending;

use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\InjectableFactory;
use Espo\Core\Mail\EmailSender;
use Espo\Core\Mail\SmtpParams;
use Espo\Core\Utils\Config;
use Espo\Modules\Crm\Tools\MassEmail\MessageHeadersPreparator;
use Espo\Modules\Crm\Tools\MassEmail\SendingProcessor;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

/**
 * Creates the native sending processor, so that the message (unsubscribe link and headers,
 * tracking, campaign log) stays EspoCRM's, but bound to one SMTP account and batch size.
 */
class ProcessorFactory
{
    public function __construct(private InjectableFactory $injectableFactory) {}

    public function create(SmtpParams $smtpParams, int $batchSize): SendingProcessor
    {
        // Without these two dependencies the binding would be silently ignored, and the emails
        // would leave through the system SMTP without any limit.
        $this->assertDependencies();

        $config = $this->injectableFactory->create(SendingConfig::class);

        $config->setOverrides([
            'massEmailMaxPerBatchCount' => $batchSize,
            'massEmailDisableMandatoryOptOutLink' => false,
        ]);

        $emailSender = $this->injectableFactory->create(AccountEmailSender::class);
        $emailSender->setAccountSmtpParams($smtpParams);

        $headersPreparator = $this->injectableFactory->createResolved(
            MessageHeadersPreparator::class,
            BindingContainerBuilder::create()
                ->bindInstance(Config::class, $config)
                ->build()
        );

        return $this->injectableFactory->createWithBinding(
            SendingProcessor::class,
            BindingContainerBuilder::create()
                ->bindInstance(Config::class, $config)
                ->bindInstance(EmailSender::class, $emailSender)
                ->bindInstance(MessageHeadersPreparator::class, $headersPreparator)
                ->build()
        );
    }

    private function assertDependencies(): void
    {
        $typeList = [];

        foreach ((new ReflectionClass(SendingProcessor::class))->getConstructor()?->getParameters() ?? [] as $param) {
            $type = $param->getType();

            if ($type instanceof ReflectionNamedType) {
                $typeList[] = $type->getName();
            }
        }

        foreach ([Config::class, EmailSender::class] as $required) {
            if (!in_array($required, $typeList, true)) {
                throw new RuntimeException("SendingProcessor does not depend on $required anymore.");
            }
        }
    }
}
