<?php

declare(strict_types=1);

namespace Jphooiveld\ProophMessageRepository\Tests;

use DateTimeImmutable;
use Exception;
use Jphooiveld\ProophMessageRepository\SerializablePayloadMessageFactory;
use Jphooiveld\ProophMessageRepository\TransportEvent;
use PHPUnit\Framework\TestCase;
use Prooph\Common\Messaging\Message;
use Ramsey\Uuid\Uuid;
use stdClass;
use UnexpectedValueException;

final class EventSauceMessageFactoryTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function test_invalid_type(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Given message name is not a valid class: Foo');

        $factory = new SerializablePayloadMessageFactory();
        $factory->createMessageFromArray('Foo', []);
    }

    /**
     * @throws Exception
     */
    public function test_invalid_class(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Message class stdClass is not a sub class of EventSauce\EventSourcing\Serialization\SerializablePayload');

        $factory = new SerializablePayloadMessageFactory();
        $factory->createMessageFromArray(stdClass::class, []);
    }

    /**
     * @throws Exception
     */
    public function test_default_values(): void
    {
        $factory = new SerializablePayloadMessageFactory();
        $event   = $factory->createMessageFromArray(TestEvent::class, ['payload' => ['foo']]);

        $this->assertInstanceOf(TransportEvent::class, $event);
        self::assertSame(Message::TYPE_EVENT, $event->messageType());
        self::assertSame([], $event->metadata());
        self::assertSame(['foo'], $event->payload());
    }

    /**
     * @throws Exception
     */
    public function test_given_values(): void
    {
        $messageData = [
            'message_name' => 'foo',
            'uuid'         => Uuid::uuid4()->toString(),
            'created_at'   => new DateTimeImmutable(),
            'metadata'     => ['bar' => 'baz'],
            'payload'      => ['qux'],
        ];

        $factory = new SerializablePayloadMessageFactory();
        $event   = $factory->createMessageFromArray(TestEvent::class, $messageData);

        self::assertSame($messageData['message_name'], $event->messageName());
        self::assertSame($messageData['uuid'], $event->uuid()->toString());
        self::assertSame($messageData['created_at'], $event->createdAt());
        self::assertSame($messageData['metadata'], $event->metadata());
        self::assertSame($messageData['payload'], $event->payload());
    }
}