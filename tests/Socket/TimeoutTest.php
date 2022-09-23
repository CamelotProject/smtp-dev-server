<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Tests\Socket;

use Camelot\SmtpDevServer\Socket\Timeout;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Camelot\SmtpDevServer\Socket\Timeout
 *
 * @internal
 */
final class TimeoutTest extends TestCase
{
    public function providerTimeout(): iterable
    {
        yield [null, 42];
        yield [0, 0];
        yield [24, 42];
    }

    /** @dataProvider providerTimeout */
    public function testGetSeconds(?int $seconds, int $microseconds): void
    {
        static::assertSame($seconds, (new Timeout($seconds, $microseconds))->getSeconds());
    }

    /** @dataProvider providerTimeout */
    public function testGetMicroseconds(?int $seconds, int $microseconds): void
    {
        static::assertSame($microseconds, (new Timeout($seconds, $microseconds))->getMicroseconds());
    }
}
