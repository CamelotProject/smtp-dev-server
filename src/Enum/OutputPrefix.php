<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Enum;

enum OutputPrefix: string
{
    case CONNECT = '<<<';
    case READ = '¿¿¿';
    case DISPATCH = '~~~';
    case WRITE = '???';
    case DISCONNECT = '>>>';
    case STATISTICS = ':::';
    case TERMINATE = '***';
    case TEST = '💩💩💩';

    public function string(): string
    {
        return sprintf('%s [%s]', $this->value, str_pad($this->name, 10));
    }
}
