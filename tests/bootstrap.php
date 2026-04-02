<?php

declare(strict_types=1);

$autoloadPath = dirname(__DIR__).'/vendor/autoload.php';
if (is_file($autoloadPath)) {
    require $autoloadPath;

    return;
}

throw new RuntimeException(
    sprintf(
        'Composer autoload file not found at "%s". Run "composer install" in the bundle root.',
        $autoloadPath,
    ),
);
