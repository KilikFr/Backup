<?php

namespace App\Backup\Config\Traits;

use App\Backup\Config\Server;

/**
 * Server
 */
trait ServerTrait
{
    /**
     * Server.
     *
     * @var string
     */
    private $server;

    /**
     * Set server
     *
     * @param string $server
     *
     * @return static
     */
    public function setServer($server)
    {
        $this->server = $server;

        return $this;
    }

    /**
     * Get server
     *
     * @return Server
     */
    public function getServer()
    {
        return $this->server;
    }
}
