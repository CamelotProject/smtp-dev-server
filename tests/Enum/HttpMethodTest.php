<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Tests\Enum;

use Camelot\SmtpDevServer\Enum\HttpMethod;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Camelot\SmtpDevServer\Enum\HttpMethod
 *
 * @internal
 */
final class HttpMethodTest extends TestCase
{
    public function providerHttpMethod(): iterable
    {
        yield 'DELETE' => [false, HttpMethod::from('DELETE')];
        yield 'GET' => [false, HttpMethod::from('GET')];
        yield 'HEAD' => [false, HttpMethod::from('HEAD')];
        yield 'PATCH' => [true, HttpMethod::from('PATCH')];
        yield 'POST' => [true, HttpMethod::from('POST')];
        yield 'PUT' => [true, HttpMethod::from('PUT')];
    }

    /** @dataProvider providerHttpMethod */
    public function testExpectsBody(bool $expected, HttpMethod $method): void
    {
        static::assertSame($expected, $method->expectsBody());
    }
}
