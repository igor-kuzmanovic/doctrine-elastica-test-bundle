# DoctrineElasticaTestBundle

DoctrineElasticaTestBundle provides DAMA-style test isolation for Elasticsearch writes made through FOSElasticaBundle persisters.

During PHPUnit runs it records the pre-test Elasticsearch document state for touched IDs and restores that state at the next test boundary.

## Features

- Integrates through a PHPUnit extension, similar in workflow to DAMA.
- Decorates FOSElastica object persisters, no application code changes required.
- Works with insert/update/delete writes done through FOSElastica listeners/persisters.
- Supports strategy switching so you can keep a legacy cleanup flow as fallback.

## Installation

```bash
composer require --dev igor-kuzmanovic/doctrine-elastica-test-bundle
```

Register the bundle in `config/bundles.php` for `test` environment:

```php
DoctrineElasticaTestBundle\DoctrineElasticaTestBundle::class => ['test' => true],
```

Enable the PHPUnit extension in your `phpunit.xml` / `phpunit.dist.xml`:

```xml
<extensions>
    <bootstrap class="DoctrineElasticaTestBundle\PHPUnit\PHPUnitExtension"/>
</extensions>
```

## Strategy Toggle

Set `ELASTICSEARCH_TEST_STRATEGY`:

- `bundle` enables DoctrineElasticaTestBundle rollback mode.
- `legacy` disables bundle rollback mode so legacy cleanup can run.
- any other value disables both bundle rollback and legacy-specific behavior (if your suite supports that mode).

## Caveats

- Rollback only applies to writes that pass through FOSElastica object persisters.
- Out-of-band Elasticsearch writes are not tracked.
- Async/deferred writes executed outside the active test window are not guaranteed to roll back.

## Development

```bash
composer cs
composer phpstan
composer test
composer ci
```
