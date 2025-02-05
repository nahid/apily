<?php

namespace Nahid\Apily\Server;

use Nahid\Apily\Api;
use Nahid\Apily\Utilities\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MockServer
{
    public function __construct(private readonly ServerRequestInterface $request)
    {
    }
    public function serve(string $name): void
    {
        if (!Config::get('mock_enabled', false)) {
            header(sprintf('HTTP/%s %s %s', '1.1', 411, 'Mocking is not enabled'));
            echo '';

            return;
        }

        $api = Api::from($name, request: $this->request);

        $response = $api->getMockResponse();
        $response = $response->withHeader('X-Powered-By', 'Apily');

        header(sprintf('HTTP/%s %s %s', '1.1', $response->getStatusCode(), $response->getReasonPhrase()));

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), true);
            }
        }

        echo $response->getBody();
    }
}
