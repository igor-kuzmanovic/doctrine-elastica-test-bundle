<?php

declare(strict_types=1);

namespace DoctrineElasticaTestBundle\Tests\PHPUnit;

use DoctrineElasticaTestBundle\PHPUnit\RuntimeState;
use Elastica\Bulk\ResponseSet;
use Elastica\Client;
use Elastica\Document;
use Elastica\Exception\NotFoundException;
use Elastica\Index;
use Elastica\Response;
use PHPUnit\Framework\TestCase;

final class RuntimeStateTest extends TestCase
{
    private string|null $originalServerStrategy = null;
    private string|null $originalEnvStrategy = null;

    protected function setUp(): void
    {
        parent::setUp();

        $serverStrategy = $_SERVER['ELASTICSEARCH_TEST_STRATEGY'] ?? null;
        $envStrategy = $_ENV['ELASTICSEARCH_TEST_STRATEGY'] ?? null;

        $this->originalServerStrategy = \is_string($serverStrategy) ? $serverStrategy : null;
        $this->originalEnvStrategy = \is_string($envStrategy) ? $envStrategy : null;

        RuntimeState::reset();
    }

    protected function tearDown(): void
    {
        RuntimeState::reset();

        $this->restoreStrategy();

        parent::tearDown();
    }

    public function testCaptureBeforeMutationIsIgnoredWhenTestIsNotActive(): void
    {
        $this->setStrategy('bundle');

        $index = $this->createMock(Index::class);
        $index->expects(self::never())->method('getDocument');

        RuntimeState::captureBeforeMutation($index, 'test_index', ['1']);
        RuntimeState::rollbackPendingMutations();
    }

    public function testRollbackRestoresSnapshotsAndDeletesNewDocuments(): void
    {
        $this->setStrategy('bundle');
        RuntimeState::beginTest();

        $client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['deleteIds'])
            ->getMock();

        $client->expects(self::once())
            ->method('deleteIds')
            ->with(['2'], 'test_index')
            ->willReturn($this->createResponseSet());

        $index = $this->createMock(Index::class);
        $index->method('getName')->willReturn('test_index');
        $index->method('getClient')->willReturn($client);

        $index->expects(self::exactly(2))
            ->method('getDocument')
            ->willReturnCallback(static function (string $id): Document {
                if ('1' === $id) {
                    return new Document('1', ['name' => 'before'], 'test_index');
                }

                throw new NotFoundException('Document not found');
            });

        $index->expects(self::once())
            ->method('addDocuments')
            ->with(self::callback(static function (array $documents): bool {
                if (1 !== count($documents)) {
                    return false;
                }

                $document = $documents[0];

                return $document instanceof Document
                    && '1' === $document->getId()
                    && ['name' => 'before'] === $document->getData();
            }))
            ->willReturn($this->createResponseSet());

        $index->expects(self::once())
            ->method('refresh')
            ->willReturn(new Response([], 200));

        RuntimeState::captureBeforeMutation($index, 'test_index', ['1', '2']);
        RuntimeState::rollbackPendingMutations();

        RuntimeState::rollbackPendingMutations();
    }

    public function testCaptureBeforeMutationUsesIdentifierDeduplication(): void
    {
        $this->setStrategy('bundle');
        RuntimeState::beginTest();

        $index = $this->createMock(Index::class);

        $index->expects(self::once())
            ->method('getDocument')
            ->with('5')
            ->willThrowException(new NotFoundException('Document not found'));

        RuntimeState::captureBeforeMutation($index, 'test_index', ['5', '5']);
    }

    public function testNormalizeIdentifier(): void
    {
        self::assertSame('5', RuntimeState::normalizeIdentifier(5));
        self::assertSame('5', RuntimeState::normalizeIdentifier(' 5 '));
        self::assertNull(RuntimeState::normalizeIdentifier(''));
        self::assertNull(RuntimeState::normalizeIdentifier(0));
        self::assertNull(RuntimeState::normalizeIdentifier(-1));
        self::assertNull(RuntimeState::normalizeIdentifier(null));
    }

    private function setStrategy(string $strategy): void
    {
        $_SERVER['ELASTICSEARCH_TEST_STRATEGY'] = $strategy;
        $_ENV['ELASTICSEARCH_TEST_STRATEGY'] = $strategy;
    }

    private function restoreStrategy(): void
    {
        if (null === $this->originalServerStrategy) {
            unset($_SERVER['ELASTICSEARCH_TEST_STRATEGY']);
        } else {
            $_SERVER['ELASTICSEARCH_TEST_STRATEGY'] = $this->originalServerStrategy;
        }

        if (null === $this->originalEnvStrategy) {
            unset($_ENV['ELASTICSEARCH_TEST_STRATEGY']);
        } else {
            $_ENV['ELASTICSEARCH_TEST_STRATEGY'] = $this->originalEnvStrategy;
        }
    }

    private function createResponseSet(): ResponseSet
    {
        return new ResponseSet(new Response([], 200), []);
    }
}
