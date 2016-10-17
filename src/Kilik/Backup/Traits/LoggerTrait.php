<?php

namespace Kilik\Backup\Traits;

use Kilik\Backup\Logger;

trait LoggerTrait
{
    /**
     * @var Logger
     */
    private $logger;

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