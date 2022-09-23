<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Storage;

/**
 * Send messages to /dev/null for safekeeping.
 */
final class NullStorage implements StorageInterface
{
    public function all(): iterable
    {
        return [];
    }

    public function add(string $message): void
    {
    }

    public function get(string $messageId): string
    {
        throw new \RuntimeException(sprintf('Message with ID "%s" was not found', $messageId));
    }

    public function has(string $messageId): bool
    {
        return false;
    }

    public function count(): int
    {
        return 0;
    }

    public function clear(): void
    {
    }
}
