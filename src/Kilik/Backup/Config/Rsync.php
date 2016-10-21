<?php

namespace Kilik\Backup\Config;

use Kilik\Backup\Config\Traits\MoreOptionsTrait;
use Kilik\Backup\Config\Traits\NameTrait;
use Kilik\Backup\Config\Traits\OptionsTrait;
use Kilik\Backup\Config\Traits\PathTrait;

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
