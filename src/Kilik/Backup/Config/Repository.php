<?php

namespace Kilik\Backup\Config;

use Kilik\Backup\Config\Traits\PathTrait;
use Kilik\Backup\Traits\ConfigTrait;

/**
 * Repository configuration
 */
class Repository
{
    use PathTrait;
    use ConfigTrait;

    /**
     * Check config
     *
     * @throws \Exception
     */
    public function checkConfig()
    {
        // @todo
    }

    /**
     * Get current base path
     *
     * @return string
     */
    public function getCurrentPath()
    {
        return $this->getPath().'/current';
    }

    /**
     * get history base path
     *
     * @return string
     */
    public function getHistoryPath()
    {
        return $this->getPath().'/history';
    }
}
