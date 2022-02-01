<?php

namespace App\Backup\Traits;

use App\Backup\Logger;

trait LoggerTrait
{
    private Logger $logger;

    /**
     * Set logger
     *
     * @param Logger $logger
     *
     * @return static
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get logger
     *
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

}