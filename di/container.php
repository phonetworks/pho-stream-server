<?php

use DI\ContainerBuilder;

$builder = new ContainerBuilder();
$builder->addDefinitions(require 'config.php');
$container = $builder->build();

return $container;
