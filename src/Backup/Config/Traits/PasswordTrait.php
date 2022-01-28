<?php

namespace App\Backup\Config\Traits;

/**
 * Password
 */
trait PasswordTrait
{
    /**
     * Password.
     *
     * @var string
     */
    private $password;

    /**
     * Set Password
     *
     * @param string $Password
     *
     * @return static
     */
    public function setPassword($Password)
    {
        $this->password = $Password;

        return $this;
    }

    /**
     * Get Password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }
}
