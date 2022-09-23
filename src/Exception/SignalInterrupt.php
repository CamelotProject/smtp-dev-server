<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Exception;

use Camelot\SmtpDevServer\Enum\Signal;

final class SignalInterrupt extends \Exception
{
    private int $hasNext;

    public function __construct(Signal $signal, int $hasNext, ?self $previous = null)
    {
        parent::__construct(sprintf('Caught %s signal.', $signal->name), $signal->value, $previous);
        $this->hasNext = $hasNext;
    }

    public function hasNext(): int
    {
        return $this->hasNext;
    }
}
