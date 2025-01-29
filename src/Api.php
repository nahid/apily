<?php

namespace Nahid\Apily;

use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
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


        if (!file_exists($filePath)) {
            throw new \Exception("File not found: $filePath");
        }

        $json = file_get_contents($filePath);
        $json = Helper::replacePlaceholders($json, $vars);
        $config = json_decode($json, true);

        return new static($config);
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

    public function setContentType(string $type): void
    {
        $this->config['http']['headers']['Content-Type'] = $type;
    }
}