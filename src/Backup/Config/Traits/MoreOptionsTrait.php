<?php

namespace App\Backup\Config\Traits;

/**
 * More options
 */
trait MoreOptionsTrait
{
    /**
     * More Options.
     *
     * @var string
     */
    private $moreOptions;

    /**
     * Set more options
     *
     * @param string $moreOptions
     *
     * @return static
     */
    public function setMoreOptions($moreOptions)
    {
        $this->moreOptions = $moreOptions;
    }

    /**
     * Get more options
     *
     * @return string
     */
    public function getMoreOptions()
    {
        return $this->moreOptions;
    }
}
