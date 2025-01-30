<?php

namespace Nahid\Apily\Utilities;

class Config
{
    protected static ?array $config = null;
    protected static array $args = [];

    public static function init(array $arguments = []): void
    {
        self::$args = $arguments;
        if (self::loadConfig()) {
            self::processEnv();
            self::processDefaults();

        }


    }

    protected static function loadConfig(): bool
    {
        if (is_null(self::$config)) {
            if (!file_exists(getcwd().'/apily.conf')) {
                echo "apily.conf file not found. Please run 'apily init' command to create the file.";
                return false;
            }

            $json = file_get_contents(getcwd().'/apily.conf');
            self::$config = json_decode($json, true);

        }

        return true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Helper::arrayGet(self::$config, $key, $default);
    }

    public static function baseUrl(): string
    {
        $url = self::get('baseUrl', 'http://localhost');

        if ($envBaseUrl = self::getFromEnv('baseUrl')) {
            $url = $envBaseUrl;
        }

        return $url;
    }

    private static function processEnv(): void
    {
        $appEnv = self::get('apiEnv', 'local');
        if (isset(self::$config['environments'][$appEnv])) {
            self::$config['env'] = self::$config['environments'][$appEnv];
        }

        unset(self::$config['environments']);
    }

    public static function getEnvs(): array
    {
       return self::get('env', []);
    }

    public static function getFromEnv(string $key, mixed $default = null): mixed
    {
        $env = self::getEnvs();
        return Helper::arrayGet($env, $key, $default);
    }

    private static function processDefaults(): void
    {
        $defaultValue = self::get('defaults', []);


        $json = json_encode($defaultValue, JSON_THROW_ON_ERROR);
        $json = Helper::replacePlaceholders($json, self::makeEnvVariables([]));

        self::$config['defaults'] = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    public static function getDefaults(): array
    {
        return self::$config['defaults'] ?? [];

    }

    public static function getDefault(string $key, mixed $default = null): mixed
    {
        $defaults = self::getDefaults();
        return Helper::arrayGet($defaults, $key, $default);
    }

    public static function makeEnvVariables(array $arguments = []): array
    {
        $args = self::$args;
        if (!empty($arguments)) {
            $args = $arguments;
        }

        return [
            'args' => $args,
            'config' => self::$config,
            'env' => self::getEnvs(),
        ];
    }

}
