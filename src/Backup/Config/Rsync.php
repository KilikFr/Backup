<?php

namespace App\Backup\Config;

use App\Backup\Config\Traits\MoreOptionsTrait;
use App\Backup\Config\Traits\NameTrait;
use App\Backup\Config\Traits\OptionsTrait;
use App\Backup\Config\Traits\PathTrait;

/**
 * Rsync configuration
 */
class Rsync
{
    use OptionsTrait;
    use MoreOptionsTrait;

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
        if (isset($array['options'])) {
            $this->options = $array['options'];
        }
        if (isset($array['more_options'])) {
            $this->moreOptions = $array['more_options'];
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
}
