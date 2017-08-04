<?php
/**
 * Class UserLanguage
 */
namespace RW\Services;

class UserLanguage
{
    public static $lang = null;
    public static $sessionKey = null;

    /**
     * Init
     * @param $sessionKey
     */
    public static function init($sessionKey)
    {
        self::$sessionKey = $sessionKey;
    }

    /**
     * Set User Language
     *
     * @param $lang
     *
     * @throws \Exception
     */
    public static function setLang($lang)
    {
        if (empty(self::$sessionKey)) {
            throw new \Exception("User Language Service is not properly init.");
        }
        self::$lang = $lang;
        $_SESSION[self::$sessionKey] = $lang;
    }

    /**
     * Get User Language
     */
    public static function getLang()
    {
        if (empty(self::$sessionKey)) {
            throw new \Exception("User Language Service is not properly init.");
        }

        if (self::$lang !== null) {
            return self::$lang;
        }

        //check session first
        if (!empty($_SESSION[self::$sessionKey])) {
            self::$lang = $_SESSION[self::$sessionKey];
            return self::$lang;
        }

        self::$lang = Languages::getBrowserLanguage();
        return self::$lang;
    }
}