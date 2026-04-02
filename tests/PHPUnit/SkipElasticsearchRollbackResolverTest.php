<?php

declare(strict_types=1);

namespace Kuzman\DoctrineElasticaTestBundle\Tests\PHPUnit;

use Kuzman\DoctrineElasticaTestBundle\PHPUnit\SkipElasticsearchRollback;
use Kuzman\DoctrineElasticaTestBundle\PHPUnit\SkipElasticsearchRollbackResolver;
use PHPUnit\Event\TestData\TestDataCollection;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Code\TestDox;
use PHPUnit\Metadata\MetadataCollection;
use PHPUnit\Framework\TestCase;

final class SkipElasticsearchRollbackResolverTest extends TestCase
{
    public function testReturnsTrueWhenClassHasAttribute(): void
    {
        self::assertTrue(SkipElasticsearchRollbackResolver::shouldSkip($this->createTestMethod(TestWithClassAttribute::class, 'testSomething')));
    }

    public function testReturnsTrueWhenMethodHasAttribute(): void
    {
        self::assertTrue(SkipElasticsearchRollbackResolver::shouldSkip($this->createTestMethod(TestWithMethodAttribute::class, 'testSomething')));
    }

    public function testReturnsTrueWhenParentClassHasAttribute(): void
    {
        self::assertTrue(SkipElasticsearchRollbackResolver::shouldSkip($this->createTestMethod(ChildOfAttributedAbstractTest::class, 'testSomething')));
    }

    public function testReturnsFalseWhenNoAttributePresent(): void
    {
        self::assertFalse(SkipElasticsearchRollbackResolver::shouldSkip($this->createTestMethod(TestWithoutAttribute::class, 'testSomething')));
    }

    /**
     * @param class-string     $className
     * @param non-empty-string $methodName
     */
    private function createTestMethod(string $className, string $methodName): TestMethod
    {
        return new TestMethod(
            $className,
            $methodName,
            __FILE__,
            1,
            new TestDox($className, $methodName, $methodName),
            MetadataCollection::fromArray([]),
            TestDataCollection::fromArray([]),
        );
    }
}

#[SkipElasticsearchRollback]
final class TestWithClassAttribute
{
    public function testSomething(): void
    {
    }
}

final class TestWithMethodAttribute
{
    #[SkipElasticsearchRollback]
    public function testSomething(): void
    {
    }
}

#[SkipElasticsearchRollback]
abstract class AttributedAbstractTest
{
}

final class ChildOfAttributedAbstractTest extends AttributedAbstractTest
{
    public function testSomething(): void
    {
    }
}

final class TestWithoutAttribute
{
    public function testSomething(): void
    {
    }
}
