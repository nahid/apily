<?php

use Nahid\Apily\Assertions\Attributes\Title;
use Nahid\Apily\Assertions\BaseAssertion;
use Psr\Http\Message\ResponseInterface;

return function (ResponseInterface $response) {
    return new class ($response) extends BaseAssertion
    {
        #[Title('Reviewreq all response Status is OK')]
        public function testStatusIsOk()
        {
            $this->assertStatusOk();
        }

        #[Title('Reviewreq all response Content-Type is application/json')]
        public function testContentTypeJson()
        {
            $contentType = $this->response->getHeaderLine('Content-Type');

            $this->assertEquals('application/md', $contentType, 'Content-Type is not application/json');
        }
    };
};
