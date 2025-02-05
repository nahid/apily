<?php

use Nahid\Apily\Contracts\AbstractMockResponse;
use Nahid\Apily\Utilities\Helper;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

return function (RequestInterface|ServerRequestInterface $request): AbstractMockResponse
{
    return new class($request) extends AbstractMockResponse {

        public function getStatusCode(): int
        {
            return 201;
        }

        public function getHeaders(): array
        {
            return ['Content-Type' => 'application/json'];
        }

        public function getBody(): string|StreamInterface
        {
            $payload = json_decode($this->request->getBody()->getContents(), true);
            $payload['id'] = rand(1000, 9999);
            $payload['status'] = 'active';
            $payload['created_at'] = date('Y-m-d H:i:s');


            return json_encode($payload);
        }
    };
};
