<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer;

use PhpMimeMailParser\Parser;

use function trim;

final class Helper
{
    public static function extractId(string $message): string
    {
        return trim((new Parser())->setText($message)->getHeader('message-id'), '<>');
    }
}
