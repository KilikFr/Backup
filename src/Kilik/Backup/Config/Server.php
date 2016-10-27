<?php

namespace Kilik\Backup\Config;

use Kilik\Backup\Config\Traits\HostnameTrait;
use Kilik\Backup\Config\Traits\NameTrait;
use Kilik\Backup\Config\Traits\RsyncTrait;
use Kilik\Backup\Traits\ConfigTrait;

/**
 * Server configuration
 */
class Server
{
    use NameTrait;
    use RsyncTrait;
    use ConfigTrait;
    use HostnameTrait;

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
     * Constructor
     */
    public function __construct()
    {
        $this->snapshots = [];
        $this->backups = [];
        $this->rsync = new Rsync();
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

}
