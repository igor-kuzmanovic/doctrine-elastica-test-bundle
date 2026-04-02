<?php

declare(strict_types=1);

namespace Kuzman\DoctrineElasticaTestBundle\PHPUnit;

use PHPUnit\Event\Code\Test;

final class SkipElasticsearchRollbackResolver
{
    public static function shouldSkip(Test $test): bool
    {
        if (!$test->isTestMethod()) {
            return false;
        }

        return self::classOrParentsHaveAttribute($test->className())
            || self::methodHasAttribute($test->className(), $test->methodName());
    }

    /**
     * @param class-string $className
     */
    private static function classOrParentsHaveAttribute(string $className): bool
    {
        $reflectionClass = new \ReflectionClass($className);

        while (false !== $reflectionClass) {
            if ([] !== $reflectionClass->getAttributes(SkipElasticsearchRollback::class)) {
                return true;
            }

            $reflectionClass = $reflectionClass->getParentClass();
        }

        return false;
    }

    /**
     * @param class-string $className
     */
    private static function methodHasAttribute(string $className, string $methodName): bool
    {
        if (!method_exists($className, $methodName)) {
            return false;
        }

        $reflectionMethod = new \ReflectionMethod($className, $methodName);

        return [] !== $reflectionMethod->getAttributes(SkipElasticsearchRollback::class);
    }
}
