<?php

use FastRoute\RouteCollector;

return function (RouteCollector $r) {

    $r->get('/', 'HomeController@index');

    $r->post('/feed/user/{user_id}', 'FeedController@addActivity');

    $r->get('/feed/user/{user_id}', 'FeedController@get');
};
