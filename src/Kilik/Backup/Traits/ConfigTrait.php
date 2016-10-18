<?php

namespace Kilik\Backup\Traits;

use Kilik\Backup\Config;

trait ConfigTrait
{
    /**
     * @var Config
     */
    private $config;

    /**
     * Set config
     *
     * @param Config $config
     *
     * @return static
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Get config
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

}