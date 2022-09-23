<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Request;

use Camelot\SmtpDevServer\Event\SocketEvent;
use Camelot\SmtpDevServer\Exception\ServerRuntimeException;

final class SmtpNegotiations implements \Countable, \IteratorAggregate
{
    use NegotiationsTrait;

    public function add(string|SocketEvent $connectionId, SmtpNegotiation $negotiation): void
    {
        $connectionId = $connectionId instanceof SocketEvent ? $connectionId->getConnectionId() : $connectionId;
        $this->negotiations[$connectionId] = $negotiation;
    }

    public function get(string|SocketEvent $connectionId): SmtpNegotiation
    {
        $connectionId = $connectionId instanceof SocketEvent ? $connectionId->getConnectionId() : $connectionId;
        $negotiation = $this->negotiations[$connectionId] ?? null;
        if ($negotiation === null) {
            throw new ServerRuntimeException('connect() has not been dispatched for ' . $connectionId);
        }
        return $this->negotiations[$connectionId];
    }
}
