<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Enum;

enum Scheme: string
{
    case SMTP = 'smtp';
    case HTTP = 'http';
    case TEST = 'test';
}
