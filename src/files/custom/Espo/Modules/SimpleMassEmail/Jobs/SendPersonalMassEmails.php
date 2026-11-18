<?php

namespace Espo\Modules\SimpleMassEmail\Jobs;

use Espo\Core\Job\JobDataLess;
use Espo\Modules\SimpleMassEmail\Tools\PersonalSending\Runner;

class SendPersonalMassEmails implements JobDataLess
{
    public function __construct(private Runner $runner) {}

    public function run(): void
    {
        $this->runner->run();
    }
}
