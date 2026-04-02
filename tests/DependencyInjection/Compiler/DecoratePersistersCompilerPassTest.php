<?php

declare(strict_types=1);

namespace DoctrineElasticaTestBundle\Tests\DependencyInjection\Compiler;

use DoctrineElasticaTestBundle\DependencyInjection\Compiler\DecoratePersistersCompilerPass;
use DoctrineElasticaTestBundle\Persister\TrackingObjectPersister;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class DecoratePersistersCompilerPassTest extends TestCase
{
    public function testDecoratesOnlyCompatiblePersisters(): void
    {
        $container = new ContainerBuilder();

        $compatible = new ChildDefinition('fos_elastica.object_persister');
        $compatible->setArgument(0, new Reference('elastica.index.foo'));
        $compatible->addTag('fos_elastica.persister', ['index' => 'foo']);
        $container->setDefinition('fos_elastica.object_persister.foo', $compatible);

        $incompatible = new Definition(\stdClass::class);
        $incompatible->addTag('fos_elastica.persister', ['index' => 'app']);
        $container->setDefinition('fos_elastica.object_persister.app', $incompatible);

        $compilerPass = new DecoratePersistersCompilerPass();
        $compilerPass->process($container);

        self::assertTrue($container->hasDefinition('doctrine_elastica_test.tracking_object_persister.foo'));
        self::assertFalse($container->hasDefinition('doctrine_elastica_test.tracking_object_persister.app'));

        $decorator = $container->getDefinition('doctrine_elastica_test.tracking_object_persister.foo');

        self::assertSame(TrackingObjectPersister::class, $decorator->getClass());
        self::assertSame(['fos_elastica.object_persister.foo', null, 0], $decorator->getDecoratedService());

        $innerArgument = $decorator->getArgument('$inner');
        self::assertInstanceOf(Reference::class, $innerArgument);
        self::assertSame('doctrine_elastica_test.tracking_object_persister.foo.inner', (string) $innerArgument);

        self::assertSame('foo', $decorator->getArgument('$indexName'));
    }
}
