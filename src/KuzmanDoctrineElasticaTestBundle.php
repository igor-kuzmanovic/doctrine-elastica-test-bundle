<?php

declare(strict_types=1);

namespace Kuzman\DoctrineElasticaTestBundle;

use Kuzman\DoctrineElasticaTestBundle\DependencyInjection\Compiler\DecoratePersistersCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class KuzmanDoctrineElasticaTestBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new DecoratePersistersCompilerPass());
    }
}
