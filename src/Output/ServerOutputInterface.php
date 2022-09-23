<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Output;

use Camelot\SmtpDevServer\Enum\Shutdown;
use Camelot\SmtpDevServer\Enum\SocketAction;
use Camelot\SmtpDevServer\Event\SocketEvent;
use Camelot\SmtpDevServer\Socket\AddressInfo;
use Camelot\SmtpDevServer\Socket\Connection;
use Camelot\SmtpDevServer\Socket\Connections;
use Camelot\SmtpDevServer\Socket\Listener;
use Camelot\SmtpDevServer\Socket\SocketInterface;
use Stringable;
use Throwable;

interface ServerOutputInterface
{
    /** Record server start-up. */
    public function startup(AddressInfo $addressInfo): void;

    /** Record server start-up failure. */
    public function startupFailed(Throwable $e): void;

    /** Record server is actively listening for remote connections. */
    public function listening(AddressInfo $addressInfo, int|string $listenerId): void;

    /** Record server shutdown initiation. */
    public function shutdown(AddressInfo $addressInfo, Shutdown $shutdown): void;

    /** Record server shutdown completion. */
    public function shutdownFinal(AddressInfo $addressInfo, Listener $listener, Connections $connections, Shutdown $shutdown): void;

    /** Record a remote connection. */
    public function connection(Connection $connection): void;

    /** Record a dispatch. */
    public function dispatch(SocketEvent $event, SocketAction $action): void;

    /** Record a completed/failed dispatch. */
    public function dispatched(Throwable $e = null): void;

    /** Record a remote request. */
    public function request(string|Stringable $request, SocketEvent $event): void;

    /** Record a response to a remote connection. */
    public function response(string|Stringable $response, int $bytesWritten, SocketEvent $event): void;

    /** Record a remote disconnection. */
    public function disconnection(SocketInterface $socket): void;

    /** Record a remote connection termination. */
    public function termination(Connection $socket): void;

    /** Record a remote transaction stats. */
    public function stats(SocketInterface $socket): void;

    /** Record status of current/total connections. */
    public function status(Listener $listener, Connections $connections): void;

    /** Emit details from an exception. */
    public function exception(Throwable $e): void;

    /** Always emit messages. */
    public function alert(string|Stringable|iterable $message): void;

    /** Emit messages when *not* running in quiet mode (-q). */
    public function warning(string|Stringable|iterable $message): void;

    /** Emit messages in verbose mode (-v). */
    public function notice(string|Stringable|iterable $message): void;

    /** Emit messages in very-verbose mode (-vv). */
    public function info(string|Stringable|iterable $message): void;

    /** Emit messages only in debug mode (-vvv). */
    public function debug(string|Stringable|iterable $message): void;
}
