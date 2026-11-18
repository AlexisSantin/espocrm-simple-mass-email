<?php

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
