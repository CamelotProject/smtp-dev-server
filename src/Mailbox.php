<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer;

use PhpMimeMailParser\Parser;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use function file_get_contents;
use const PHP_EOL;

final class Mailbox
{
    private string $spoolDir;
    private Parser $parser;

    public function __construct(string $spoolDir)
    {
        $this->spoolDir = $spoolDir;
        $this->parser = new Parser();
    }

    public function all(): array
    {
        $emails = [];
        $files = Finder::create()
            ->files()
            ->in($this->spoolDir)
            ->name('*.eml')
        ;

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            $id = $file->getFilenameWithoutExtension();
            $emails[$id] = $this->get($id);
        }

        return $emails;
    }

    public function get(string $id): array
    {
        $this->parser->setPath(Path::join($this->spoolDir, "{$id}.eml"));

        return [
            'headers' => $this->parser->getHeaders(),
            'text' => $this->parser->getMessageBody('text'),
            'html' => $this->parser->getMessageBody('html'),
        ];
    }

    public function read(string $id): string
    {
        return file_get_contents(Path::join($this->spoolDir, "{$id}.eml"));
    }

    public function delete(string $id): void
    {
        $fs = new Filesystem();
        $fs->remove(Path::join($this->spoolDir, "{$id}.eml"));
    }
}
