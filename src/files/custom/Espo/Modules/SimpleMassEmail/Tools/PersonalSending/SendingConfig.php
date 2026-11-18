<?php

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
