<?php

namespace Nahid\Apily\Utilities;

class Config
{
    protected static ?array $config = null;

    protected static function init()
    {
        if (is_null(self::$config)) {
            $json = file_get_contents(getcwd().'/apily.conf');
            self::$config = json_decode($json, true);
        }

    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::init();
        return Helper::arrayGet(self::$config, $key, $default);
    }

    public static function baseUrl(): string
    {
        return self::get('baseUrl', 'http://localhost');
    }

    public static function makeEnvVariables(array $arguments): array
    {
        self::init();
        $config = self::$config;
        $config['env'] = [];
        $env = self::get('apiEnv', 'local');
        if (isset($config['environments'][$env])) {
            $env = $config['environments'][$env];
        }

        unset($config['environments']);

        return [
            'args' => $arguments,
            'config' => $config,
            'env' => $env,
        ];
    }

}