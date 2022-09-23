<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Tests\Twig;

use Camelot\SmtpDevServer\Twig\TwigExtension;
use PhpMimeMailParser\Attachment;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

/**
 * @covers \Camelot\SmtpDevServer\Twig\TwigExtension
 *
 * @internal
 */
final class TwigExtensionTest extends TestCase
{
    public function testGetFunctions(): void
    {
        $functions = (new TwigExtension())->getFunctions();

        static::assertCount(1, $functions);
        static::assertInstanceOf(TwigFunction::class, $functions[0]);
        static::assertSame('attachment_icon', $functions[0]->getName());
    }

    public function providerAttachments(): iterable
    {
        yield ['audio', new Attachment('file.ext', 'audio/aac', null)];
        yield ['audio', new Attachment('file.ext', 'audio/ogg', null)];
        yield ['audio', new Attachment('file.ext', 'audio/midi', null)];
        yield ['audio', new Attachment('file.ext', 'audio/x-midi', null)];
        yield ['audio', new Attachment('file.ext', 'audio/mpeg', null)];
        yield ['audio', new Attachment('file.ext', 'audio/wav', null)];
        yield ['audio', new Attachment('file.ext', 'audio/webm', null)];
        yield ['audio', new Attachment('file.ext', 'audio/3gpp', null)];
        yield ['audio', new Attachment('file.ext', 'audio/3gpp2', null)];

        yield ['image', new Attachment('file.ext', 'image/png', null)];
        yield ['image', new Attachment('file.ext', 'image/avif', null)];
        yield ['image', new Attachment('file.ext', 'image/bmp', null)];
        yield ['image', new Attachment('file.ext', 'image/gif', null)];
        yield ['image', new Attachment('file.ext', 'image/vnd.microsoft.icon', null)];
        yield ['image', new Attachment('file.ext', 'image/jpeg', null)];
        yield ['image', new Attachment('file.ext', 'image/svg+xml', null)];
        yield ['image', new Attachment('file.ext', 'image/tiff', null)];
        yield ['image', new Attachment('file.ext', 'image/webp', null)];

        yield ['pdf', new Attachment('file.ext', 'application/pdf', null)];
        yield ['excel', new Attachment('file.ext', 'application/vnd.ms-excel', null)];
        yield ['excel', new Attachment('file.ext', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null)];
        yield ['powerpoint', new Attachment('file.ext', 'application/vnd.ms-powerpoint', null)];
        yield ['powerpoint', new Attachment('file.ext', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', null)];
        yield ['word', new Attachment('file.ext', 'application/msword', null)];
        yield ['word', new Attachment('file.ext', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null)];

        yield ['lines', new Attachment('file.ext', 'foo/bar', null)];

        yield ['zip', new Attachment('file.ext', 'application/gzip', null)];
        yield ['zip', new Attachment('file.ext', 'application/java-archive', null)];
        yield ['zip', new Attachment('file.ext', 'application/x-tar', null)];
        yield ['zip', new Attachment('file.ext', 'application/x-bzip', null)];
        yield ['zip', new Attachment('file.ext', 'application/x-bzip2', null)];
        yield ['zip', new Attachment('file.ext', 'application/x-freearc', null)];
        yield ['zip', new Attachment('file.ext', 'application/vnd.rar', null)];

        yield ['video', new Attachment('file.ext', 'video/ogg', null)];
        yield ['video', new Attachment('file.ext', 'video/mp4', null)];
        yield ['video', new Attachment('file.ext', 'video/x-msvideo', null)];
        yield ['video', new Attachment('file.ext', 'video/mpeg', null)];
        yield ['video', new Attachment('file.ext', 'video/mp2t', null)];
        yield ['video', new Attachment('file.ext', 'video/webm', null)];
        yield ['video', new Attachment('file.ext', 'video/3gpp', null)];
        yield ['video', new Attachment('file.ext', 'video/3gpp2', null)];
    }

    /** @dataProvider providerAttachments */
    public function testGetAttachmentIcon(string $expected, Attachment $attachment): void
    {
        static::assertSame("file-{$expected}.svg", (new TwigExtension())->getAttachmentIcon($attachment));
    }
}
