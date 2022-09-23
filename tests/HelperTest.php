<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Tests;

use Camelot\SmtpDevServer\Helper;
use Camelot\SmtpDevServer\Tests\Fixtures\Fixture;
use PHPUnit\Framework\TestCase;
use Stringable;

/**
 * @covers \Camelot\SmtpDevServer\Helper
 *
 * @internal
 */
final class HelperTest extends TestCase
{
    public function testExtractId(): void
    {
        static::assertSame('ebe6cf5f63163127badb871b833e0e6e@paddestoel.dev', Helper::extractId(Fixture::read('emails/simple.eml')));
    }

    public function providerBufferToLines(): iterable
    {
        yield 'GET' => [
            [
                'GET / HTTP/1.1',
                'Host: 127.0.0.1:2580',
                'Connection: keep-alive',
                'Pragma: no-cache',
                'Cache-Control: no-cache',
                'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/jxl,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'Accept-Encoding: gzip, deflate, br',
                'Accept-Language: en-GB,en;q=0.9,de-DE;q=0.8,de;q=0.7,nl-NL;q=0.6,nl;q=0.5,es-US;q=0.4,es;q=0.3,pt-PT;q=0.2,pt;q=0.1,en-US;q=0.1',
                'Cookie: PHPSESSID=abc123',
                '',
                "\r\n",
            ],
            Fixture::read('packets/get.php'),
        ];
        yield 'GET partial' => [
            [
                'GET / HTTP/1.1',
                'Host: 127.0.0.1:2580',
                'Connection: keep-alive',
                'Pragma: no-cache',
                'Cache-Control: no-cache',
                'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/jxl,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'Accept-Encoding: gzip, deflate, br',
                'Accept-Language: en-GB,en;q=0.9,de-DE;q=0.8,de;q=0.7,nl-NL;q=0.6,nl;q=0.5,es-US;q=0.4,es;q=0.3,pt-PT;q=0.2,pt;q=0.1,en-US;q=0.1',
                'Cookie: PHPSESSID=abc123',
            ],
            trim(Fixture::read('packets/get.php')),
        ];
        yield 'POST' => [
            [
                'POST / HTTP/1.1',
                'Host: 127.0.0.1:2580',
                'Connection: keep-alive',
                'Content-Length: 32',
                'Pragma: no-cache',
                'Cache-Control: no-cache',
                'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36',
                'Origin: http://127.0.0.1:2580',
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/jxl,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'Referer: http://127.0.0.1:2580/',
                'Accept-Encoding: gzip, deflate, br',
                'Accept-Language: en-GB,en;q=0.9,de-DE;q=0.8,de;q=0.7,nl-NL;q=0.6,nl;q=0.5,es-US;q=0.4,es;q=0.3,pt-PT;q=0.2,pt;q=0.1,en-US;q=0.1',
                'Cookie: PHPSESSID=abc123',
                '',
                'flush-older=&flush-older-than=12',
                '',
                "\r\n",
            ],
            Fixture::read('packets/post.php'),
        ];

        yield 'Null' => [[], null];
    }

    /** @dataProvider providerBufferToLines */
    public function testBufferToLines(array $expected, null|string|Stringable $buffer): void
    {
        static::assertSame($expected, Helper::bufferToLines($buffer));
    }
}
