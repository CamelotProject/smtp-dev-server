<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Request;

use Symfony\Component\HttpFoundation\Request;

final class HttpRequest extends Request implements RequestInterface
{
    public function setContent(string|null $content): self
    {
        $this->content = $content;

        return $this;
    }
}
