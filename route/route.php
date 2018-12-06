<?php

use FastRoute\RouteCollector;

return function (RouteCollector $r) {

    $r->post('/v1.0/feed/{feed_slug}/{user_id}/', 'FeedController@addActivity');

    $r->post('/v1.0/feed/{feed_slug}/{user_id}/follows/', 'FeedController@follow');

    $r->get('/v1.0/feed/{feed_slug}/{user_id}/', 'FeedController@get');

    $r->get('/v1.0/enrich/feed/{feed_slug}/{user_id}/', 'FeedController@get');
};
