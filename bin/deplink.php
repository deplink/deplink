<?php

require __DIR__ . '/../bootstrap/kernel.php';

// Script Execution Configuration
set_time_limit(0); // disable timeout
ini_set('memory_limit', '128M');

// DI Container
$builder = new DI\ContainerBuilder();
$builder->addDefinitions(ROOT_DIR . '/config/container.php');
$container = $builder->build();

// Environment Variables
$config = $container->get(\Deplink\Environment\Config::class);
$config->setDir(ROOT_DIR . '/config');

// Console Application
$application = new Symfony\Component\Console\Application();
$application->setName($config->get('console.name'));
$application->setVersion($config->get('console.version'));

foreach ($config->get('console.commands') as $command) {
    $application->add($container->make($command));
}

$application->run();
