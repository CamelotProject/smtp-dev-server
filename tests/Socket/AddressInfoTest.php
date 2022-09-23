<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Tests\Socket;

use Camelot\SmtpDevServer\Enum\Scheme;
use Camelot\SmtpDevServer\Socket\AddressInfo;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Camelot\SmtpDevServer\Socket\AddressInfo
 *
 * @internal
 */
final class AddressInfoTest extends TestCase
{
    public function testFormatted(): void
    {
        $addressInfo = new AddressInfo(Scheme::SMTP, '127.0.0.1', 5252);

        static::assertSame('smtp://127.0.0.1:5252', $addressInfo->formatted());
    }

    public function testGetScheme(): void
    {
        $addressInfo = new AddressInfo(Scheme::SMTP, '127.0.0.1', 5252);

        static::assertSame(Scheme::SMTP, $addressInfo->getScheme());
    }

    public function testGetAddress(): void
    {
        $addressInfo = new AddressInfo(Scheme::SMTP, '127.0.0.1', 5252);

        static::assertSame('127.0.0.1', $addressInfo->getAddress());
    }

    public function testGetPort(): void
    {
        $addressInfo = new AddressInfo(Scheme::SMTP, '127.0.0.1', 5252);

        static::assertSame(5252, $addressInfo->getPort());
    }
}
