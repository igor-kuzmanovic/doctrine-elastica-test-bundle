<?php

declare(strict_types=1);

return (new PhpCsFixer\Config())
    ->setFinder(PhpCsFixer\Finder::create()->in([__DIR__.'/src', __DIR__.'/tests']))
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS' => true,
        '@PER-CS:risky' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
    ]);
