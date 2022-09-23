<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Tests\Enum;

use Camelot\SmtpDevServer\Enum\OutputPrefix;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Camelot\SmtpDevServer\Enum\OutputPrefix
 *
 * @internal
 */
final class OutputPrefixTest extends TestCase
{
    public function providerOutputPrefix(): iterable
    {
        yield [OutputPrefix::CONNECT, '<<< [CONNECT   ]'];
        yield [OutputPrefix::READ, '¿¿¿ [READ      ]'];
        yield [OutputPrefix::DISPATCH, '~~~ [DISPATCH  ]'];
        yield [OutputPrefix::WRITE, '??? [WRITE     ]'];
        yield [OutputPrefix::DISCONNECT, '>>> [DISCONNECT]'];
        yield [OutputPrefix::STATISTICS, '::: [STATISTICS]'];
        yield [OutputPrefix::TERMINATE, '*** [TERMINATE ]'];
        yield [OutputPrefix::TEST, '💩💩💩 [TEST      ]'];
    }

    /** @dataProvider providerOutputPrefix */
    public function testString(OutputPrefix $outputPrefix, string $expected): void
    {
        static::assertSame($expected, $outputPrefix->string());
    }
}
