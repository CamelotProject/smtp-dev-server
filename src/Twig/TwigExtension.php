<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Twig;

use PhpMimeMailParser\Attachment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class TwigExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('attachment_icon', [$this, 'getAttachmentIcon']),
        ];
    }

    public function getAttachmentIcon(Attachment $attachment): string
    {
        [$family] = explode('/', $attachment->getContentType());

        $match = match ($family) {
            'audio' => 'audio',
            'image' => 'image',
            'video' => 'video',
            default => null,
        };
        if (!$match) {
            $match = match ($attachment->getContentType()) {
                'application/pdf' => 'pdf',

                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'excel',

                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'powerpoint',

                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'word',

                'application/gzip',
                'application/java-archive',
                'application/x-tar',
                'application/x-bzip',
                'application/x-bzip2',
                'application/x-freearc',
                'application/vnd.rar' => 'zip',

                default => 'lines',
            };
        }

        return "file-{$match}.svg";
    }
}
