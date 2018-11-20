<?php

namespace Pho\Stream\Exception;

use Teapot\StatusCode;
use Zend\Diactoros\Response\JsonResponse;

class ExceptionHandler
{
    public function handle(\Exception $ex)
    {
        $response = new JsonResponse([
            'message' => (string) $ex,
        ]);
        $response = $response->withStatus(StatusCode::INTERNAL_SERVER_ERROR);

        return $response;
    }
}
