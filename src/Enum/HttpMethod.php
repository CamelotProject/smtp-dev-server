<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Enum;

enum HttpMethod: string
{
    case DELETE = 'DELETE';
    case GET = 'GET';
    case HEAD = 'HEAD';
    case PATCH = 'PATCH';
    case POST = 'POST';
    case PUT = 'PUT';

    public function expectsBody(): bool
    {
        return match ($this) {
            self::PATCH,
            self::POST,
            self::PUT => true,
            default => false,
        };
    }
}
