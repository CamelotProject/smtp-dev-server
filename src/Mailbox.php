<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer;

use PhpMimeMailParser\Attachment;
use PhpMimeMailParser\Parser;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

final class Mailbox
{
    private string $spoolDir;

    public function __construct(string $spoolDir)
    {
        $this->spoolDir = $spoolDir;
    }

    public function all(bool $inline = false): array
    {
        $emails = [];
        /** @var SplFileInfo $file */
        foreach ($this->files() as $file) {
            $id = $file->getFilenameWithoutExtension();
            $emails[$id] = $this->get($id, $inline);
        }

        return $emails;
    }

    public function get(string $id, bool $inline = false): array
    {
        $parser = new Parser();
        $parser->setPath(Path::join($this->spoolDir, "{$id}.eml"));
        $html = $parser->getMessageBody('htmlEmbedded');
        if ($inline) {
            $html = $this->inlineAttachments($html, ...$parser->getAttachments());
        }

        return [
            'headers' => $parser->getHeaders(),
            'text' => $parser->getMessageBody('text'),
            'html' => $html,
            'attachments' => $parser->getAttachments(!$inline),
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

    public function flush(int $olderThan = null): int
    {
        $fs = new Filesystem();
        $files = $this->files();
        $count = \count($files);
        if ($olderThan) {
            $files->date("< now - {$olderThan} hours");
        }
        dump(array_keys(iterator_to_array($files)));
        $fs->remove($files);

        return $count;
    }

    private function inlineAttachments(string $html, Attachment ...$attachments): string
    {
        foreach ($attachments as $attachment) {
            if ($attachment->getContentDisposition() === 'inline') {
                $base64 = 'data:image/' . $attachment->getContentType() . ';base64,' . base64_encode($attachment->getContent());
                $html = str_replace('cid:' . $attachment->getContentID(), $base64, $html);
            }
        }

        return $html;
    }

    private function files(): Finder
    {
        return Finder::create()
            ->files()
            ->in($this->spoolDir)
            ->name('*.eml')
            ->sortByModifiedTime()
            ->reverseSorting()
        ;
    }
}
