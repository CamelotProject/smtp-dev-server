<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Storage;

interface StorageInterface
{
    /**
     * Return all messages in storage.
     *
     * @return string[]
     */
    public function all(): iterable;

    /** Add a message to the store. */
    public function add(string $message): void;

    /**
     * Get a message from teh store.
     *
     * @throws \RuntimeException
     */
    public function get(string $messageId): string;

    /** Test if message exists in storage. */
    public function has(string $messageId): bool;

    /** Number of messages in storage. */
    public function count(): int;

    /** Clear storage. */
    public function clear(): void;
}
