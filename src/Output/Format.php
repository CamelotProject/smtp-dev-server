<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Output;

use RuntimeException;

final class Format
{
    public static function time(int|float $secs)
    {
        static $timeFormats = [
            [0, '< 1 sec'],
            [1, '1 sec'],
            [2, 'secs', 1],
            [60, '1 min'],
            [120, 'mins', 60],
            [3600, '1 hr'],
            [7200, 'hrs', 3600],
            [86400, '1 day'],
            [172800, 'days', 86400],
        ];

        foreach ($timeFormats as $index => $format) {
            if ($secs >= $format[0]) {
                if ((isset($timeFormats[$index + 1]) && $secs < $timeFormats[$index + 1][0])
                    || $index === \count($timeFormats) - 1
                ) {
                    if (\count($format) === 2) {
                        return $format[1];
                    }

                    return floor($secs / $format[2]) . ' ' . $format[1];
                }
            }
        }
        throw new RuntimeException('Time is backwards!'); // @codeCoverageIgnore
    }

    public static function memory(int $memory): string
    {
        $bytes = number_format((float) $memory);

        if ($memory >= 1024 * 1024 * 1024) {
            return sprintf('%s GiB (%s bytes)', number_format((float) $memory / 1024 / 1024 / 1024, 2), $bytes);
        }

        if ($memory >= 1024 * 1024) {
            return sprintf('%s MiB (%s bytes)', number_format((float) $memory / 1024 / 1024, 2), $bytes);
        }

        if ($memory >= 1024) {
            return sprintf('%s KiB (%s bytes)', number_format((float) $memory / 1024, 2), $bytes);
        }

        return sprintf('%d B', $bytes);
    }
}
