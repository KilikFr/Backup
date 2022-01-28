<?php

namespace App\Backup\Config;

use App\Backup\Config;
use App\Backup\Config\Traits\HostnameTrait;
use App\Backup\Config\Traits\NameTrait;
use App\Backup\Config\Traits\RsyncTrait;

/**
 * Server configuration
 */
class Server
{
    use NameTrait;
    use RsyncTrait;
    private string $hostname;
    private Config $config;

    /**
     * Snapshots
     *
     * @var Snapshot[]
     */
    private $snapshots;

    /**
     * Backup
     *
     * @var Backup[]
     */
    private $backups;

    /**
     * @var string
     */
    private $user = "root";

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->snapshots = [];
        $this->backups = [];
        $this->rsync = new Rsync();
    }

    public function setConfig(Config $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function setHostname(string $hostname): self
    {
        $this->hostname = $hostname;

        return $this;
    }

    public function getHostname(): string
    {
        return $this->hostname;
    }
    /**
     * Get backups
     *
     * @return Backup[]
     */
    public function getBackups()
    {
        return $this->backups;
    }

    /**
     * @param array $array
     *
     * @return static
     * @throws \Exception
     */
    public function setFromArray($array)
    {
        if (isset($array['hostname'])) {
            $this->hostname = $array['hostname'];
        }

        // load rsync config
        if (isset($array['rsync'])) {
            $this->rsync->setFromArray($array['rsync']);
        }

        if (isset($array['user'])) {
            $this->user = $array['user'];
        }

        if (isset($array['snapshots']) && is_array($array['snapshots'])) {
            foreach ($array['snapshots'] as $snapshotName => $snapshotConfig) {
                $this->snapshots[] = (new Snapshot())->setName($snapshotName)->setFromArray($snapshotConfig)->setServer(
                    $this
                );
            }
        }

        if (isset($array['backups']) && is_array($array['backups'])) {
            foreach ($array['backups'] as $backupName => $backupConfig) {
                $backup = (new Backup())->setName($backupName)->setFromArray($backupConfig)->setServer($this);
                if (isset($backupConfig['snapshot'])) {
                    $snapshotName = $backupConfig['snapshot'];
                    $found = false;
                    foreach ($this->snapshots as $snapshot) {
                        if ($snapshot->getName() == $snapshotName) {
                            $found = true;
                            $backup->setSnapshot($snapshot);
                            break;
                        }
                    }
                    if (!$found) {
                        throw new \Exception(
                            'snapshot '.$snapshot.' not found for backup '.$backupName.' in server '.$this->getName()
                        );
                    }
                }
                $this->backups[] = $backup;
            }
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
        foreach ($this->snapshots as $snapshot) {
            $snapshot->checkConfig();
        }

        foreach ($this->backups as $backup) {
            $backup->checkConfig();
        }
    }

    /**
     * Get as text
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * Get a remote command line
     *
     * @param string $cmd
     * @return string remote command
     */
    public function getRemoteCmd($cmd)
    {
        $cmd = 'ssh root@'.$this->getHostname().' '.$cmd;

        return $cmd;
    }


    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param string $user
     *
     * @return static
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

}
