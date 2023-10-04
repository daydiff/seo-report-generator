<?php

class login
{

    private static $login;
    private static $pass;

    protected static $instance;  // object instance

    /**
     * Protecting from creating via new login()
     *
     * @return login
     */
    private function __construct()
    { /* ... */
    }

    /**
     * Protecting from creating by cloning
     *
     * @return login
     */
    private function __clone()
    { /* ... */
    }

    /**
     * Return a single class instance
     *
     * @return login
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new login;
            self::$login = 'avanta';
            self::$pass = 'avantapass';
        }
        return self::$instance;
    }

    public static function isTrue($ulogin, $upass)
    {
        $vector = self::getvector($ulogin, $upass);
        $pattern = self::getvector(self::$login, self::$pass);
        if ($vector == $pattern) {
            return true;
        } else {
            return false;
        }
    }

    public static function getVector($ulogin, $upass)
    {
        $vector = md5($ulogin . md5($upass) . 'avanta');
        return $vector;
    }

}
