<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Enum;

enum SocketAction
{
    case connect;
    case buffer;
    case disconnect;
}
