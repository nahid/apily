<?php

namespace Nahid\Apily;

use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Nahid\Apily\Utilities\Config;
use Nahid\Apily\Utilities\Helper;
use Psr\Http\Message\ResponseInterface;

class Api
{
    private static string $apiFilePath = '';
    private static string $apiBaseDir = '';
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public static function from(string $path, array $vars = []): static
    {
        $apiPath = self::getFilePath($path);

        if (!file_exists($apiPath)) {
            throw new \Exception("File not found: " . $apiPath);
        }

        $json = file_get_contents($apiPath);
        $json = Helper::replacePlaceholders($json, $vars);
        $config = json_decode($json, true);

        return new static($config);
    }

    public static function getFileDir(?string $path = null): string
    {
        if (self::$apiBaseDir !== '') {
            return self::$apiBaseDir;
        }

        if (is_null($path)) {
            throw new \Exception('Path is required');
        }

        $path = str_replace('.', '/', $path);
        $fileName = strrchr($path, '/');
        $baseDir = str_replace($fileName, '', $path);

        return self::$apiBaseDir = getcwd().'/.apily/' . $baseDir;
    }

    public static function getFilePath(?string $path = null): string
    {
        if (self::$apiFilePath !== '') {
            return self::$apiFilePath;
        }

        if (is_null($path)) {
            throw new \Exception('Path is required');
        }

        $baseDir = self::getFileDir($path);
        $fileBaseName = str_replace('.', '', strrchr($path, '.')) . '.api';

        return self::$apiFilePath = $baseDir . '/' . $fileBaseName;
    }

    public function request(): Request
    {
        $body = match ($this->get('http.body.type', 'empty')) {
            'json' => $this->handleJsonContent(),
            'multipart' => $this->handleMultipartContent(),
            'binary' => $this->handleBinaryContent(),
            'x-www-form-urlencoded' => $this->handleXWwwFormUrlencodedContent(),
            'form-data' => $this->handleFormDataContent(),
            'text' => $this->handleTextContent(),
            'empty' => null,
            default => throw new \Exception('Invalid body type'),
        };

        if (in_array($this->getMethod(), ['GET', 'HEAD'])) {
            $body = null;
        }

        $queryParams = array_replace_recursive(Config::getDefault('query', []), $this->get('http.query', []));
        $url = (new Uri($this->getFullUrl()))->withQuery(http_build_query($queryParams));

        $headers = array_replace_recursive(Config::getDefault('headers', []), $this->getHeaders());

        return new Request(
            $this->getMethod(),
            $url,
            $headers,
            $body
        );

    }

    private function handleJsonContent(): string
    {
        return json_encode($this->getBodyData());
    }

    private function handleMultipartContent(): MultipartStream
    {
        $body = $this->getBodyData();
        $multipartData = [];

        foreach ($body as $f) {
            $field = [
                'name' => $f['name'],
            ];

            $content = $f['content'];
            if ($f['type'] === 'file') {
                $field['contents'] = fopen($content, 'r');
                $field['filename'] = basename($content);
            }

            $multipartData[] = $field;
        }

        $this->setContentType('multipart/form-data');
        return new MultipartStream($multipartData);
    }

    private function handleBinaryContent(): string
    {
        $this->setContentType(mime_content_type($this->getBodyData()));

        return file_get_contents($this->getBodyData());
    }

    private function handleXWwwFormUrlencodedContent(): string
    {
        $this->setContentType('application/x-www-form-urlencoded');

        return http_build_query($this->getBodyData());
    }

    private function handleFormDataContent(): string
    {
        $this->setContentType('application/x-www-form-urlencoded');

        return http_build_query($this->getBodyData());
    }

    private function handleTextContent(): string
    {
        $this->setContentType('text/plain');

        return $this->getBodyData();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Helper::arrayGet($this->config, $key, $default);
    }


    public function getMethod(): string
    {
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD', 'TRACE', 'CONNECT'];
        $method = strtoupper($this->get('http.method', 'GET'));
        if (!in_array($method, $methods)) {
            throw new \Exception('Invalid HTTP method');
        }

        return $method;
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

    public function getBodyData(): mixed
    {
        return $this->get('http.body.data');
    }

    public function getMockType(): ?string
    {
        return $this->get('mock.type');
    }

    public function getMockFilePath(): string
    {
        $type = $this->getMockType();
        if (!$type) {
            return '';
        }

        if ($type === 'static') {
            $mockFileName = str_replace('.api', '.mock.json', basename(self::getFilePath()));

            $defaultPath = self::getFileDir() . '/' . $mockFileName;

            if (file_exists($defaultPath)) {
                return $defaultPath;
            }

            return self::getFileDir() . '/' . ltrim($this->get('mock.file', ''), '/');
        }

        if ($type == 'dynamic') {
            $mockFileName = str_replace('.api', '.mock.php', basename(self::getFilePath()));

            $defaultPath = self::getFileDir() . '/' . $mockFileName;

            if (file_exists($defaultPath)) {
                return $defaultPath;
            }

            return self::getFileDir() . '/' . ltrim($this->get('mock.file', ''), '/');
        }

        return '';
    }

    public function getMockResponse(): ?ResponseInterface
    {

        if ($this->getMockType() == 'static') {
            $mockFilePath = $this->getMockFilePath();

            if (file_exists($mockFilePath)) {
                $data = json_decode(file_get_contents($mockFilePath), true);
                $body = $data['body'];

                if (is_array($body)) {
                    $body = json_encode($body, JSON_PRETTY_PRINT);
                }

                return new Response(
                    $data['status'] ?? 200,
                        $data['headers'] ?? [],
                    $body
                );

            }
        }

        $mockFilePath = $this->getMockFilePath();

        $mockFn = require $mockFilePath;
        $mockClass = $mockFn($this->request());

        if (!$mockClass instanceof Contracts\AbstractMockResponse) {

            throw new \Exception('Invalid mock response class');
        }

        return $mockClass->make($this->request());
    }


    public function setContentType(string $type): void
    {
        $this->config['http']['headers']['Content-Type'] = $type;
    }
}
