<?php

namespace Linguise\Vendor\Linguise\Script\Core;

defined('LINGUISE_SCRIPT_TRANSLATION') or die();

class Hook
{

    private static $hooks = [];

    public static function add($name, $class)
    {
        self::$hooks[$name] = $class;
    }

    public static function trigger($name, &...$parameters)
    {
        if (!empty(self::$hooks[$name]) && is_callable([self::$hooks[$name], $name])) {
            call_user_func_array([self::$hooks[$name], $name], $parameters);
        }
    }

    public static function wait($name, &...$parameters)
    {
        if (!empty(self::$hooks[$name]) && is_callable(self::$hooks[$name].'::'.$name)) {
            return call_user_func(self::$hooks[$name].'::'.$name, $parameters);
        }

        return null;
    }
}