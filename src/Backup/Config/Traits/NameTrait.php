<?php

namespace App\Backup\Config\Traits;

/**
 * Simple name
 */
trait NameTrait
{
    /**
     * Name.
     *
     * @var string
     */
    private $name;

    /**
     * Set name
     *
     * @param string $name
     *
     * @return static
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
