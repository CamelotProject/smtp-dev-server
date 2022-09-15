<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Request;

interface RequestInterface
{
    public function getContent(bool $asResource = false);

    public function setContent(?string $content): self;
}
