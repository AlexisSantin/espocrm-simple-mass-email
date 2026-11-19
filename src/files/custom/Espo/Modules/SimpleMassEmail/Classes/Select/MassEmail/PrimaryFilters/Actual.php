<?php

namespace Espo\Modules\SimpleMassEmail\Classes\Select\MassEmail\PrimaryFilters;

use Espo\Core\Select\Primary\Filter;
use Espo\Modules\Crm\Entities\MassEmail;
use Espo\Modules\SimpleMassEmail\Tools\PersonalSending\Runner;
use Espo\ORM\Query\SelectBuilder;

/**
 * The mass emails not finished yet, including those sent by the extension.
 */
class Actual implements Filter
{
    public function apply(SelectBuilder $queryBuilder): void
    {
        $queryBuilder->where([
            'status' => [
                MassEmail::STATUS_DRAFT,
                MassEmail::STATUS_PENDING,
                MassEmail::STATUS_IN_PROCESS,
                Runner::STATUS_SENDING,
                Runner::STATUS_THROTTLED,
            ],
        ]);
    }
}
