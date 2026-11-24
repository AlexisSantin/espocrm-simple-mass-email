<?php
/************************************************************************
 * This file is part of Simple Mass Email for EspoCRM.
 *
 * Copyright (C) 2026 Alexis SANTIN
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 ************************************************************************/

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
