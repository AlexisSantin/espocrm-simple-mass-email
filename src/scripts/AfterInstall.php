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
 * Gives the content templates created without an assigned user (before 1.2.1) to their author:
 * roles that read only their own templates could not open nor link them.
 *
 * Schedules the job that sends the mass emails, every minute (once, so that the schedule chosen
 * by the administrator is kept).
 *
 * Adds a "New Mass Email" link to the navigation (system tab list), right after Emails.
 *
 * - The link is shown only to users who have access to mass emails (`aclScope`, which follows
 *   the Campaign permissions).
 * - It is added once: a flag remembers it, so an upgrade does not bring the link back after the
 *   administrator moved or removed it in Administration → User Interface.
 * - Users with their own tab list (Preferences) do not get it: they can add it themselves.
 */
class AfterInstall
{
    private const FLAG = 'simpleMassEmailTabAdded';
    private const URL = '#MassEmail/create';
    private const DELIMITERS = ['_delimiter_', '_delimiter-ext_'];
    private const JOB = 'SimpleMassEmailSendPersonal';
    private const SCHEDULING = '* * * * *';
    private const OLD_SCHEDULING = '*/10 * * * *';

    public function run(Container $container): void
    {
        $entityManager = $container->getByClass(EntityManager::class);

        $this->assignContentTemplates($entityManager);
        $this->scheduleJob($entityManager);

        $config = $container->getByClass(Config::class);

        if ($config->get(self::FLAG)) {
            return;
        }

        $configWriter = $container->getByClass(InjectableFactory::class)->create(ConfigWriter::class);

        $tabList = $config->get('tabList') ?? [];

        if (is_array($tabList) && !$this->hasLink($tabList)) {
            $configWriter->set('tabList', $this->insertLink($tabList, $this->createLink()));
        }

        $configWriter->set(self::FLAG, true);
        $configWriter->save();
    }

    private function assignContentTemplates(EntityManager $entityManager): void
    {
        $templates = $entityManager
            ->getRDBRepository('EmailTemplate')
            ->where([
                'oneOff' => true,
                'assignedUserId' => null,
                'createdById!=' => null,
            ])
            ->find();

        foreach ($templates as $template) {
            $template->set('assignedUserId', $template->get('createdById'));
            $entityManager->saveEntity($template, ['skipHooks' => true, 'silent' => true]);
        }
    }

    private function scheduleJob(EntityManager $entityManager): void
    {
        $job = $entityManager
            ->getRDBRepository('ScheduledJob')
            ->where(['job' => self::JOB])
            ->findOne();

        if ($job) {
            // Every 10 minutes up to 1.2: "Send now" now relies on a run every minute. A schedule
            // the administrator changed is kept.
            if ($job->get('scheduling') === self::OLD_SCHEDULING) {
                $job->set('scheduling', self::SCHEDULING);
                $entityManager->saveEntity($job);
            }

            return;
        }

        $entityManager->createEntity('ScheduledJob', [
            'name' => 'Simple Mass Email: sending',
            'job' => self::JOB,
            'status' => 'Active',
            'scheduling' => self::SCHEDULING,
        ]);
    }

    private function createLink(): object
    {
        return (object) [
            'type' => 'url',
            'id' => (string) random_int(1, 1000000),
            'text' => '$NewMassEmail',
            'url' => self::URL,
            'iconClass' => 'fas fa-envelopes-bulk',
            'color' => null,
            'aclScope' => 'MassEmail',
            'onlyAdmin' => false,
            'openInNewTab' => false,
        ];
    }

    /**
     * @param array<int, mixed> $list
     */
    private function hasLink(array $list): bool
    {
        foreach ($list as $item) {
            if (!is_object($item)) {
                continue;
            }

            if (($item->type ?? null) === 'url' && ($item->url ?? null) === self::URL) {
                return true;
            }

            if (is_array($item->itemList ?? null) && $this->hasLink($item->itemList)) {
                return true;
            }
        }

        return false;
    }

    /**
     * After Emails (also inside a group), else before the "more" delimiter, else at the end.
     *
     * @param array<int, mixed> $tabList
     * @return array<int, mixed>
     */
    private function insertLink(array $tabList, object $link): array
    {
        $tabList = array_values($tabList);

        $index = array_search('Email', $tabList, true);

        if ($index !== false) {
            array_splice($tabList, $index + 1, 0, [$link]);

            return $tabList;
        }

        foreach ($tabList as $item) {
            if (!is_object($item) || !is_array($item->itemList ?? null)) {
                continue;
            }

            $itemList = array_values($item->itemList);
            $index = array_search('Email', $itemList, true);

            if ($index !== false) {
                array_splice($itemList, $index + 1, 0, [$link]);
                $item->itemList = $itemList;

                return $tabList;
            }
        }

        foreach ($tabList as $i => $item) {
            if (in_array($item, self::DELIMITERS, true)) {
                array_splice($tabList, $i, 0, [$link]);

                return $tabList;
            }
        }

        $tabList[] = $link;

        return $tabList;
    }
}
