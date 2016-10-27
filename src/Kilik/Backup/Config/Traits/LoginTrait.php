<?php

namespace Kilik\Backup\Config\Traits;

/**
 * Login
 */
trait LoginTrait
{
    /**
     * Login.
     *
     * @var string
     */
    private $login;

    /**
     * Set Login
     *
     * @param string $Login
     *
     * @return static
     */
    public function setLogin($Login)
    {
        $this->login = $Login;

        return $this;
    }

    /**
     * Get Login
     *
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }
}
