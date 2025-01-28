<?php

namespace Nahid\Apily;

use GuzzleHttp\Psr7\Request;

class Http
{
    protected string $method;
    protected string $url;
    protected array $headers;
    protected array $body;

    public function __construct(string $method, string $url, array $headers = [], array $body = [])
    {
        $this->method = $method;
        $this->url = $url;
        $this->headers = $headers;
        $this->body = $body;
    }

    public function request(): Request
    {
        return new Request($this->method, $this->url, $this->headers, json_encode($this->body));
    }


}