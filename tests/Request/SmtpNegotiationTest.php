<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Tests\Request;

use Camelot\SmtpDevServer\Exception\SmtpNegotiationException;
use Camelot\SmtpDevServer\Request\SmtpNegotiation;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Camelot\SmtpDevServer\Request\SmtpNegotiation
 *
 * @internal
 */
final class SmtpNegotiationTest extends TestCase
{
    public function testMailFrom(): void
    {
        SmtpNegotiation::create()->mailFrom('me@here.com');
        $this->addToAssertionCount(1);
    }

    public function testMailFromFailsOnMultipleCalls(): void
    {
        $this->expectException(SmtpNegotiationException::class);
        $this->expectExceptionMessage('MAIL FROM already set.');

        $negotiation = SmtpNegotiation::create();
        $negotiation->mailFrom('me@here.com');
        $negotiation->mailFrom('me@here.com');
    }

    public function testRcptTo(): void
    {
        $negotiation = SmtpNegotiation::create();
        $negotiation->mailFrom('me@here.com');
        $negotiation->rcptTo('you@there.com');
        $this->addToAssertionCount(1);
    }

    public function testRcptToFailsIfCalledBeforeMailFrom(): void
    {
        $this->expectException(SmtpNegotiationException::class);
        $this->expectExceptionMessage('MAIL FROM must be called before RCPT TO.');

        SmtpNegotiation::create()->rcptTo('you@there.com');
    }

    public function testData(): void
    {
        $negotiation = SmtpNegotiation::create();
        $negotiation->mailFrom('me@here.com');
        $negotiation->rcptTo('you@there.com');
        $negotiation->data();
        $this->addToAssertionCount(1);
    }

    public function testDataFailsIfCalledBeforeMailFrom(): void
    {
        $this->expectException(SmtpNegotiationException::class);
        $this->expectExceptionMessage('MAIL FROM and RCPT TO must be sent first.');

        SmtpNegotiation::create()->data();
    }

    public function testDataFailsIfCalledBeforeRcptTo(): void
    {
        $this->expectException(SmtpNegotiationException::class);
        $this->expectExceptionMessage('RCPT TO must be called at least once before DATA.');

        $negotiation = SmtpNegotiation::create();
        $negotiation->mailFrom('me@here.com');
        $negotiation->data();
    }

    public function testAddContent(): void
    {
        $negotiation = SmtpNegotiation::create();
        $negotiation->mailFrom('me@here.com');
        $negotiation->rcptTo('you@there.com');
        $negotiation->data();
        $negotiation->addContent('something');

        static::assertSame('something', $negotiation->getContent());
    }

    public function testAddContentFailsBeforeData(): void
    {
        $this->expectException(SmtpNegotiationException::class);
        $this->expectExceptionMessage('Message data can not be sent before before DATA.');

        SmtpNegotiation::create()->addContent('something');
    }

    public function testReset(): void
    {
        $negotiation = SmtpNegotiation::create();
        $negotiation->mailFrom('me@here.com');
        $negotiation->rcptTo('you@there.com');
        $negotiation->data();
        $negotiation->addContent('something');
        $negotiation->reset();

        static::assertNull($negotiation->getContent());
    }

    public function providerHasHeaders(): iterable
    {
        yield '' => [false, SmtpNegotiation::create()];
//        yield '' => [];
//        yield '' => [];
//        yield '' => [];
//        yield '' => [];
//        yield '' => [];
//        yield '' => [];
//        yield '' => [];
    }

    /** @dataProvider providerHasHeaders */
    public function testHasHeaders(bool $expected, SmtpNegotiation $negotiation): void
    {
        static::assertSame($expected, $negotiation->hasHeaders());
    }
}
