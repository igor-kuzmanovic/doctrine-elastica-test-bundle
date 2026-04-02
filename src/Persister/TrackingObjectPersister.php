<?php

declare(strict_types=1);

namespace DoctrineElasticaTestBundle\Persister;

use DoctrineElasticaTestBundle\PHPUnit\RuntimeState;
use Elastica\Document;
use Elastica\Index;
use FOS\ElasticaBundle\Persister\ObjectPersisterInterface;

/**
 * @template TObject of object
 *
 * @implements ObjectPersisterInterface<TObject>
 */
final class TrackingObjectPersister implements ObjectPersisterInterface
{
    /**
     * @param ObjectPersisterInterface<TObject> $inner
     */
    public function __construct(
        private readonly ObjectPersisterInterface $inner,
        private readonly Index $index,
        private readonly string $indexName,
    ) {
    }

    /**
     * @template T of object
     *
     * @param T $object
     *
     * @return (T is TObject ? true : false)
     */
    public function handlesObject(object $object): bool
    {
        return $this->inner->handlesObject($object);
    }

    /**
     * @param TObject $object
     */
    public function insertOne(object $object): void
    {
        $this->recordObjects([$object]);
        $this->inner->insertOne($object);
    }

    /**
     * @param TObject $object
     */
    public function replaceOne(object $object): void
    {
        $this->recordObjects([$object]);
        $this->inner->replaceOne($object);
    }

    /**
     * @param TObject $object
     */
    public function deleteOne(object $object): void
    {
        $this->recordObjects([$object]);
        $this->inner->deleteOne($object);
    }

    public function deleteById(string $id, string|bool $routing = false): void
    {
        RuntimeState::captureBeforeMutation($this->index, $this->indexName, [$id]);
        $this->inner->deleteById($id, $routing);
    }

    /**
     * @param list<TObject> $objects
     */
    public function insertMany(array $objects): void
    {
        $this->recordObjects($objects);
        $this->inner->insertMany($objects);
    }

    /**
     * @param list<TObject> $objects
     */
    public function replaceMany(array $objects): void
    {
        $this->recordObjects($objects);
        $this->inner->replaceMany($objects);
    }

    /**
     * @param list<TObject> $objects
     */
    public function deleteMany(array $objects): void
    {
        $this->recordObjects($objects);
        $this->inner->deleteMany($objects);
    }

    /**
     * @param list<string> $identifiers
     */
    public function deleteManyByIdentifiers(array $identifiers, string|bool $routing = false): void
    {
        RuntimeState::captureBeforeMutation($this->index, $this->indexName, $identifiers);
        $this->inner->deleteManyByIdentifiers($identifiers, $routing);
    }

    /**
     * @param list<TObject> $objects
     */
    private function recordObjects(array $objects): void
    {
        $identifiers = [];

        foreach ($objects as $object) {
            $identifier = $this->resolveIdentifier($object);
            if (null === $identifier) {
                continue;
            }

            $identifiers[] = $identifier;
        }

        if ([] === $identifiers) {
            return;
        }

        RuntimeState::captureBeforeMutation($this->index, $this->indexName, $identifiers);
    }

    private function resolveIdentifier(object $object): ?string
    {
        if (is_callable([$this->inner, 'transformToElasticaDocument'])) {
            $document = $this->inner->transformToElasticaDocument($object);
            if (!$document instanceof Document) {
                return null;
            }

            return RuntimeState::normalizeIdentifier($document->getId());
        }

        if (method_exists($object, 'getId')) {
            /** @var mixed $id */
            $id = $object->getId();

            return RuntimeState::normalizeIdentifier($id);
        }

        return null;
    }
}
