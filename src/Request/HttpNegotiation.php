<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Request;

use Camelot\SmtpDevServer\Enum\HttpMethod;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\InputBag;

final class HttpNegotiation
{
    public HeaderBag $headers;
    public InputBag $cookies;
    public InputBag $request;
    private ?HttpMethod $method = null;
    private ?string $path = null;
    private ?string $protocol = null;
    private ?string $body = null;
    private bool $headersComplete = false;
    private bool $bodyComplete = false;

    public function __construct()
    {
        $this->cookies = new InputBag();
        $this->headers = new HeaderBag();
        $this->request = new InputBag();
    }

    public function protocol(): ?string
    {
        return $this->protocol;
    }

    public function setProtocol(?string $protocol): self
    {
        $this->protocol = $protocol;

        return $this;
    }

    public function method(): ?HttpMethod
    {
        return $this->method;
    }

    public function setMethod(HttpMethod $method): self
    {
        $this->method = $method;

        return $this;
    }

    public function path(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): void
    {
        $this->path = $path;
    }

    public function body(): ?string
    {
        return $this->body;
    }

    public function appendBody(?string $body): void
    {
        if ($body !== null) {
            $this->body .= $body;
        }
    }

    public function headersComplete(): bool
    {
        return $this->headersComplete;
    }

    public function setHeadersComplete(): void
    {
        $this->headersComplete = true;
        if (!$this->method->expectsBody()) {
            $this->bodyComplete = true;
        }
    }

    public function bodyComplete(): bool
    {
        return $this->bodyComplete;
    }

    public function setBodyComplete(): void
    {
        $this->bodyComplete = true;
    }

    public function complete(): bool
    {
        return $this->headersComplete && $this->bodyComplete;
    }
}
