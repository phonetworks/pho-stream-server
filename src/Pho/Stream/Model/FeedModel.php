<?php

namespace Pho\Stream\Model;

use Pho\Stream\RedisCommand;
use Predis\Client;

class FeedModel
{
    private $client;
    private $redisCommand;

    public function __construct(Client $client, RedisCommand $redisCommand)
    {
        $this->client = $client;
        $this->redisCommand = $redisCommand;
    }

    public function addActivity($userId, $actor, $verb, $object, $text)
    {
        return $this->redisCommand->xadd("user_{$userId}", '*', [
            'actor' => $actor,
            'verb' => $verb,
            'object' => $object,
            'text' => $text,
        ]);
    }

    public function feedExists($feed)
    {
        return (bool) $this->client->exists($feed);
    }

    public function follow($userId, $target)
    {
        return $this->client->sadd("timeline_{$userId}", $target);
    }

    public function getUser($userId, $count = 25)
    {
        $stream = "user_{$userId}";
        $response = $this->redisCommand->xread(
            [ $stream ],
            [ '0' ],
            $count
        );

        $feed = [];

        foreach ($response[$stream] as $id => $dictionary) {
            $feed[] = [
                'id' => $id,
            ] + $dictionary;
        }

        return $feed;
    }

    public function getTimeline($userId, $count = 25)
    {
        $targets = $this->client->smembers("timeline_{$userId}");

        if (empty($targets)) {
            return [];
        }

        $ids = array_fill(0, count($targets), '0');
        $response = $this->redisCommand->xread($targets, $ids, $count);

        $feed = [];

        foreach ($response as $stream => $streamData) {
            foreach ($streamData as $id => $dictionary) {
                $feed[] = [
                    'id' => $id,
                ] + $dictionary;
            }
        }

        return $feed;
    }
}
