<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Socket;

use Camelot\SmtpDevServer\Exception\ServerRuntimeException;
use Symfony\Component\Stopwatch\StopwatchEvent;

final class Stats
{
    private string $name;
    private float|int $startTime;
    private float|int $endTime;
    private float|int $duration;
    private int $memory;

    public function __construct(string $name, float|int $startTime, float|int $endTime, float|int $duration, int $memory)
    {
        $this->name = $name;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->duration = $duration;
        $this->memory = $memory;
    }

    public static function create(StopwatchEvent $event): self
    {
        if ($event->isStarted()) {
            throw new ServerRuntimeException(sprintf('Event %s was not stopped.', $event->getName()));
        }

        return new self($event->getName(), $event->getStartTime(), $event->getEndTime(), $event->getDuration(), $event->getMemory());
    }

    public function name(): string
    {
        return $this->name;
    }

    public function startTime(): float|int
    {
        return $this->startTime;
    }

    public function endTime(): float|int
    {
        return $this->endTime;
    }

    public function duration(): float|int
    {
        return $this->duration;
    }

    public function memory(): int
    {
        return $this->memory;
    }
}
