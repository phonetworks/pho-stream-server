<?php

namespace Pho\Stream\Controller;

use Pho\Stream\Model\FeedModel;
use Psr\Http\Message\ServerRequestInterface;
use Rakit\Validation\Validator;
use Teapot\StatusCode;
use Zend\Diactoros\Response\JsonResponse;

class FeedController
{
    private $feedModel;

    public function __construct(FeedModel $feedModel)
    {
        $this->feedModel = $feedModel;
    }

    public function addActivity($user_id, ServerRequestInterface $request)
    {
        $body = $request->getBody()->getContents();
        $body = json_decode($body, true);

        $validator = new Validator();
        $validation = $validator->validate($body, [
            'actor' => 'required',
            'verb' => 'required',
            'object' => 'required',
        ]);

        if ($validation->fails()) {
            $errors = $validation->errors();
            return new JsonResponse($errors->toArray(), StatusCode::BAD_REQUEST);
        }

        $actor = $body['actor'];
        $verb = $body['verb'];
        $object = $body['object'];
        $text = $body['text'];

        $id = $this->feedModel->addActivity($user_id, $actor, $verb, $object, $text);

        $res = [
            'id' => $id,
            'actor' => $actor,
            'verb' => $verb,
            'object' => $object,
        ];

        return new JsonResponse($res);
    }

    public function get($user_id, ServerRequestInterface $request)
    {
        $queryParams = $request->getQueryParams();

        $validator = new Validator();
        $validation = $validator->validate($queryParams, [
            'count' => 'integer',
        ]);

        if ($validation->fails()) {
            $errors = $validation->errors();
            return new JsonResponse($errors->toArray(), StatusCode::BAD_REQUEST);
        }

        if (isset($queryParams['count'])) {

            $count = intval($queryParams['count']);

            $validation = $validator->validate([
                'count' => $count,
            ], [
                'count' => 'min:1|max:10',
            ]);

            if ($validation->fails()) {
                $errors = $validation->errors();
                return new JsonResponse($errors->toArray(), StatusCode::BAD_REQUEST);
            }

            $feed = $this->feedModel->get($user_id, $count);
        }
        else {
            $feed = $this->feedModel->get($user_id);
        }

        if (is_null($feed)) {
            return new JsonResponse([
                'results' => null,
            ], StatusCode::NOT_FOUND);
        }

        $results = array_map(function ($item) {
            return [
                'id' => $item[0],
                'actor' => $item[1][array_search('actor', $item[1]) + 1],
                'verb' => $item[1][array_search('verb', $item[1]) + 1],
                'object' => $item[1][array_search('object', $item[1]) + 1],
                'text' => $item[1][array_search('text', $item[1]) + 1],
            ];
        }, $feed[0][1]);

        $res = [
            'results' => $results,
        ];

        return new JsonResponse($res);
    }
}
