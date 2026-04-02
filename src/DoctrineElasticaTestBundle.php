<?php

declare(strict_types=1);

namespace DoctrineElasticaTestBundle;

use DoctrineElasticaTestBundle\DependencyInjection\Compiler\DecoratePersistersCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class DoctrineElasticaTestBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new DecoratePersistersCompilerPass());
    }
}
