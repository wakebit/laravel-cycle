<?php

declare(strict_types=1);

namespace Wakebit\LaravelCycle\Tests\Command\Migrate;

use Cycle\Database\DatabaseInterface;
use Symfony\Component\Console\Command\Command;
use Wakebit\LaravelCycle\Tests\TestCase;

final class InitCommandTest extends TestCase
{
    public function testConsoleCommand(): void
    {
        /** @var DatabaseInterface $db */
        $db = $this->app->get(DatabaseInterface::class);

        $this->assertCount(0, $db->getTables());

        $this->artisan('cycle:migrate:init')
            ->assertExitCode(Command::SUCCESS)
            ->expectsOutput('Migrations table were successfully created.');

        $tables = $db->getTables();
        $this->assertCount(1, $tables);
        $this->assertSame('cycle_migrations', $tables[0]->getName());
    }
}
