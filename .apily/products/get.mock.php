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
            return 200;
        }

        public function getHeaders(): array
        {
            return ['Content-Type' => 'application/json'];
        }

        public function getBody(): string|StreamInterface
        {
            $faker = Faker\Factory::create();
            $id = $faker->randomNumber(5);
            if (php_sapi_name() === 'cli') {
                $path = $this->request->getUri()->getPath();
                $params = explode('/', $path);
                $id = end($params);
            } else {
                $params = $this->request->getAttribute('uri_params');
                $id = Helper::arrayGet($params, 'url.id', $id);
            }


            $review = [
                'id' =>$id,
                'name' => $faker->name,
                'email' => $faker->email,
                'comment' => $faker->text,
                'rating' => $faker->numberBetween(1, 5),
                'created_at' => $faker->dateTimeThisYear->format('Y-m-d H:i:s'),
            ];

            return json_encode($review);
        }
    };
};
