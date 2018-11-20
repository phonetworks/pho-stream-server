<?php

require "vendor/autoload.php";

use Pho\Stream\Server;

$server = new Server();
$server->handle();