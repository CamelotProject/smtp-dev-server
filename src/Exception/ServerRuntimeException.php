<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Exception;

use RuntimeException;

/**
 * Exception thrown during connection handling.
 *
 * Generally this should cause the socket to be closed and removed from the connection collection.
 */
final class ServerRuntimeException extends RuntimeException implements InternalExceptionInterface
{
}
