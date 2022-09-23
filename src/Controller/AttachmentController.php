<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Controller;

use Camelot\SmtpDevServer\Exception\ServerRuntimeException;
use Camelot\SmtpDevServer\Mailbox;
use DateTimeImmutable;
use PhpMimeMailParser\Attachment;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;

final class AttachmentController
{
    private Mailbox $mailbox;

    public function __construct(Mailbox $mailbox)
    {
        $this->mailbox = $mailbox;
    }

    public function __invoke(Request $request, string $messageId, string $filename): ?Response
    {
        $attachment = $this->getAttachment($messageId, $filename);

        $response = (new Response())
            ->setPublic()
            ->setDate(new DateTimeImmutable())
            ->setMaxAge(3600)
            ->setSharedMaxAge(3600)
            ->setContent($attachment->getContent())
        ;
        $response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename));
        $response->headers->set('Content-Type', $attachment->getContentType());

        return $response;
    }

    private function getAttachment(string $messageId, string $filename): Attachment
    {
        $filenames = [];
        $message = $this->mailbox->get($messageId);
        /** @var Attachment $attachment */
        foreach ($message['attachments'] as $attachment) {
            $filenames[] = $filename;
            if ($attachment->getFilename() === $filename) {
                return $attachment;
            }
        }

        throw new ServerRuntimeException(sprintf('Message ID %s does not have the attachment %s. Attached files: %s', $messageId, $filename, implode(', ', $filenames)));
    }
}
