# DoctrineElasticaTestBundle

DoctrineElasticaTestBundle rolls back Elasticsearch changes made through FOSElastica persisters between PHPUnit tests.

Heavily inspired by https://github.com/dmaicher/doctrine-test-bundle.  

## 🚨 EXPERIMENTAL

- This bundle is experimental and may change without notice.
- Currently works only with the FOSElasticaBundle fork at https://github.com/igor-kuzmanovic/FOSElasticaBundle.

## Requirements

- PHP 8.2 or higher
- Symfony 6.4, 7.3, or 8.0
- FOSElasticaBundle 7.x

## Installation

```bash
composer require --dev kuzman/doctrine-elastica-test-bundle
```

Register the bundle for the test environment in `config/bundles.php`:

```php
return [
    // ...
    Kuzman\DoctrineElasticaTestBundle\KuzmanDoctrineElasticaTestBundle::class => ['test' => true],
];
```

Add the PHPUnit extension to `phpunit.xml.dist`:

```xml
<extensions>
    <bootstrap class="Kuzman\DoctrineElasticaTestBundle\PHPUnit\PHPUnitExtension"/>
</extensions>
```

## Usage

Once installed, the bundle activates automatically when your PHPUnit suite loads the extension.

### Skipping rollback for specific tests

Use `Kuzman\DoctrineElasticaTestBundle\PHPUnit\SkipElasticsearchRollback` when you need to bypass rollback handling for a specific test class or method.

```php
use Kuzman\DoctrineElasticaTestBundle\PHPUnit\SkipElasticsearchRollback;

#[SkipElasticsearchRollback] // skip for all tests in this class
final class MyTest extends \PHPUnit\Framework\TestCase
{
}

final class AnotherTest extends \PHPUnit\Framework\TestCase
{
    #[SkipElasticsearchRollback] // skip only this test method
    public function testSomething(): void
    {
    }
}
```

## Caveats

- Only writes that go through FOSElastica persisters are tracked.
- Out-of-band Elasticsearch writes are not rolled back.
- Rollback works only when tests are executed by PHPUnit with this extension enabled in phpunit.xml.dist.

## Development

```bash
vendor/bin/php-cs-fixer fix --dry-run --diff
vendor/bin/phpstan analyse
vendor/bin/phpunit
```
