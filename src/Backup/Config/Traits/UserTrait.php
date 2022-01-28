<?php

namespace App\Backup\Config\Traits;

/**
 * User
 */
trait UserTrait
{
    /**
     * User.
     *
     * @var string
     */
    private $user;

    /**
     * Set User
     *
     * @param string $User
     *
     * @return static
     */
    public function setUser($User)
    {
        $this->user = $User;

        return $this;
    }

    /**
     * Get User
     *
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }
}
