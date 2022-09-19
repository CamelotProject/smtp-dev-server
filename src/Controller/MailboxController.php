<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Controller;

use Camelot\SmtpDevServer\Mailbox;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class MailboxController
{
    private Environment $twig;
    private Mailbox $mailbox;

    public function __construct(Environment $twig, Mailbox $mailbox)
    {
        $this->twig = $twig;
        $this->mailbox = $mailbox;
    }

    public function __invoke(Request $request, string $messageId = null): ?Response
    {
        $save = $request->query->get('save');
        if ($save) {
            $email = $this->mailbox->read($save);
            $response = new Response($email);
            $response->headers->set('Content-Type', 'text/plain');
            $response->headers->set('Content-Length', (string) \strlen($email));
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $save . '.eml"');

            return $response;
        }

        $delete = $request->query->get('delete');
        if ($delete) {
            $this->mailbox->delete($delete);

            return new RedirectResponse('/');
        }

        $emails = $this->mailbox->all();
        $response = new Response();
        $response
            ->setPrivate()
            ->setContent($this->twig->render('base.html.twig', [
                'emails' => $emails,
                'refresh' => $request->query->get('refresh', 15),
            ]))
        ;

        return $response;
    }
}
