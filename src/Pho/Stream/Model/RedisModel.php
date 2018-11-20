<?php

namespace Pho\Stream\Model;

use Predis\Client;

class RedisModel
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }
}
