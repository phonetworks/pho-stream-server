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
            'text' => $text,
        ];

        return new JsonResponse($res);
    }

    public function follow($user_id, ServerRequestInterface $request)
    {
        $queryParams = $request->getQueryParams();

        $validator = new Validator();
        $validation = $validator->validate($queryParams, [
            'target' => "required|not_in:user_{$user_id}",
        ]);

        if ($validation->fails()) {
            $errors = $validation->errors();
            return new JsonResponse($errors->toArray(), StatusCode::BAD_REQUEST);
        }

        $target = $queryParams['target'];

        if (! $this->feedModel->feedExists($target)) {
            return new JsonResponse([
                'target' => "Invalid target {$target}",
            ], StatusCode::BAD_REQUEST);
        }

        $ret = $this->feedModel->follow($user_id, $target);

        return new JsonResponse([
            'success' => boolval($ret),
        ]);
    }

    public function getUser($user_id, ServerRequestInterface $request)
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
                'count' => 'min:1|max:100',
            ]);

            if ($validation->fails()) {
                $errors = $validation->errors();
                return new JsonResponse($errors->toArray(), StatusCode::BAD_REQUEST);
            }

            $feed = $this->feedModel->getUser($user_id, $count);
        }
        else {
            $feed = $this->feedModel->getUser($user_id);
        }

        if (is_null($feed)) {
            return new JsonResponse([
                'results' => null,
            ], StatusCode::NOT_FOUND);
        }

        $res = [
            'results' => $feed,
        ];

        return new JsonResponse($res);
    }

    public function getTimeline($user_id, ServerRequestInterface $request)
    {
        $feed = $this->feedModel->getTimeline($user_id);

        return new JsonResponse([
            'results' => $feed,
        ]);
    }
}
