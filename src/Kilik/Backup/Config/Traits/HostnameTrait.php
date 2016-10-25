<?php

namespace Kilik\Backup\Config\Traits;

/**
 * Hostname
 */
trait HostnameTrait
{
    /**
     * Hostname.
     *
     * @var string
     */
    private $hostname;

    /**
     * Set hostname
     *
     * @param string $hostname
     *
     * @return static
     */
    public function setHostname($hostname)
    {
        $this->hostname = $hostname;
    }

    /**
     * Get hostname
     *
     * @return string
     */
    public function getHostname()
    {
        return $this->hostname;
    }

}
