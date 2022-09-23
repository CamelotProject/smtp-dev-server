<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Controller;

use DateTimeImmutable;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;

final class AssetController
{
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    public function __invoke(Request $request, string $asset): ?Response
    {
        $response = new Response();

        $file = new SplFileInfo(Path::join($this->projectDir, 'public', 'asset', $asset), '', $asset);
        if (!$file->isFile()) {
            return $response->setStatusCode(Response::HTTP_NOT_FOUND)->setContent('Not found: ' . $file->getPathname());
        }

        $response
            ->setPublic()
            ->setDate(new DateTimeImmutable())
            ->setMaxAge(3600)
            ->setSharedMaxAge(3600)
            ->setContent(file_get_contents($file->getPathname()))
        ;
        $response->headers->set(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER, 'true');

        match ($file->getExtension()) {
            'css' => $response->headers->set('content-type', 'text/css; charset=utf-8'),
            'svg' => $response->headers->set('content-type', 'image/svg+xml'),
            default => null,
        };

        return $response;
    }
}
