<?php

namespace Pho\Stream\Controller;

use Pho\Stream\Model\RedisModel;
use Zend\Diactoros\Response\JsonResponse;

class HomeController
{
    private $redisModel;

    public function __construct(RedisModel $redisModel)
    {
        $this->redisModel = $redisModel;
    }

    public function index()
    {
        return new JsonResponse([
            'message' => 'OK',
        ]);
    }
}
