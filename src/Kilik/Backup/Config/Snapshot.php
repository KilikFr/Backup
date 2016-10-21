<?php

namespace Kilik\Backup\Config;

use Kilik\Backup\Config\Traits\NameTrait;
use Kilik\Backup\Config\Traits\OptionsTrait;
use Kilik\Backup\Config\Traits\PathTrait;
use Kilik\Backup\Config\Traits\ServerTrait;

/**
 * Snapshot configuration
 */
class Snapshot
{
    use NameTrait;
    use ServerTrait;

    /**
     * Group (ex: /dev/vg).
     *
     * @var string
     */
    private $group;

    /**
     * Volume (ex: home).
     *
     * @var string
     */
    private $volume;

    /**
     * Size
     *
     * @var string
     */
    private $size = '1G';

    /**
     * Mount point
     *
     * @var string
     */
    private $mount;

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * @param array $array
     *
     * @return static
     */
    public function setFromArray($array)
    {
        if (isset($array['group'])) {
            $this->group = $array['group'];
        }
        if (isset($array['mount'])) {
            $this->mount = $array['mount'];
        }
        if (isset($array['size'])) {
            $this->size = $array['size'];
        }
        if (isset($array['volume'])) {
            $this->volume = $array['volume'];
        }

        return $this;
    }

    /**
     * Get mount point
     *
     * @return string
     */
    public function getMount()
    {
        return $this->mount;
    }

    /**
     * Get create command line
     *
     * @return string
     */
    public function getCreateCmdLine()
    {
        return '--snapshot --name '.$this->name.' --size '.$this->size.' '.$this->group.'/'.$this->volume;
    }

    /**
     * Get remove command line
     *
     * @return string
     */
    public function getRemoveCmdLine()
    {
        return ' -f '.$this->group.'/'.$this->name;
    }

    /**
     * Get mount command line
     *
     * @return string
     */
    public function getMountCmdLine()
    {
        return ' -o ro '.$this->group.'/'.$this->name.' '.$this->mount;
    }

    /**
     * Get umount command line
     *
     * @return string
     */
    public function getUmountCmdLine()
    {
        return ' '.$this->group.'/'.$this->name;
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
}
