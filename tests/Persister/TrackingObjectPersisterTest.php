<?php

declare(strict_types=1);

namespace DoctrineElasticaTestBundle\Tests\Persister;

use DoctrineElasticaTestBundle\Persister\TrackingObjectPersister;
use DoctrineElasticaTestBundle\PHPUnit\RuntimeState;
use Elastica\Document;
use Elastica\Exception\NotFoundException;
use Elastica\Index;
use FOS\ElasticaBundle\Persister\ObjectPersisterInterface;
use PHPUnit\Framework\TestCase;

final class TrackingObjectPersisterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['ELASTICSEARCH_TEST_STRATEGY'] = 'bundle';
        $_ENV['ELASTICSEARCH_TEST_STRATEGY'] = 'bundle';

        RuntimeState::reset();
        RuntimeState::beginTest();
    }

    protected function tearDown(): void
    {
        RuntimeState::reset();
        unset($_SERVER['ELASTICSEARCH_TEST_STRATEGY'], $_ENV['ELASTICSEARCH_TEST_STRATEGY']);

        parent::tearDown();
    }

    public function testInsertOneCapturesSnapshotAndDelegates(): void
    {
        $inner = new TestObjectPersister();

        $index = $this->createMock(Index::class);
        $index->expects(self::once())
            ->method('getDocument')
            ->with('10')
            ->willThrowException(new NotFoundException('Document not found'));

        $persister = new TrackingObjectPersister($inner, $index, 'test_index');

        $entity = new TestEntity(10);
        $persister->insertOne($entity);

        self::assertSame([$entity], $inner->inserted);
    }

    public function testDeleteManyByIdentifiersCapturesSnapshotAndDelegates(): void
    {
        $inner = new TestObjectPersister();

        $index = $this->createMock(Index::class);
        $index->expects(self::once())
            ->method('getDocument')
            ->with('42')
            ->willThrowException(new NotFoundException('Document not found'));

        $persister = new TrackingObjectPersister($inner, $index, 'test_index');

        $persister->deleteManyByIdentifiers(['42']);

        self::assertSame([['42']], $inner->deletedByIds);
    }
}

final class TestEntity
{
    public function __construct(
        private readonly int $id,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }
}

/**
 * @implements ObjectPersisterInterface<TestEntity>
 */
final class TestObjectPersister implements ObjectPersisterInterface
{
    /** @var list<TestEntity> */
    public array $inserted = [];

    /** @var list<list<string>> */
    public array $deletedByIds = [];

    public function handlesObject(object $object): bool
    {
        return $object instanceof TestEntity;
    }

    public function insertOne(object $object): void
    {
        $this->inserted[] = $object;
    }

    public function replaceOne(object $object): void
    {
    }

    public function deleteOne(object $object): void
    {
    }

    public function deleteById(string $id, string|bool $routing = false): void
    {
        $this->deletedByIds[] = [$id];
    }

    public function insertMany(array $objects): void
    {
    }

    public function replaceMany(array $objects): void
    {
    }

    public function deleteMany(array $objects): void
    {
    }

    public function deleteManyByIdentifiers(array $identifiers, string|bool $routing = false): void
    {
        $this->deletedByIds[] = $identifiers;
    }

    public function transformToElasticaDocument(object $object): Document
    {
        if (!$object instanceof TestEntity) {
            throw new \InvalidArgumentException('Unsupported object type.');
        }

        return new Document((string) $object->getId(), ['id' => $object->getId()]);
    }
}
