<?php

namespace Nahid\Apily\Assertions;

use Psr\Http\Message\ResponseInterface;

abstract class BaseAssertion
{
    public function __construct(protected ResponseInterface $response)
    {
    }

    public function assertStatusCode(int $statusCode, ?string $message = null): void
    {
        if ($message === null) {
            $message = "Status code is not equal to $statusCode";
        }

        $actualStatusCode = $this->response->getStatusCode();
        if ($actualStatusCode !== $statusCode) {
            throw new \Exception($message);
        }
    }

    public function assertStatusOk(?string $message = null): void
    {
        $this->assertStatusCode(200, $message);
    }

    public function assert(bool $condition, string $message = 'Assertion failed'): void
    {
        if (!$condition) {
            throw new \Exception($message);
        }
    }

    public function assertJson(string $json, string $message = 'Assertion failed'): void
    {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON string');
        }
    }


    public function assertEquals($expected, $actual, ?string $message = null): void
    {
        if ($message === null) {
            $message = "Expected value is not equal to actual value";
        }

        if ($expected !== $actual) {
            throw new \Exception($message);
        }
    }

    public function assertNotEquals($expected, $actual, ?string $message = null): void
    {
        if ($message === null) {
            $message = "Expected value is equal to actual value";
        }

        if ($expected === $actual) {
            throw new \Exception($message);
        }
    }

    public function assertContains(string $needle, string $haystack, ?string $message = null): void
    {
        if ($message === null) {
            $message = "Needle not found in haystack";
        }

        if (!str_contains($haystack, $needle)) {
            throw new \Exception($message);
        }
    }

    public function assertNotContains(string $needle, string $haystack, ?string $message = null): void
    {
        if ($message === null) {
            $message = "Needle found in haystack";
        }

        if (str_contains($haystack, $needle)) {
            throw new \Exception($message);
        }
    }

    public function assertArrayHasKey(string $key, array $array, ?string $message = null): void
    {
        if ($message === null) {
            $message = "Key not found in array";
        }

        if (!array_key_exists($key, $array)) {
            throw new \Exception($message);
        }
    }

    public function assertNotEmpty($param)
    {
        if (empty($param)) {
            throw new \Exception('Parameter is empty');
        }
    }

    public function assertEmpty($param)
    {
        if (!empty($param)) {
            throw new \Exception('Parameter is not empty');
        }
    }


}