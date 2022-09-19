<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Controller;

use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function file_get_contents;

final class AssetController
{
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    public function __invoke(Request $request, string $asset): ?Response
    {
        $file = new SplFileInfo(Path::join($this->projectDir, 'public', 'asset', $asset), '', $asset);

        $response = new Response();
        $response
            ->setPrivate()
            ->setContent(file_get_contents($file->getPathname()))
        ;

        match ($file->getExtension()) {
            'css' => $response->headers->set('content-type', 'text/css; charset=utf-8'),
            'svg' => $response->headers->set('content-type', 'image/svg+xml'),
            default => null,
        };

        return $response;
    }
}
