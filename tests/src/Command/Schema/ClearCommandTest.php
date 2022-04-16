<?php

declare(strict_types=1);

namespace Wakebit\LaravelCycle\Tests\Command\Schema;

use Symfony\Component\Console\Command\Command;
use Wakebit\CycleBridge\Contracts\Schema\CacheManagerInterface;
use Wakebit\LaravelCycle\Tests\TestCase;

final class ClearCommandTest extends TestCase
{
    public function testClear(): void
    {
        $cache = \Mockery::mock(CacheManagerInterface::class);
        $this->app->instance(CacheManagerInterface::class, $cache);

        $cache->shouldReceive('clear')->withNoArgs();

        $this->artisan('cycle:schema:clear')
            ->expectsOutput('ORM schema cache cleared!')
            ->assertExitCode(Command::SUCCESS);
    }
}
