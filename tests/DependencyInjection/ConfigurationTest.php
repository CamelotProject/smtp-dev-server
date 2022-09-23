<?php

declare(strict_types=1);

namespace Camelot\SmtpDevServer\Tests\DependencyInjection;

use Camelot\SmtpDevServer\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

/**
 * @covers \Camelot\SmtpDevServer\DependencyInjection\Configuration
 *
 * @internal
 */
final class ConfigurationTest extends TestCase
{
    public function testGetConfigTreeBuilder(): void
    {
        static::markTestIncomplete();

        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, []);

        static::assertArrayHasKey('smtp', $config);
        static::assertArrayHasKey('http', $config);
    }
}
