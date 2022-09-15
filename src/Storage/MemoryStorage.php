<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Storage;

use Camelot\SmtpDevServer\Helper;

/**
 * In memory storage.
 */
final class MemoryStorage implements StorageInterface
{
    private array $messages = [];

    public function all(): iterable
    {
        return $this->messages;
    }

    public function add(string $message): void
    {
        $messageId = Helper::extractId($message);

        $this->messages[$messageId] = $message;
    }

    public function get(string $messageId): string
    {
        return $this->messages[$messageId] ?? throw new \RuntimeException(sprintf('Message with ID "%s" was not found', $messageId));
    }

    public function has(string $messageId): bool
    {
        return (bool) ($this->messages[$messageId] ?? false);
    }

    public function count(): int
    {
        return \count($this->messages);
    }

    public function clear(): void
    {
        $this->messages = [];
    }
}
