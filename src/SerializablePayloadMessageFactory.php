<?php

declare(strict_types=1);

namespace Jphooiveld\ProophMessageRepository;

use DateTimeImmutable;
use DateTimeZone;
use EventSauce\EventSourcing\Serialization\SerializablePayload;
use Exception;
use Prooph\Common\Messaging\Message;
use Prooph\Common\Messaging\MessageFactory;
use Ramsey\Uuid\Uuid;
use UnexpectedValueException;

final class SerializablePayloadMessageFactory implements MessageFactory
{
    /**
     * @inheritDoc
     * @throws Exception
     */
    public function createMessageFromArray(string $messageName, array $messageData): Message
    {
        if (!class_exists($messageName)) {
            throw new UnexpectedValueException('Given message name is not a valid class: ' . $messageName);
        }

        if (!is_subclass_of($messageName, SerializablePayload::class)) {
            throw new UnexpectedValueException(sprintf('Message class %s is not a sub class of %s', $messageName, SerializablePayload::class));
        }

        if (!isset($messageData['message_name'])) {
            $messageData['message_name'] = $messageName;
        }

        if (!isset($messageData['uuid'])) {
            $messageData['uuid'] = Uuid::uuid4()->toString();
        }

        if (!isset($messageData['created_at'])) {
            $messageData['created_at'] = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        }

        if (!isset($messageData['metadata'])) {
            $messageData['metadata'] = [];
        }

        return TransportEvent::fromArray($messageData);
    }
}