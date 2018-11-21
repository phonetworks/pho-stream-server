<?php

use FastRoute\RouteCollector;

return function (RouteCollector $r) {

    $r->get('/', 'HomeController@index');

    $r->post('/feed/user/{user_id}', 'FeedController@addActivity');

    $r->post('/feed/user/{user_id}/follows', 'FeedController@follow');

    $r->get('/feed/user/{user_id}', 'FeedController@getUser');

    $r->get('/feed/timeline/{user_id}', 'FeedController@getTimeline');
};
