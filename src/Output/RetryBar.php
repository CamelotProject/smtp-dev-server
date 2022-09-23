<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Output;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Terminal;

final class RetryBar
{
    private int $wait;
    private StyleInterface $io;
    private ProgressBar $bar;

    public function __construct(int $wait, OutputInterface $output)
    {
        $this->wait = $wait;
        $this->io = $output instanceof StyleInterface ? $output : new SymfonyStyle(new ArgvInput(), $output);

        ProgressBar::setFormatDefinition('custom', "%message% %remaining:6s%\t%bar%");
        ProgressBar::setPlaceholderFormatterDefinition('remaining', fn (ProgressBar $bar) => Helper::formatTime($bar->getMaxSteps() - $bar->getProgress()));
        $this->bar = $this->io->createProgressBar($this->wait);
        $maxWidth = (new Terminal())->getWidth() - 42;
        $displayWidth = Helper::width(' 🌰') * $this->wait;
        $this->bar->setBarWidth(min($displayWidth, $maxWidth));
        $this->bar->setFormat('custom');
        $this->bar->setBarCharacter(' 🐾');
        $this->bar->setEmptyBarCharacter(' 🌰');
        $this->bar->setProgressCharacter(' 🐹');
    }

    public function timeout(int $try): void
    {
        $this->bar->start($this->wait);
        $this->bar->setMessage(" * Socket in use. Retry #{$try} in");

        foreach ($this->bar->iterate(range(1, $this->wait)) as $_) {
            sleep(1);
        }

        usleep(1000000);
        $this->bar->clear();
    }
}
