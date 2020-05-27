<?php

declare(strict_types=1);

namespace Jphooiveld\ProophMessageRepository;

use ArrayIterator;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\Serialization\MessageSerializer;
use Exception;
use Generator;
use LogicException;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Metadata\MetadataMatcher;
use Prooph\EventStore\Metadata\Operator;
use Prooph\EventStore\StreamName;
use Prooph\EventStore\TransactionalEventStore;
use Ramsey\Uuid\Uuid;

final class ProophMessageRepository implements MessageRepository
{
    /**
     * @var string
     */
    private const AGGREGATE_ID = '_aggregate_id';

    /**
     * @var string
     */
    private const AGGREGATE_VERSION = '_aggregate_version';

    /**
     * @var string
     */
    private const AGGREGATE_TYPE = '_aggregate_type';

    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * @var MessageSerializer
     */
    private $serializer;

    /**
     * @var string
     */
    private $streamName;

    public function __construct(EventStore $eventStore, MessageSerializer $serializer, string $streamName)
    {
        $this->eventStore = $eventStore;
        $this->serializer = $serializer;
        $this->streamName = $streamName;
    }

    /**
     * @throws Exception
     */
    public function persist(Message ...$messages): void
    {
        if (count($messages) === 0) {
            return;
        }

        $events = new ArrayIterator();

        foreach ($messages as $index => $message) {
            $payload   = $this->serializer->serializeMessage($message);
            $eventType = get_class($message->event());
            $metadata  = [self::AGGREGATE_TYPE => $eventType];

            if (isset($payload['headers'][Header::AGGREGATE_ROOT_VERSION])) {
                $metadata[self::AGGREGATE_ID] = $payload['headers'][Header::AGGREGATE_ROOT_ID];
            }

            if (isset($payload['headers'][Header::AGGREGATE_ROOT_VERSION])) {
                $metadata[self::AGGREGATE_VERSION] = $payload['headers'][Header::AGGREGATE_ROOT_VERSION];
            }

            $data = [
                'uuid'         => $payload['headers'][Header::EVENT_ID] = $payload['headers'][Header::EVENT_ID] ?? Uuid::uuid4()->toString(),
                'message_name' => $eventType,
                'metadata'     => $metadata,
                'created_at'   => $message->timeOfRecording()->dateTime(),
                'payload'      => $payload,
            ];

            $events->append(TransportEvent::fromArray($data));
        }

        $streamName = new StreamName($this->streamName);

        if ($this->eventStore instanceof TransactionalEventStore) {
            $this->eventStore->beginTransaction();
            $this->eventStore->appendTo($streamName, $events);
            $this->eventStore->commit();
            return;
        }

        $this->eventStore->appendTo($streamName, $events);
    }

    public function retrieveAll(AggregateRootId $id): Generator
    {
        $matcher = (new MetadataMatcher())
            ->withMetadataMatch(self::AGGREGATE_ID, Operator::EQUALS(), $id->toString());

        return $this->yieldMessagesForResult($matcher);
    }

    public function retrieveAllAfterVersion(AggregateRootId $id, int $aggregateRootVersion): Generator
    {
        $matcher = (new MetadataMatcher())
            ->withMetadataMatch(self::AGGREGATE_ID, Operator::EQUALS(), $id->toString())
            ->withMetadataMatch(self::AGGREGATE_VERSION, Operator::GREATER_THAN(), $aggregateRootVersion);

        return $this->yieldMessagesForResult($matcher);
    }

    public function retrieveEverything(): Generator
    {
        return $this->yieldMessagesForResult();
    }

    /**
     * @return Generator|int
     */
    private function yieldMessagesForResult(?MetadataMatcher $matcher = null)
    {
        foreach ($this->eventStore->load(new StreamName($this->streamName), 1, null, $matcher) as $event) {
            if (!($event instanceof TransportEvent)) {
                throw new LogicException(sprintf('Event must be instance of %s but is instance of %s', TransportEvent::class, get_class($event)));
            }

            $messages = $this->serializer->unserializePayload($event->payload());

            /* @var Message $message */
            foreach ($messages as $message) {
                yield $message;
            }
        }

        if (isset($message)) {
            return $message->header(Header::AGGREGATE_ROOT_VERSION) ?: 0;
        }

        return 0;
    }
}