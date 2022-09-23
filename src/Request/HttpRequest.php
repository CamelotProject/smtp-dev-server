<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Request;

use Symfony\Component\HttpFoundation\Request;

final class HttpRequest extends Request implements RequestInterface
{
    public static function createFromNegotiation(HttpNegotiation $negotiation): self
    {
        $request = self::create($negotiation->path(), $negotiation->method()->value);
        $request->method = $negotiation->method()->value;
        $request->server->set('SERVER_PROTOCOL', $negotiation->protocol());
        $request->cookies = $negotiation->cookies;
        $request->headers = $negotiation->headers;
        $request->request = $negotiation->request;
        $request->content = $negotiation->body();

        return $request;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }
}
