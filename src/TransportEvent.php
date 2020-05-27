<?php

declare(strict_types=1);

namespace Jphooiveld\ProophMessageRepository;

use Prooph\Common\Messaging\DomainEvent;
use Prooph\Common\Messaging\PayloadTrait;

final class TransportEvent extends DomainEvent
{
    use PayloadTrait;
}