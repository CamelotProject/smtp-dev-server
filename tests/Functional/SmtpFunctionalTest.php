<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Tests\Functional;

use const SIGINT;

use Camelot\SmtpDevServer\Tests\Fixtures\Fixture;
use Camelot\SmtpDevServer\Tests\Fixtures\MockServer;
use Camelot\SmtpDevServer\Tests\Fixtures\MockTransport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Process\Process;

/**
 * @internal
 *
 * @group functional
 *
 * @coversNothing
 */
final class SmtpFunctionalTest extends FunctionalTestCase
{
    private TransportInterface $transport;
    private Address $from;
    private Address $to;
    private iterable $cc;

    protected function setUp(): void
    {
        $this->from = new Address('sonja@paddestoel.dev', 'Sonja Squirrel');
        $this->to = new Address('adèle@heggen.dev', 'Adèle van de Leen');
        $this->cc[] = new Address('bebee@heggen.dev', 'Bebbe Klever');
        $this->cc[] = new Address('matthijs@heggen.dev', 'Matthijs Slim');
        $this->cc[] = new Address('zoë@heggen.dev', 'Zoë Slim');
        $this->transport = new MockTransport();
    }

    public function providerBasicEmail(): iterable
    {
        yield 'First message' => [];
        yield 'Second message' => [];
    }

    /** @dataProvider providerBasicEmail */
    public function testBasicEmail(): void
    {
        $email = $this->getBaseEmail()
            ->text(Fixture::read('emails/simple.txt'))
        ;

        $this->transport->send($email);

        static::assertCount(1, $this->transport->getSentMessages());
    }

    public function testAttachmentEmail(): void
    {
        $base64 = 'data:image/png;base64,' . base64_encode(Fixture::read('attachments/dummy.png'));
        $html = str_replace('%%IMAGE%%', $base64, Fixture::read('emails/simple.html'));

        $email = $this->getBaseEmail()
            ->html($html)
            ->text(Fixture::read('emails/simple.txt'))
            ->attachFromPath(Fixture::path('attachments/dummy.pdf'), 'First draft.pdf')
            ->attachFromPath(Fixture::path('attachments/dummy.pdf'), 'Second draft.pdf')
        ;

        $this->transport->send($email);

        static::assertCount(1, $this->transport->getSentMessages());
    }

    private function getBaseEmail(): Email
    {
        return (new Email())
            ->from($this->from)
            ->to($this->to)
            ->cc(...$this->cc)
            ->subject('Are there any seeds left?')
        ;
    }
}
