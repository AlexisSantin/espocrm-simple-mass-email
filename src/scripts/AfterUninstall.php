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

use Espo\Core\Container;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\ORM\EntityManager;

/**
 * Removes the scheduled job. The mass emails being sent through a personal account stay as they
 * are: their status keeps them out of the native sending.
 *
 * Removes the "New Mass Email" link added at installation: its label is translated by the
 * extension, which is no longer there. A link the administrator created with another label is kept.
 *
 * Not called on upgrades (EspoCRM skips the uninstall "after" script of the replaced version).
 */
class AfterUninstall
{
    private const FLAG = 'simpleMassEmailTabAdded';
    private const URL = '#MassEmail/create';
    private const TEXT = '$NewMassEmail';
    private const JOB = 'SimpleMassEmailSendPersonal';

    public function run(Container $container): void
    {
        $entityManager = $container->getByClass(EntityManager::class);

        $jobs = $entityManager
            ->getRDBRepository('ScheduledJob')
            ->where(['job' => self::JOB])
            ->find();

        foreach ($jobs as $job) {
            $entityManager->removeEntity($job);
        }

        $config = $container->getByClass(Config::class);
        $configWriter = $container->getByClass(InjectableFactory::class)->create(ConfigWriter::class);

        $tabList = $config->get('tabList');

        if (is_array($tabList)) {
            $configWriter->set('tabList', $this->removeLink($tabList));
        }

        if ($config->has(self::FLAG)) {
            $configWriter->remove(self::FLAG);
        }

        $configWriter->save();
    }

    /**
     * @param array<int, mixed> $list
     * @return array<int, mixed>
     */
    private function removeLink(array $list): array
    {
        $output = [];

        foreach ($list as $item) {
            if (is_object($item) && $this->isLink($item)) {
                continue;
            }

            if (is_object($item) && is_array($item->itemList ?? null)) {
                $item->itemList = $this->removeLink($item->itemList);
            }

            $output[] = $item;
        }

        return $output;
    }

    private function isLink(object $item): bool
    {
        return ($item->type ?? null) === 'url' &&
            ($item->url ?? null) === self::URL &&
            ($item->text ?? null) === self::TEXT;
    }
}
