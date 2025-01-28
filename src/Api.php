<?php

namespace Nahid\Apily;

use GuzzleHttp\Psr7\Request;
use Nahid\Apily\Utilities\Config;
use Nahid\Apily\Utilities\Helper;

class Api
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public static function from(string $path, array $vars = []): static
    {
        $path = str_replace('.', '/', $path);
        $filePath = getcwd().'/.apily/'.$path.'.api';


        if (!file_exists($file = $filePath)) {
            throw new \Exception("File not found: $file");
        }

        $json = file_get_contents($filePath);
        $json = Helper::replacePlaceholders($json, $vars);
        $config = json_decode($json, true);

        return new static($config);
    }

    public function request(): Request
    {
        return new Request(
            $this->getMethod(),
            $this->getFullUrl(),
            $this->getHeaders(),
            json_encode($this->getBody())
        );

    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Helper::arrayGet($this->config, $key, $default);
    }


    public function getMethod(): string
    {
        return $this->get('http.method', 'GET');
    }

    public function getFullUrl(): string
    {
        $baseUrl = rtrim(Config::baseUrl(), '/');
        $path = ltrim($this->get('http.path'), '/');

        return $baseUrl.'/'.$path;
    }

    public function getPath(): string
    {
        return $this->get('http.path');
    }

    public function getHeaders(): array
    {
        return $this->get('http.headers', []);
    }

    public function getBody(): mixed
    {
        return $this->get('http.body');
    }
}