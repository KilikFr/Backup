<?php

namespace App\Backup\Config\Traits;

/**
 * Simple options
 */
trait OptionsTrait
{
    /**
     * Options.
     *
     * @var string
     */
    private $options;

    /**
     * Set options
     *
     * @param string $options
     *
     * @return static
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * Get options
     *
     * @return string
     */
    public function getOptions()
    {
        return $this->options;
    }
}
