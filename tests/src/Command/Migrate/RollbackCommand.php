<?php

declare(strict_types=1);

namespace Wakebit\LaravelCycle\Tests\Command\Migrate;

use Cycle\Database\DatabaseInterface;
use Cycle\Migrations\Config\MigrationConfig;
use Symfony\Component\Console\Command\Command;
use Wakebit\LaravelCycle\Tests\TestCase;

final class RollbackCommandTest extends TestCase
{
    private \Illuminate\Contracts\Config\Repository $config;
    private DatabaseInterface $db;
    private string $migrationsTable;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var \Illuminate\Contracts\Config\Repository */
        $this->config = $this->app->get(\Illuminate\Contracts\Config\Repository::class);

        /** @var DatabaseInterface */
        $this->db = $this->app->get(DatabaseInterface::class);

        /** @var MigrationConfig $migrationConfig */
        $migrationConfig = $this->config->get('cycle.migrations');
        $this->migrationsTable = $migrationConfig->getTable();
    }

    public function testCancellationWhenEnvironmentIsNotSafe(): void
    {
        $this->setMigrationConfigValue('safe', false);
        $this->callMigrateCommand();

        $this->artisan('cycle:migrate:rollback')
            ->expectsOutput('Confirmation is required to run migrations!')
            ->expectsQuestion('<question>Would you like to continue?</question> ', false)
            ->expectsOutput('Cancelling operation...')
            ->assertExitCode(Command::FAILURE);

        $this->assertAllTablesArePresent();
    }

    public function testConfirmationWhenEnvironmentIsNotSafe(): void
    {
        $this->setMigrationConfigValue('safe', false);
        $this->callMigrateCommand();

        $this->artisan('cycle:migrate:rollback')
            ->expectsOutput('Confirmation is required to run migrations!')
            ->expectsQuestion('<question>Would you like to continue?</question> ', true)
            ->expectsOutput('Migration 0_default_create_customers was successfully rolled back.')
            ->assertExitCode(Command::SUCCESS);

        $this->assertAllTablesExceptLatestArePresent();
    }

    public function testForceRunningWhenEnvironmentIsNotSafe(): void
    {
        $this->setMigrationConfigValue('safe', false);
        $this->callMigrateCommand();

        $this->artisan('cycle:migrate:rollback --force')
            ->expectsOutput('Migration 0_default_create_customers was successfully rolled back.')
            ->assertExitCode(Command::SUCCESS);

        $this->assertAllTablesExceptLatestArePresent();
    }

    public function testRollingBackAllMigrations(): void
    {
        $this->callMigrateCommand();

        $this->artisan('cycle:migrate:rollback --all')
            ->expectsOutput('Migration 0_default_create_customers was successfully rolled back.')
            ->expectsOutput('Migration 0_default_change_articles_add_description was successfully rolled back.')
            ->expectsOutput('Migration 0_default_create_articles was successfully rolled back.')
            ->assertExitCode(Command::SUCCESS);

        $this->assertOnlyMigrationsTableIsPresent();
    }

    public function testRunningWithoutExecutedMigrations(): void
    {
        $this->assertNoTablesArePresent();

        $this->artisan('cycle:migrate:rollback')
            ->expectsOutput('No executed migrations were found.')
            ->assertExitCode(Command::SUCCESS);

        $this->assertOnlyMigrationsTableIsPresent();
    }

    public function testWithConfiguredMigrator(): void
    {
        $this->artisan('cycle:migrate:init')->assertExitCode(Command::SUCCESS);
        $this->assertOnlyMigrationsTableIsPresent();

        $this->artisan('cycle:migrate:rollback')->assertExitCode(Command::SUCCESS);
        $this->assertOnlyMigrationsTableIsPresent();
    }

    public function testWithNonConfiguredMigrator(): void
    {
        $this->assertNoTablesArePresent();

        $this->artisan('cycle:migrate:rollback')->assertExitCode(Command::SUCCESS);
        $this->assertOnlyMigrationsTableIsPresent();
    }

    private function callMigrateCommand(): void
    {
        $this->artisan('cycle:migrate --force')->assertExitCode(Command::SUCCESS);
        $this->assertAllTablesArePresent();
    }

    private function assertNoTablesArePresent(): void
    {
        $this->assertCount(0, $this->db->getTables());
    }

    private function assertOnlyMigrationsTableIsPresent(): void
    {
        $tables = $this->db->getTables();

        $this->assertCount(1, $tables);
        $this->assertSame($this->migrationsTable, $tables[0]->getName());
    }

    private function assertAllTablesArePresent(): void
    {
        $tables = $this->db->getTables();

        $this->assertCount(3, $tables);
        $this->assertSame($this->migrationsTable, $tables[0]->getName());
        $this->assertSame('articles', $tables[1]->getName());
        $this->assertSame('customers', $tables[2]->getName());
    }

    private function assertAllTablesExceptLatestArePresent(): void
    {
        $tables = $this->db->getTables();

        $this->assertCount(2, $tables);
        $this->assertSame($this->migrationsTable, $tables[0]->getName());
        $this->assertSame('articles', $tables[1]->getName());
    }
}
