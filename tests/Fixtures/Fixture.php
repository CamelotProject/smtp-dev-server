<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Tests\Fixtures;

use Symfony\Component\Filesystem\Path;

final class Fixture
{
    public static function read(string $filepath): string
    {
        $filepath = Path::join(__DIR__, $filepath);
        $file = new \SplFileInfo($filepath);
        if ($file->getExtension() === 'php') {
            return require $filepath;
        }
        return file_get_contents($filepath);
    }

    public static function path(string $filepath): string
    {
        return Path::join(__DIR__, $filepath);
    }
}
