<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Tests\Functional;

use Camelot\SmtpDevServer\Tests\Fixtures\MockServer;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
abstract class FunctionalTestCase extends TestCase
{
    private static MockServer $server;

    public static function setUpBeforeClass(): void
    {
        self::$server = MockServer::create();
        self::$server->start();
    }

    public static function tearDownAfterClass(): void
    {
        self::$server->stop();
    }
}
