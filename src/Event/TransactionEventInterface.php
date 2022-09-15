<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Event;

interface TransactionEventInterface
{
    public function getHostname(): string;

    public function getMessage(): \Stringable|string|null;

    public function getResponse(): \Stringable|string|null;

    public function setResponse(\Stringable|string|null $response): self;

    public function stayAlive(): bool;
}
