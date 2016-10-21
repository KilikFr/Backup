<?php

namespace Kilik\Backup\Config;

use Kilik\Backup\Config\Traits\NameTrait;
use Kilik\Backup\Config\Traits\PathTrait;
use Kilik\Backup\Config\Traits\RsyncTrait;
use Kilik\Backup\Config\Traits\ServerTrait;

/**
 * Backup configuration
 */
class Backup
{
    use NameTrait;
    use PathTrait;
    use ServerTrait;
    use RsyncTrait;

    const TYPE_FILES = 'files';

    /**
     * Type.
     *
     * @var string
     */
    private $type = self::TYPE_FILES;

    /**
     * Snapshot.
     *
     * @var Snapshot
     */
    private $snapshot;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->rsync=new Rsync();
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return static
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get snapshot
     *
     * @param Snapshot $snapshot
     *
     * @return static
     */
    public function setSnapshot(Snapshot $snapshot)
    {
        $this->snapshot = $snapshot;

        return $this;
    }

    /**
     * Get snapshot
     *
     * @return Snapshot
     */
    public function getSnapshot()
    {
        return $this->snapshot;
    }

    /**
     * @param array $array
     *
     * @return static
     */
    public function setFromArray($array)
    {
        // load rsync config
        if (isset($array['rsync'])) {
            $this->rsync->setFromArray($array['rsync']);
        }

        if (isset($array['type'])) {
            $this->type = $array['type'];
        }

        if (isset($array['path'])) {
            $this->path = $array['path'];
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
     * Get as text
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * Get rsync options, depending backup, server, and global options
     *
     * @return string
     */
    public function getRsyncOptions()
    {
        // force backup options
        if($this->rsync->getOptions()) {
            return $this->rsync->getOptions().' '.$this->rsync->getMoreOptions();
        }

        if($this->getServer()->getRsync()->getOptions()) {
            $options=$this->getServer()->getRsync()->getOptions();
        }
        else {
            $options=$this->getServer()->getConfig()->getRsync()->getOptions();
        }

        if($this->rsync->getMoreOptions()) {
            $options.=' '.$this->rsync->getMoreOptions();
        }
        else if($this->getServer()->getRsync()->getMoreOptions()) {
            $options.=' '.$this->getServer()->getRsync()->getMoreOptions();
        }
        else if($this->getServer()->getConfig()->getRsync()->getMoreOptions()) {
            $options.=' '.$this->getServer()->getRsync()->getMoreOptions();
        }

        return $options;
    }
}
