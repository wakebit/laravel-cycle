<?php

declare(strict_types=1);

namespace Wakebit\LaravelCycle\Tests\Command\Migrate;

use Spiral\Database\DatabaseInterface;
use Wakebit\LaravelCycle\Tests\TestCase;

final class InitCommandTest extends TestCase
{
    public function testConsoleCommand(): void
    {
        /** @var DatabaseInterface $db */
        $db = $this->app->get(DatabaseInterface::class);

        $this->assertCount(0, $db->getTables());

        $this->artisan('cycle:migrate:init')
            ->assertExitCode(0)
            ->expectsOutput('Migrations table were successfully created.');

        $tables = $db->getTables();
        $this->assertCount(1, $tables);
        $this->assertSame('cycle_migrations', $tables[0]->getName());
    }
}
