<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;
use Predis\Client;

return [

    ServerRequestInterface::class => function () {
        return ServerRequestFactory::fromGlobals(
            $_SERVER,
            $_GET,
            $_POST,
            $_COOKIE,
            $_FILES
        );
    },
    ResponseInterface::class => \DI\create(Response::class),

    Client::class => function () {
        $uri = config('redis.uri');
        // multitenant case
        if(isset($_REQUEST["api_key"])) {
            $client = new Client($uri);
            $_ = $client->get($_REQUEST["api_key"]);
            if(!empty($_)) {
                $client = new Client($uri."?".$_);
            }
        }
        elseif ($uri) {
            $client = new Client($uri);
        }
        else {
            $client = new Client();
        }
        return $client;
    },

];
