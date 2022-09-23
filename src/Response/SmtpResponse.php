<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Response;

use Camelot\SmtpDevServer\Enum\SmtpResponseCode;

final class SmtpResponse implements \Stringable
{
    private SmtpResponseCode $statusCode;
    private array $lines;

    public function __construct(SmtpResponseCode $statusCode, string|array $lines = [])
    {
        $this->statusCode = $statusCode;
        $this->lines = (array) $lines;
    }

    public function __toString(): string
    {
        $response = '';
        $code = $this->statusCode->value;
        $count = \count($this->lines) - 1;
        foreach ($this->lines as $i => $line) {
            $delim = $i === $count ? ' ' : '-';
            $response .= "{$code}{$delim}{$line}\r\n";
        }

        return $response;
    }

    public static function create(SmtpResponseCode $statusCode, string|array $lines = []): self
    {
        return new self($statusCode, $lines);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode->value;
    }

    public function addLine(?string $line): self
    {
        $this->lines[] = $line;

        return $this;
    }

    public function getContent(): ?string
    {
        return (string) $this;
    }
}
