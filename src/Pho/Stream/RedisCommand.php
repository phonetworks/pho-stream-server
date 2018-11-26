<?php

namespace Pho\Stream;

use Predis\Client;

class RedisCommand
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function xadd($key, $id, array $dictionary)
    {
        if (empty($dictionary)) {
            throw new \InvalidArgumentException('empty dictionary');
        }
        $fieldValues = [];
        foreach ($dictionary as $field => $value) {
            $fieldValues[] = $field;
            $fieldValues[] = $value;
        }
        $command = array_merge(
            [ 'XADD', $key, $id ],
            $fieldValues
        );

        return $this->client->executeRaw($command);
    }

    public function xread(array $keys, array $ids, $count = null)
    {
        if ($keys)
            if (count($keys) !== count($ids)) {
                throw new \InvalidArgumentException('Number of keys and ids not equal');
            }
        $cmdCount = $count ? [ 'COUNT', $count ] : [];
        $command = array_merge(
            [ 'XREAD' ],
            $cmdCount,
            [ 'STREAMS' ],
            $keys,
            $ids
        );

        $response = $this->client->executeRaw($command);

        $parsedResponse =  array_reduce($response, function ($acc, $streamData) {
            $streamName = $streamData[0];
            $streamEntries = $this->parseStreamEntries($streamData[1]);
            return $acc + [
                $streamName => $streamEntries,
            ];
        }, []);

        return $parsedResponse;
    }

    public function parseStreamEntries($entriesData)
    {
        return array_reduce($entriesData, function ($acc, $entryData) {
            $entryId = $entryData[0];
            $dictionaryData = $entryData[1];
            $dictionary = [];
            for ($i = 0; $i < count($dictionaryData); $i = $i + 2) {
                $dictionary[$dictionaryData[$i]] = $dictionaryData[$i + 1];
            }
            return $acc + [
                $entryId => $dictionary,
            ];
        }, []);
    }

    public function xrevrange($key, $end, $start, $count = null)
    {
        $cmdCount = $count ? [ 'COUNT', $count ] : [];
        $command = array_merge(
            [ 'XREVRANGE', $key, $end, $start ],
            $cmdCount
        );

        $response = $this->client->executeRaw($command);
        $parsedResponse = $this->parseStreamEntries($response);

        return $parsedResponse;
    }
}
