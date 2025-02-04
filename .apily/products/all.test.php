<?php

use Nahid\Apily\Assertions\Attributes\Title;

return function (\Psr\Http\Message\ResponseInterface $response) {
    return new class($response) extends \Nahid\Apily\Assertions\BaseAssertion
    {
        #[Title('Response status code is 201')]
        public function testStatusCode(): void
        {
            $this->assertStatusCode(201);
        }

        public function testTitle(): void
        {
            $response = $this->response->getBody()->getContents();
            $data = json_decode($response, true);

            $this->assert(is_array($data['data']), 'Response is not an array');
        }
    };
};