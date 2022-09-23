<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Controller;

use Camelot\SmtpDevServer\Mailbox;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final class MailboxController
{
    private Environment $twig;
    private Mailbox $mailbox;

    public function __construct(Environment $twig, Mailbox $mailbox)
    {
        $this->twig = $twig;
        $this->mailbox = $mailbox;
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function __invoke(Request $request, string $messageId = null): ?Response
    {
        $session = $request->getSession();

        if ($request->isMethod(Request::METHOD_POST)) {
            return $this->flush($request);
        }

        if ($request->query->has('save')) {
            return $this->save($request->query->get('save'));
        }

        if ($request->query->has('delete')) {
            return $this->delete($request->query->get('delete'), $request->query->get('refresh'), $session);
        }

        $emails = $this->mailbox->all(true);
        [$toasts, $new] = $this->toasts($session, $emails);

        $response = new Response();
        $response
            ->setPrivate()
            ->setContent($this->twig->render('base.html.twig', [
                'emails' => $emails,
                'new' => $new,
                'refresh' => $request->query->get('refresh'),
                'toasts' => $toasts,
            ]))
        ;

        return $response;
    }

    private function toasts(SessionInterface $session, array $emails): array
    {
        $toasts = [];
        $new = [];

        if ($session->has('flushed')) {
            $toasts[] = ['message' => "Flushed {$session->get('flushed')} messages", 'type' => 'is-warning'];
            $session->remove('flushed');
        }

        if ($session->has('deleted')) {
            $toasts[] = ['message' => "Deleted Message: {$session->get('deleted')}", 'type' => 'is-warning'];
            $session->remove('deleted');
        }

        foreach (array_diff(array_keys($emails), $session->get('current-ids', [])) as $id) {
            $new[] = $id;
            $toasts[] = ['message' => "New Message: {$id}", 'type' => 'is-info'];
        }
        $session->set('current-ids', array_keys($emails));

        return [$toasts, $new];
    }

    private function save(string $save): ?Response
    {
        $email = $this->mailbox->read($save);
        $response = new Response($email);
        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set('Content-Length', (string) \strlen($email));
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $save . '.eml"');

        return $response;
    }

    private function delete(string $delete, ?string $refresh, SessionInterface $session): ?Response
    {
        $this->mailbox->delete($delete);
        $session->set('deleted', $delete);

        return new RedirectResponse("/{$refresh}" ? "?refresh={$refresh}" : '');
    }

    private function flush(Request $request): ?Response
    {
        $session = $request->getSession();

        if ($request->request->has('flush-all')) {
            $count = $this->mailbox->flush();
        } elseif ($request->request->has('flush-older')) {
            $count = $this->mailbox->flush($request->request->getInt('flush-older-than'));
        } else {
            $count = 0;
        }
        $session->set('flushed', $count);

        return new RedirectResponse($request->getUri());
    }
}
