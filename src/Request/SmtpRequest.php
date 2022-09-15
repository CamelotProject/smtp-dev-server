<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Request;

use Symfony\Component\HttpFoundation\ParameterBag;

final class SmtpRequest implements RequestInterface
{
    public ParameterBag $headers;
    /** @var null|resource|string */
    private $content;

    public function __construct(array $headers = [], ?string $content = null)
    {
        $this->headers = new ParameterBag($headers);
        $this->content = $content;
    }

    /**
     * Returns the request body content.
     *
     * @param bool $asResource If true, a resource will be returned
     *
     * @return resource|string
     */
    public function getContent(bool $asResource = false): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }
}
