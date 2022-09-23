<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Storage;

use Camelot\SmtpDevServer\Helper;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

final class MailboxStorage implements StorageInterface
{
    private string $baseDir;

    public function __construct(string $projectDir)
    {
        $this->baseDir = Path::join($projectDir, 'var/spool');
        $this->init();
    }

    public function all(): iterable
    {
        yield from $this->getFiles()->getIterator();
    }

    public function add(string $message): void
    {
        $messageId = Helper::extractId($message);

        (new Filesystem())->dumpFile(Path::join($this->baseDir, "{$messageId}.eml"), $message);
    }

     public function get(string $messageId): string
     {
         foreach ($this->getFiles($messageId) as $file) {
             return file_get_contents((string) $file);
         }

         throw new FileNotFoundException(null, 0, null, "{$messageId}.eml");
     }

    public function has(string $messageId): bool
    {
        return (bool) \count($this->getFiles($messageId)->getIterator());
    }

    public function count(): int
    {
        return \count($this->getFiles()->getIterator());
    }

    public function clear(): void
    {
        (new Filesystem())->remove($this->getFiles());
    }

     private function init(): void
     {
         $fs = new Filesystem();
         if (!$fs->exists($this->baseDir)) {
             $fs->mkdir($this->baseDir);
         }
     }

     /** @return Finder */
     private function getFiles(string $messageId = null): iterable
     {
         return Finder::create()
             ->files()
             ->in($this->baseDir)
             ->name(($messageId ?: '*') . '.eml')
         ;
     }
}
