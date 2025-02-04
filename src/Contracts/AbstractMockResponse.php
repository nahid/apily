<?php

namespace Nahid\Apily\Contracts;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

abstract class AbstractMockResponse
{
    public function __construct(protected readonly Request $request)
    {
        //
    }
    abstract public function getStatusCode(): int;
    abstract public function getHeaders(): array;
    abstract public function getBody(): string|StreamInterface;
    public function make(): ResponseInterface
    {
        return new Response($this->getStatusCode(), $this->getHeaders(), $this->getBody());
    }
}
