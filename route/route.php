<?php

use FastRoute\RouteCollector;

return function (RouteCollector $r) {

    $r->get('/', 'HomeController@index');
};
