<?php

use Nahid\Apily\Contracts\AbstractMockResponse;
use Psr\Http\Message\StreamInterface;

return function (\Psr\Http\Message\RequestInterface|\Psr\Http\Message\ServerRequestInterface $request): AbstractMockResponse
{
    return new class($request) extends AbstractMockResponse {

        public function getStatusCode(): int
        {
            return 200;
        }

        public function getHeaders(): array
        {
            return ['Content-Type' => 'application/json'];
        }

        public function getBody(): string|StreamInterface
        {
            $faker = Faker\Factory::create();
            $reviewReqs = [];

            for ($i = 0; $i < 10; $i++) {
                $reviewReqs[] = [
                    'id' => $faker->randomNumber(5),
                    'name' => $faker->name,
                    'email' => $faker->email,
                    'comment' => $faker->text,
                    'rating' => $faker->numberBetween(1, 5),
                    'created_at' => $faker->dateTimeThisYear->format('Y-m-d H:i:s'),
                ];
            }

            return json_encode($reviewReqs);
        }
    };
};
