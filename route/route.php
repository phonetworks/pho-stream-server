<?php

use FastRoute\RouteCollector;

return function (RouteCollector $r) {

    $r->get('/', 'HomeController@index');

    $r->post('/feed/{feed_slug}/{user_id}', 'FeedController@addActivity');

    $r->post('/feed/{feed_slug}/{user_id}/follows', 'FeedController@follow');

    $r->get('/feed/{feed_slug}/{user_id}', 'FeedController@get');
};
