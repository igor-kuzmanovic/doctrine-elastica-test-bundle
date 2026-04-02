<?php

declare(strict_types=1);

namespace Kuzman\DoctrineElasticaTestBundle\PHPUnit;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class SkipElasticsearchRollback
{
}
