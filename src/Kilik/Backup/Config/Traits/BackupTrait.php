<?php

namespace Kilik\Backup\Config\Traits;

use Kilik\Backup\Config\Backup;
use Kilik\Backup\Config\Server;

/**
 * Backup
 */
trait BackupTrait
{
    /**
     * Backup.
     *
     * @var Backup
     */
    private $backup;

    /**
     * Set Backup
     *
     * @param Backup $backup
     *
     * @return static
     */
    public function setBackup(Backup $backup)
    {
        $this->backup = $backup;

        return $this;
    }

    /**
     * Get Backup
     *
     * @return Backup
     */
    public function getBackup()
    {
        return $this->backup;
    }
}
