<?php

declare(strict_types=1);

namespace Jphooiveld\ProophMessageRepository\Tests;

use EventSauce\EventSourcing\Serialization\SerializablePayload;

final class TestEvent implements SerializablePayload
{
    public function toPayload(): array
    {
        return [];
    }

    public static function fromPayload(array $payload): SerializablePayload
    {
        return new self();
    }
}