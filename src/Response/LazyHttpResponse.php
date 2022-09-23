<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Response;

use Symfony\Component\HttpFoundation\Response;

final class LazyHttpResponse extends Response
{
    /** @var callable */
    private $callback;

    public function __construct(callable $callback, ?string $content = '', int $status = 200, array $headers = [])
    {
        parent::__construct($content, $status, $headers);
        $this->callback = $callback;
    }

    public function __toString(): string
    {
        ($this->callback)($this);

        return
            sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText) . "\r\n" .
            $this->headers . "\r\n" .
            $this->getContent()
        ;
    }

    public static function create(callable $callback): self
    {
        return new self($callback);
    }

    public function sendContent(): static
    {
        ($this->callback)($this);

        return parent::sendContent();
    }
}
