<?php

namespace App\Backup\Config\Traits;

/**
 * Path
 */
trait PathTrait
{
    /**
     * Path.
     *
     * @var string
     */
    private $path;

    /**
     * Set path
     *
     * @param string $path
     *
     * @return static
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
}
