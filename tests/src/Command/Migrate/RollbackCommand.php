<?php

declare(strict_types=1);

namespace Wakebit\LaravelCycle\Tests\Command\Migrate;

use Spiral\Database\DatabaseInterface;
use Spiral\Migrations\Config\MigrationConfig;
use Wakebit\LaravelCycle\Tests\TestCase;

final class RollbackCommandTest extends TestCase
{
    /** @var \Illuminate\Contracts\Config\Repository */
    private $config;

    /** @var DatabaseInterface */
    private $db;

    /** @var string */
    private $migrationsTable;

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
            ->assertExitCode(1);

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
            ->assertExitCode(0);

        $this->assertAllTablesExceptLatestArePresent();
    }

    public function testForceRunningWhenEnvironmentIsNotSafe(): void
    {
        $this->setMigrationConfigValue('safe', false);
        $this->callMigrateCommand();

        $this->artisan('cycle:migrate:rollback --force')
            ->expectsOutput('Migration 0_default_create_customers was successfully rolled back.')
            ->assertExitCode(0);

        $this->assertAllTablesExceptLatestArePresent();
    }

    public function testRollingBackAllMigrations(): void
    {
        $this->callMigrateCommand();

        $this->artisan('cycle:migrate:rollback --all')
            ->expectsOutput('Migration 0_default_create_customers was successfully rolled back.')
            ->expectsOutput('Migration 0_default_change_articles_add_description was successfully rolled back.')
            ->expectsOutput('Migration 0_default_create_articles was successfully rolled back.')
            ->assertExitCode(0);

        $this->assertOnlyMigrationsTableIsPresent();
    }

    public function testRunningWithoutExecutedMigrations(): void
    {
        $this->assertNoTablesArePresent();

        $this->artisan('cycle:migrate:rollback')
            ->expectsOutput('No executed migrations were found.')
            ->assertExitCode(0);

        $this->assertOnlyMigrationsTableIsPresent();
    }

    public function testWithConfiguredMigrator(): void
    {
        $this->artisan('cycle:migrate:init')->assertExitCode(0);
        $this->assertOnlyMigrationsTableIsPresent();

        $this->artisan('cycle:migrate:rollback')->assertExitCode(0);
        $this->assertOnlyMigrationsTableIsPresent();
    }

    public function testWithNonConfiguredMigrator(): void
    {
        $this->assertNoTablesArePresent();

        $this->artisan('cycle:migrate:rollback')->assertExitCode(0);
        $this->assertOnlyMigrationsTableIsPresent();
    }

    private function callMigrateCommand(): void
    {
        $this->artisan('cycle:migrate --force')->assertExitCode(0);
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
