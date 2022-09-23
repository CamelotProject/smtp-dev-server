<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Exception;

use RuntimeException;

/**
 * Exception thrown for fatal server error that require a full shutdown.
 *
 * Should only be caught in public entry methods and result in a termination of PHP execution.
 */
final class ServerFatalRuntimeException extends RuntimeException implements InternalExceptionInterface
{
}
