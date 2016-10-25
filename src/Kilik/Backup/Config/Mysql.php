<?php

namespace Kilik\Backup\Config;

use Kilik\Backup\Config\Traits\BackupTrait;
use Kilik\Backup\Config\Traits\HostnameTrait;
use Kilik\Backup\Config\Traits\LoginTrait;
use Kilik\Backup\Config\Traits\NameTrait;
use Kilik\Backup\Config\Traits\PasswordTrait;
use Kilik\Backup\Config\Traits\PathTrait;
use Kilik\Backup\Config\Traits\RsyncTrait;
use Kilik\Backup\Config\Traits\ServerTrait;
use Kilik\Backup\Config\Traits\UserTrait;

/**
 * Mysql configuration
 */
class Mysql
{
    use HostnameTrait;
    use UserTrait;
    use PasswordTrait;
    use BackupTrait;

    /**
     * Port.
     *
     * @var int
     */
    private $port=3306;

    /**
     * Socket.
     *
     * @var string
     */
    private $socket;

    /**
     * Set Port
     *
     * @param int $port
     *
     * @return static
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * Get Port
     *
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Set Socket
     *
     * @param string $socket
     *
     * @return static
     */
    public function setSocket($socket)
    {
        $this->socket = $socket;

        return $this;
    }

    /**
     * Get Socket
     *
     * @return string
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * @param array $array
     *
     * @return static
     */
    public function setFromArray($array)
    {
        if (isset($array['hostname'])) {
            $this->hostname = $array['hostname'];
        }
        if (isset($array['port'])) {
            $this->port = $array['port'];
        }
        if (isset($array['socket'])) {
            $this->socket = $array['socket'];
        }
        if (isset($array['user'])) {
            $this->user = $array['user'];
        }
        if (isset($array['password'])) {
            $this->password = $array['password'];
        }

        return $this;
    }

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
     * String name
     *
     * @return string
     */
    public function __toString()
    {
        if ($this->socket) {
            return $this->user.'@'.$this->socket;
        }

        return $this->user.'@'.$this->hostname.':'.$this->port;
    }
}
