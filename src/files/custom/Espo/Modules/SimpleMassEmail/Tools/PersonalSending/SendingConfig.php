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

namespace Espo\Modules\SimpleMassEmail\Tools\PersonalSending;

use Espo\Core\Utils\Config;

/**
 * The configuration as seen by the native mass email code during a personal sending.
 */
class SendingConfig extends Config
{
    /** @var array<string, mixed> */
    private array $overrides = [];

    /**
     * @param array<string, mixed> $overrides
     */
    public function setOverrides(array $overrides): void
    {
        $this->overrides = $overrides;
    }

    public function get(string $name, $default = null)
    {
        if (array_key_exists($name, $this->overrides)) {
            return $this->overrides[$name];
        }

        return parent::get($name, $default);
    }
}
