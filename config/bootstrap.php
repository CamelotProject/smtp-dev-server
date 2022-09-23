<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

if (is_file(dirname(__DIR__) . '/vendor/autoload.php')) {
    $projectDir = dirname(__DIR__);
    require_once $projectDir . '/vendor/autoload.php';
} elseif (is_file(dirname(__DIR__, 3) . '/autoload.php')) {
    $projectDir = dirname(__DIR__, 3);
    require_once $projectDir . '/autoload.php';
} else {
    throw new LogicException('Composer autoload is missing. Try running "composer install".');
}

(new DotEnv())->bootEnv("{$projectDir}/.env");
