<?php

namespace Nahid\Apily;

use GuzzleHttp\Psr7\MultipartStream;
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
        match ($this->get('http.type', 'empty')) {
            'json' => $body = $this->handleJsonContent(),
            'multipart' => $body = $this->handleMultipartContent(),
            'binary' => $body = $this->handleBinaryContent(),
            'x-www-form-urlencoded' => $body = $this->handleXWwwFormUrlencodedContent(),
            'form-data' => $body = $this->handleFormDataContent(),
            'text' => $body = $this->handleTextContent(),
            'empty' => $body = null,
        };

        if (in_array($this->getMethod(), ['GET', 'HEAD'])) {
            $body = null;
        }
        
        return new Request(
            $this->getMethod(),
            $this->getFullUrl(),
            $this->getHeaders(),
            $body
        );

    }

    private function handleJsonContent(): string
    {
        return json_encode($this->getBody());
    }

    private function handleMultipartContent(): MultipartStream
    {
        $body = $this->getBody();
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
        $this->setContentType(mime_content_type($this->getBody()));

        return file_get_contents($this->getBody());
    }

    private function handleXWwwFormUrlencodedContent(): string
    {
        $this->setContentType('application/x-www-form-urlencoded');

        return http_build_query($this->getBody());
    }

    private function handleFormDataContent(): string
    {
        $this->setContentType('application/x-www-form-urlencoded');

        return http_build_query($this->getBody());
    }

    private function handleTextContent(): string
    {
        $this->setContentType('text/plain');

        return $this->getBody();
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

    public function getBody(): mixed
    {
        return $this->get('http.body');
    }

    public function setContentType(string $type): void
    {
        $this->config['http']['headers']['Content-Type'] = $type;
    }
}