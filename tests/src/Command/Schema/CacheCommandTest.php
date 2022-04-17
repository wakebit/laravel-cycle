<?php

declare(strict_types=1);

namespace Wakebit\LaravelCycle\Tests\Command\Schema;

use Cycle\ORM\SchemaInterface;
use Symfony\Component\Console\Command\Command;
use Wakebit\CycleBridge\Contracts\Schema\CacheManagerInterface;
use Wakebit\LaravelCycle\Tests\TestCase;
use Wakebit\LaravelCycle\TestsApp\Entity\Customer;

final class CacheCommandTest extends TestCase
{
    public function testGetSchema(): void
    {
        $this->artisan('cycle:schema:cache')
            ->expectsOutput('ORM schema cached successfully!')
            ->assertExitCode(Command::SUCCESS);

        /** @var SchemaInterface $schema */
        $schema = $this->app->get(SchemaInterface::class);

        $this->assertTrue($schema->defines('customer'));
        $this->assertSame(Customer::class, $schema->define('customer', SchemaInterface::ENTITY));
    }

    public function testGetSchemaFromCache(): void
    {
        $cache = $this->createMock(CacheManagerInterface::class);
        $cache->expects($this->once())->method('write');
        $cache->expects($this->once())->method('isCached')->willReturn(true);
        $cache->expects($this->once())->method('read')->willReturn([]);

        $this->app->instance(CacheManagerInterface::class, $cache);
        $this->artisan('cycle:schema:cache')
            ->expectsOutput('ORM schema cached successfully!')
            ->assertExitCode(Command::SUCCESS);

        $schema = $this->app->get(SchemaInterface::class);

        $this->assertFalse($schema->defines('customer'));
    }
}
