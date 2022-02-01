<?php

namespace App\Backup\Config;

use App\Backup\Config\Traits\NameTrait;
use App\Backup\Config\Traits\OptionsTrait;
use App\Backup\Config\Traits\PathTrait;
use App\Backup\Config\Traits\ServerTrait;

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
     * Command to execute before creating snapshot
     *
     * @var string
     */
    private $execBeforeCreate;

    /**
     * Command to execute after creating snapshot
     *
     * @var string
     */

    private $execAfterCreate;

    /**
     * Command to execute after creating snapshot failure
     *
     * @var string
     */

    private $execAfterCreateFailed;

    /**
     * Command to execute before removing snapshot
     *
     * @var string
     */
    private $execBeforeRemove;

    /**
     * Command to execute after removing snapshot
     *
     * @var string
     */
    private $execAfterRemove;

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
        if (isset($array['exec_before_create'])) {
            $this->execBeforeCreate = $array['exec_before_create'];
        }
        if (isset($array['exec_after_create'])) {
            $this->execAfterCreate = $array['exec_after_create'];
        }
        if (isset($array['exec_after_create_failed'])) {
            $this->execAfterCreateFailed = $array['exec_after_create_failed'];
        }
        if (isset($array['exec_before_remove'])) {
            $this->execBeforeRemove = $array['exec_before_remove'];
        }
        if (isset($array['exec_after_remove'])) {
            $this->execAfterRemove = $array['exec_after_remove'];
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
     * Get execBeforeCreate
     *
     * @return string
     */
    public function getExecBeforeCreate()
    {
        return $this->execBeforeCreate;
    }

    /**
     * Get execAfterCreate
     *
     * @return string
     */
    public function getExecAfterCreate()
    {
        return $this->execAfterCreate;
    }

    /**
     * Get execAfterCreateFailed
     *
     * @return string
     */
    public function getExecAfterCreateFailed()
    {
        return $this->execAfterCreateFailed;
    }

    /**
     * Get execBeforeRemove
     *
     * @return string
     */
    public function getExecBeforeRemove()
    {
        return $this->execBeforeRemove;
    }

    /**
     * Get execAfterRemove
     *
     * @return string
     */
    public function getExecAfterRemove()
    {
        return $this->execAfterRemove;
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
        if (is_null($this->group) || $this->group == '') {
            throw new \Exception('group is not defined in snapshot \''.$this->name.'\'');
        }
        if (is_null($this->volume) || $this->volume == '') {
            throw new \Exception('volume is not defined in snapshot \''.$this->name.'\'');
        }
        if (is_null($this->size) || $this->size == '') {
            throw new \Exception('size is not defined in snapshot \''.$this->name.'\'');
        }
        if (!preg_match('|[0-9]{1,}G|', $this->size)) {
            throw new \Exception('size is not in good format \''.$this->name.'\', should be like \'10G\'');
        }
        if (is_null($this->mount) || $this->mount == '') {
            throw new \Exception('mount point (mount) is not defined in snapshot \''.$this->name.'\'');
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
}
