<?php

use Nahid\Apily\Assertions\Attributes\Title;

return function (\Psr\Http\Message\ResponseInterface $response) {
    return new class($response) extends \Nahid\Apily\Assertions\BaseAssertion
    {

    };
};