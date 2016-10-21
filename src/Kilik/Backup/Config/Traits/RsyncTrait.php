<?php

namespace Kilik\Backup\Config\Traits;

use Kilik\Backup\Config\Rsync;

/**
 * Rsync
 */
trait RsyncTrait
{
    /**
     * Rsync.
     *
     * @var Rsync
     */
    private $rsync;

    /**
     * Set rsync
     *
     * @param Rsync $rsync
     *
     * @return static
     */
    public function setRsync(Rsync $rsync)
    {
        $this->rsync = $rsync;
    }

    /**
     * Get rsync
     *
     * @return Rsync
     */
    public function getRsync()
    {
        return $this->rsync;
    }
}
