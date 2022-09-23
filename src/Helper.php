<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer;

use PhpMimeMailParser\Parser;

final class Helper
{
    private function __construct()
    {
    }

    public static function extractId(string $message): string
    {
        return trim((new Parser())->setText($message)->getHeader('message-id'), '<>');
    }

    public static function bufferToLines(null|string|\Stringable $buffer): array
    {
        $crlf = "\r\n";
        if ($buffer === null) {
            return [];
        }

        $buffer = "{$buffer}";
        if (preg_match('#(?<lines>\X+)\r\n$#u', $buffer, $matches)) {
            $lines = explode($crlf, $matches['lines']);
            $lines[] = $crlf;
        } else {
            $lines = explode($crlf, $buffer);
        }

        return $lines;
    }
}
