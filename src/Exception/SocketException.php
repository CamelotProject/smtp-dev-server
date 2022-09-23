<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Exception;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when any socket_*() fails.
 *
 * These exceptions should terminate the connection and remove it from the collection.
 *
 * @internal
 */
final class SocketException extends RuntimeException implements InternalExceptionInterface
{
    public function __construct(string $function, null|iterable|\Socket $socket, Throwable $previous = null)
    {
        if (is_iterable($socket)) {
            foreach ($socket as $s) {
                if (socket_last_error($s) > 0) {
                    $socket = $s;
                    break;
                }
                $socket = null;
            }
        }

        $code = socket_last_error($socket);
        $message = sprintf('%s() failed. (%s) %s', $function, $code, socket_strerror($code));
        @socket_clear_error($socket);
        parent::__construct($message, $code, $previous);
    }
}
