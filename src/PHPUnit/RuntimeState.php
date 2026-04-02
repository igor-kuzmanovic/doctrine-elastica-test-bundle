<?php

declare(strict_types=1);

namespace Kuzman\DoctrineElasticaTestBundle\PHPUnit;

use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastica\Document;
use Elastica\Exception\NotFoundException;
use Elastica\Index;

final class RuntimeState
{
    private static bool $enabled = false;

    /**
     * @var array<string, array<string, array<string, mixed>|null>>
     */
    private static array $snapshots = [];

    /**
     * @var array<string, Index>
     */
    private static array $indexes = [];

    private static bool $testActive = false;

    public static function enable(): void
    {
        self::$enabled = true;
    }

    public static function disable(): void
    {
        self::$enabled = false;
        self::reset();
    }

    public static function beginTest(bool $trackMutations = true): void
    {
        if (!self::$enabled) {
            self::reset();

            return;
        }

        self::$testActive = $trackMutations;
    }

    public static function finishTestRun(): void
    {
        try {
            if (!self::$enabled) {
                self::reset();

                return;
            }

            self::rollbackPendingMutations();
            self::$testActive = false;
        } finally {
            self::disable();
        }
    }

    public static function rollbackPendingMutations(): void
    {
        if ([] === self::$snapshots) {
            return;
        }

        $errors = [];

        foreach (self::$snapshots as $indexName => $documents) {
            if (!isset(self::$indexes[$indexName])) {
                continue;
            }

            $index = self::$indexes[$indexName];
            $documentsToRestore = [];
            $identifiersToDelete = [];

            foreach ($documents as $id => $snapshot) {
                $id = (string) $id;

                if (null === $snapshot) {
                    $identifiersToDelete[] = $id;

                    continue;
                }

                $documentsToRestore[] = new Document($id, $snapshot, $index->getName());
            }

            try {
                if ([] !== $documentsToRestore) {
                    $index->addDocuments($documentsToRestore);
                }

                if ([] !== $identifiersToDelete) {
                    $index->getClient()->deleteIds($identifiersToDelete, $index->getName());
                }

                if ([] !== $documentsToRestore || [] !== $identifiersToDelete) {
                    $index->refresh();
                }
            } catch (\Throwable $throwable) {
                $errors[] = \sprintf('[%s] %s', $indexName, $throwable->getMessage());
            }
        }

        self::clearPendingMutations();

        if ([] !== $errors) {
            throw new \RuntimeException("Elasticsearch rollback failed:\n".implode("\n", $errors));
        }
    }

    /**
     * @param iterable<mixed> $identifiers
     */
    public static function captureBeforeMutation(Index $index, string $indexName, iterable $identifiers): void
    {
        if (!self::$enabled || !self::$testActive) {
            return;
        }

        self::$indexes[$indexName] = $index;

        foreach ($identifiers as $identifier) {
            $normalizedIdentifier = self::normalizeIdentifier($identifier);
            if (null === $normalizedIdentifier) {
                continue;
            }

            if (\array_key_exists($normalizedIdentifier, self::$snapshots[$indexName] ?? [])) {
                continue;
            }

            self::$snapshots[$indexName][$normalizedIdentifier] = self::fetchDocumentSnapshot($index, $normalizedIdentifier);
        }
    }

    public static function reset(): void
    {
        self::$testActive = false;
        self::clearPendingMutations();
    }

    public static function normalizeIdentifier(mixed $identifier): ?string
    {
        if (null === $identifier) {
            return null;
        }

        if (\is_int($identifier)) {
            if ($identifier <= 0) {
                return null;
            }

            return (string) $identifier;
        }

        if (\is_string($identifier)) {
            $identifier = trim($identifier);

            return '' === $identifier ? null : $identifier;
        }

        if (\is_float($identifier)) {
            if ($identifier <= 0) {
                return null;
            }

            return (string) (int) $identifier;
        }

        return null;
    }

    private static function clearPendingMutations(): void
    {
        self::$snapshots = [];
        self::$indexes = [];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function fetchDocumentSnapshot(Index $index, string $identifier): ?array
    {
        try {
            $data = $index->getDocument($identifier)->getData();
            if (!\is_array($data)) {
                return null;
            }

            $normalizedData = [];
            foreach ($data as $key => $value) {
                $normalizedData[(string) $key] = $value;
            }

            return $normalizedData;
        } catch (NotFoundException) {
            return null;
        } catch (ClientResponseException $exception) {
            if (404 === $exception->getResponse()->getStatusCode()) {
                return null;
            }

            throw $exception;
        }
    }
}
