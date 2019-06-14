<?php declare(strict_types=1);

namespace Limepie;

class Cookie
{
    public static $domain = '';
    public static $keyStores = [];
    //private static $cookie = [];

    public function __construct($option = [])
    {
        if ($option['domain']) {
            self::$domain = $option['domain'];
        }
    }

    public static function setDomain($domainName)
    {
        self::$domain = $domainName;
    }

    public static function getDomain()
    {
        return self::$domain;
    }

    public static function setKeyStore($key, $value)
    {
        self::$keyStores[$key] = $value;
    }

    public static function getKeyStore($key)
    {
        return self::$keyStores[$key];
    }

    public static function set($key, $value, $expire = 0, $path = '/', $domain = null, $secure = true, $httpOnly = true)
    {
        $_value = Encrypt::pack($value);

        if (self::_set($key, $_value, $expire, $path, $domain, $secure, $httpOnly)) {
            return $_value;
        }

        return false;
    }

    public static function get($key, $check = false)
    {
        return self::_get($key) ? Encrypt::unpack(self::_get($key)) : null;
    }

    public static function remove($key, $value = '', $expire = 0, $path = '/', $domain = null, $secure = true, $httpOnly = true)
    {
        return self::_destroy($key, $value, $expire, $path, $domain, $secure, $httpOnly);
    }

    private static function _set($key, $value, $expire = 0, $path = '/', $domain = null, $secure = true, $httpOnly = true)
    {
        $key    = self::_sethost($key);
        $domain = true === (null === $domain) ? self::$domain : $domain;

        if (4096 < \strlen($value)) {
            die('4 KB per cookie maximum');
        }

        if (0 < $expire && \time() >= $expire) {
            $expire = \time() + $expire;
        }

        $_COOKIE[$key] = $value;

        return \setcookie($key, $value, $expire, $path, $domain, $secure, $httpOnly);
    }

    private static function _get($key)
    {
        $key = self::_sethost($key);

        return true === isset($_COOKIE[$key]) ? $_COOKIE[$key] : false;
    }

    private static function _destroy($key, $value = '', $expire = 0, $path = '/', $domain = null, $secure = true, $httpOnly = true)
    {
        $key    = self::_sethost($key);
        $domain = true === (null === $domain) ? self::$domain : $domain;

        if (isset($_COOKIE[$key])) {
            unset($_COOKIE[$key], $_REQUEST[$key]);
            $_COOKIE[$key] = $_REQUEST[$key] = null;

            return \setcookie($key, '', \time() - 3600, $path, $domain, $secure, $httpOnly);
        }

        return false;
    }

    private static function _sethost($key)
    {
        //$key = \str_replace('.', '_', $_SERVER['HTTP_HOST']) . '_' . $key;

        return $key;
    }
}

function store($key, $value = null)
{
    if ($value) {
        return Cookie::set($key, $value);
    }

    return Cookie::get($key);
}
