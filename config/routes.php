<?php

declare(strict_types=1);

use Camelot\SmtpDevServer\Controller\AssetController;
use Camelot\SmtpDevServer\Controller\AttachmentController;
use Camelot\SmtpDevServer\Controller\MailboxController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    $routes->add('mailbox', '/')
        ->controller(MailboxController::class)
        ->methods([Request::METHOD_GET, Request::METHOD_POST])
    ;
    $routes->add('asset', '/asset/{asset}')
        ->controller(AssetController::class)
        ->methods([Request::METHOD_GET])
        ->requirements(['asset' => '.+'])
    ;
    $routes->add('attachment', '/attachment/{messageId}/{filename}')
        ->controller(AttachmentController::class)
        ->methods([Request::METHOD_GET])
        ->requirements(['filename' => '.+'])
    ;
};
