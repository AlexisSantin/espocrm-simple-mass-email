<?php

use Espo\Core\Container;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Config\ConfigWriter;

/**
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

    public function run(Container $container): void
    {
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
