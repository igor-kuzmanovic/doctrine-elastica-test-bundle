<?php

declare(strict_types=1);

namespace DoctrineElasticaTestBundle\DependencyInjection\Compiler;

use DoctrineElasticaTestBundle\Persister\TrackingObjectPersister;
use FOS\ElasticaBundle\Persister\ObjectPersister;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class DecoratePersistersCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds('fos_elastica.persister') as $serviceId => $tags) {
            if (!$container->hasDefinition($serviceId)) {
                continue;
            }

            $definition = $container->getDefinition($serviceId);
            if (!$this->isCompatiblePersisterDefinition($definition)) {
                continue;
            }

            $indexArgument = $definition->getArgument(0);
            $indexName = $serviceId;
            $firstTag = $tags[0] ?? null;

            if (\is_array($firstTag) && isset($firstTag['index']) && \is_string($firstTag['index'])) {
                $indexName = $firstTag['index'];
            }

            $decoratorId = sprintf('doctrine_elastica_test.tracking_object_persister.%s', $indexName);
            $innerServiceId = sprintf('%s.inner', $decoratorId);

            $decorator = new Definition(TrackingObjectPersister::class);
            $decorator->setDecoratedService($serviceId);
            $decorator->setArgument('$inner', new Reference($innerServiceId));
            $decorator->setArgument('$index', $indexArgument);
            $decorator->setArgument('$indexName', $indexName);

            $container->setDefinition($decoratorId, $decorator);
        }
    }

    private function isCompatiblePersisterDefinition(Definition $definition): bool
    {
        try {
            $definition->getArgument(0);
        } catch (\OutOfBoundsException) {
            return false;
        }

        if ($definition instanceof ChildDefinition) {
            return in_array($definition->getParent(), ['fos_elastica.object_persister', 'fos_elastica.object_serializer_persister'], true);
        }

        $className = $definition->getClass();
        if (null === $className) {
            return false;
        }

        return is_a($className, ObjectPersister::class, true);
    }
}
