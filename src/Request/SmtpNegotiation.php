<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Request;

use Camelot\SmtpDevServer\Exception\SmtpNegotiationException;
use Stringable;

final class SmtpNegotiation
{
    private ?string $mailFrom = null;
    private array $rcptTo = [];
    private bool $data = false;
    private ?string $content = null;

    public static function create(): self
    {
        return new self();
    }

    public function hasHeaders(): bool
    {
        return $this->mailFrom && $this->rcptTo && $this->data;
    }

    public function mailFrom(string $mailFrom): void
    {
        if ($this->mailFrom !== null) {
            throw new SmtpNegotiationException('MAIL FROM already set.');
        }
        $this->mailFrom = $mailFrom;
    }

    public function rcptTo(string $rcptTo): void
    {
        if ($this->mailFrom === null) {
            throw new SmtpNegotiationException('MAIL FROM must be called before RCPT TO.');
        }
        $this->rcptTo[] = $rcptTo;
    }

    public function data(): void
    {
        if ($this->mailFrom === null) {
            throw new SmtpNegotiationException('MAIL FROM and RCPT TO must be sent first.');
        }
        if (\count($this->rcptTo) === 0) {
            throw new SmtpNegotiationException('RCPT TO must be called at least once before DATA.');
        }

        $this->data = true;
    }

    public function addContent(null|string|Stringable $buffer): void
    {
        if ($this->data === false) {
            throw new SmtpNegotiationException('Message data can not be sent before before DATA.');
        }
        $this->content .= $buffer;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function reset(): void
    {
        $this->mailFrom = null;
        $this->rcptTo = [];
        $this->data = false;
        $this->content = null;
    }
}
