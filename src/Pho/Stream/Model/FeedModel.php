<?php

namespace Pho\Stream\Model;

use Predis\Client;

class FeedModel
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function addActivity($userId, $actor, $verb, $object, $text)
    {
        $command = [ 'XADD', "user_{$userId}" , '*', 'actor', $actor, 'verb', $verb, 'object', $object, 'text', $text ];
        return $this->client->executeRaw($command);
    }

    public function get($userId, $count = 10)
    {
        $command = [ 'XREAD', 'COUNT', $count, 'STREAMS', "user_{$userId}", '0'];
        return $this->client->executeRaw($command);
    }
}
