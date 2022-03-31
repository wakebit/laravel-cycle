<?php

declare(strict_types=1);

namespace Wakebit\LaravelCycle\Tests\Command\Schema;

use Cycle\ORM\SchemaInterface;
use Wakebit\CycleBridge\Contracts\Schema\CacheManagerInterface;
use Wakebit\LaravelCycle\Tests\TestCase;
use Wakebit\LaravelCycle\TestsApp\Entity\Customer;

final class CacheCommandTest extends TestCase
{
    public function testGetSchema(): void
    {
        $this->artisan('cycle:schema:cache')
            ->expectsOutput('ORM schema cached successfully!')
            ->assertExitCode(0);

        /** @var SchemaInterface $schema */
        $schema = $this->app->get(SchemaInterface::class);

        $this->assertTrue($schema->defines('customer'));
        $this->assertSame(Customer::class, $schema->define('customer', SchemaInterface::ENTITY));
    }

    public function testGetSchemaFromCache(): void
    {
        $cache = \Mockery::mock(CacheManagerInterface::class);
        $this->app->instance(CacheManagerInterface::class, $cache);

        $cache->shouldReceive('write');
        $cache->shouldReceive('isCached')->once()->andReturn(true);
        $cache->shouldReceive('read')->once()->andReturn([]);

        $this->artisan('cycle:schema:cache')
            ->expectsOutput('ORM schema cached successfully!')
            ->assertExitCode(0);

        /** @var SchemaInterface $schema */
        $schema = $this->app->get(SchemaInterface::class);

        $this->assertFalse($schema->defines('customer'));
    }
}
