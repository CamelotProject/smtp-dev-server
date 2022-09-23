<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Output;

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

final class ServerOutputFormatter extends OutputFormatter
{
    public function __construct(OutputFormatterInterface $formatter)
    {
        // black, red, green, yellow, blue, magenta, cyan, white, default, gray,
        // bright-red, bright-green, bright-yellow, bright-blue, bright-magenta, bright-cyan, bright-white
        parent::__construct($formatter->isDecorated(), [
            'bounce' => new OutputFormatterStyle('bright-white', '#ff0000', ['bold']),
            'connect' => new OutputFormatterStyle('green', '#fff', ['bold']),
            'read' => new OutputFormatterStyle('gray', '#fff', []),
            'dispatch' => new OutputFormatterStyle('cyan', '#fff', []),
            'write' => new OutputFormatterStyle('gray', '#fff', []),
            'disconnect' => new OutputFormatterStyle('yellow', '#fff', ['bold']),
            'terminate' => new OutputFormatterStyle('blue', '#fff', ['bold']),
            'statistics' => new OutputFormatterStyle('magenta', '#fff', ['bold']),

            'success' => new OutputFormatterStyle('bright-green', '#fff', []),
            'failure' => new OutputFormatterStyle('bright-red', '#fff', []),
        ]);
    }
}
