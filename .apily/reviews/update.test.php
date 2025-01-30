<?php

use Nahid\Apily\Assertions\Attributes\Title;
use Nahid\Apily\Assertions\BaseAssertion;
use Psr\Http\Message\ResponseInterface;

return function (ResponseInterface $response) {
    return new class ($response) extends BaseAssertion
    {
        #[Title('Reviews update Response Status is OK')]
        public function testStatusIsOk()
        {
            $this->assertStatusOk();
        }
    };
};
