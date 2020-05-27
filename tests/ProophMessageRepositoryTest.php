<?php

declare(strict_types=1);

namespace Jphooiveld\ProophMessageRepository\Tests;

use ArrayIterator;
use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\EventSourcing\Time\TestClock;
use EventSauce\EventSourcing\UuidAggregateRootId;
use Exception;
use Jphooiveld\ProophMessageRepository\ProophMessageRepository;
use PHPUnit\Framework\TestCase;
use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use Ramsey\Uuid\Uuid;

final class ProophMessageRepositoryTest extends TestCase
{
    /**
     * @var string
     */
    private const STREAM_NAME = 'test';

    /**
     * @var ProophMessageRepository
     */
    private $repository;

    /**
     * @var DefaultHeadersDecorator
     */
    private $decorator;

    protected function setUp(): void
    {
        parent::setUp();

        $eventStore = new InMemoryEventStore();
        $streamName = new StreamName(self::STREAM_NAME);
        $stream     = new Stream($streamName, new ArrayIterator());

        $eventStore->create($stream);

        $serializer       = new ConstructingMessageSerializer();
        $clock            = new TestClock();
        $this->decorator  = new DefaultHeadersDecorator(null, $clock);
        $this->repository = new ProophMessageRepository($eventStore, $serializer, self::STREAM_NAME);
    }

    /**
     * @throws Exception
     */
    public function test_it_works(): void
    {
        $aggregateRootId = UuidAggregateRootId::create();
        $eventId         = Uuid::uuid4()->toString();

        $this->repository->persist();

        $this->assertEmpty(iterator_to_array($this->repository->retrieveAll($aggregateRootId)));

        $message = $this->decorator->decorate(new Message(new TestEvent(), [
            Header::EVENT_ID          => $eventId,
            Header::AGGREGATE_ROOT_ID => $aggregateRootId->toString(),
            Header::AGGREGATE_ROOT_VERSION => 10,
        ]));

        $this->repository->persist($message);

        $generator        = $this->repository->retrieveAll($aggregateRootId);
        $retrievedMessage = iterator_to_array($generator, false)[0];

        $this->assertEquals($message, $retrievedMessage);
        $this->assertEquals(10, $generator->getReturn());
    }

    /**
     * @throws Exception
     */
    public function test_persisting_events_without_event_ids(): void
    {
        $message = $this->decorator->decorate(new Message(
            new TestEvent(),
            [Header::AGGREGATE_ROOT_ID => Uuid::uuid4()->toString()]
        ));

        $this->repository->persist($message);

        $persistedMessages = iterator_to_array($this->repository->retrieveEverything());

        $this->assertCount(1, $persistedMessages);
        $this->assertNotEquals($message, $persistedMessages[0]);
    }

    /**
     * @throws Exception
     */
    public function test_retrieving_messages_after_a_specific_version(): void
    {
        $aggregateRootId = UuidAggregateRootId::create();
        $messages        = [];

        $messages[] = $this->decorator->decorate(new Message(new TestEvent(), [
            Header::EVENT_ID               => Uuid::uuid4()->toString(),
            Header::AGGREGATE_ROOT_ID      => $aggregateRootId->toString(),
            Header::AGGREGATE_ROOT_VERSION => 10,
        ]));

        $messages[] = $this->decorator->decorate(new Message(new TestEvent(), [
            Header::EVENT_ID               => $lastEventId = Uuid::uuid4()->toString(),
            Header::AGGREGATE_ROOT_ID      => $aggregateRootId->toString(),
            Header::AGGREGATE_ROOT_VERSION => 11,
        ]));

        $this->repository->persist(...$messages);

        $generator = $this->repository->retrieveAllAfterVersion($aggregateRootId, 10);

        /** @var Message[] $messages */
        $messages = iterator_to_array($generator);

        $this->assertEquals(11, $generator->getReturn());
        $this->assertCount(1, $messages);
        $this->assertEquals($lastEventId, $messages[0]->header(Header::EVENT_ID));
    }
}