<?php

namespace App\Backup\Config;

use App\Backup\Config\Traits\PathTrait;

/**
 * Repository configuration
 */
class Repository
{
    use PathTrait;

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
