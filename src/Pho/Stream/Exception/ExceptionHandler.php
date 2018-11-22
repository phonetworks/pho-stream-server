<?php

namespace Pho\Stream\Exception;

use Teapot\StatusCode;
use Zend\Diactoros\Response\JsonResponse;

class ExceptionHandler
{
    public function handle(\Exception $ex)
    {
        switch (get_class($ex)) {

            case ValidationFailedException::class:
                $response = new JsonResponse($ex->getErrorBag()->toArray(), StatusCode::BAD_REQUEST);
                break;

            default:
                $response = new JsonResponse([
                    'message' => (string) $ex,
                ], StatusCode::INTERNAL_SERVER_ERROR);
        }

        return $response;
    }
}
