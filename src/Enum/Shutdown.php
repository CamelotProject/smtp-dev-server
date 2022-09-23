<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Enum;

enum Shutdown
{
    case Normal;
    case Immediate;
    case Final;

    public function isNormal(): bool
    {
        return $this === self::Normal;
    }

    public function isImmediate(): bool
    {
        return $this === self::Immediate;
    }

    public function isFinal(): bool
    {
        return $this === self::Final;
    }
}
