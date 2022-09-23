<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Tests\Socket;

use Camelot\SmtpDevServer\Socket\Buffer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Camelot\SmtpDevServer\Socket\Buffer
 *
 * @internal
 */
final class BufferTest extends TestCase
{
    public function testToString(): void
    {
        $buffer = new Buffer($this->getGenerator());

        static::assertSame('abc', "{$buffer}");
        static::assertSame('abc', "{$buffer}", 'Failed to cast a second time');
    }

    private function getGenerator(): \Generator
    {
        foreach (['a', 'b', 'c'] as $letter) {
            yield $letter;
        }
    }
}
