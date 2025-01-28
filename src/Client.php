<?php

namespace Nahid\Apily;

use GuzzleHttp\Client as GuzzleClient;

class Client
{
    public function __construct()
    {

    }


    public function httpClient(): GuzzleClient
    {
        return new GuzzleClient();
    }

}