<?php

declare(strict_types=1);

namespace Wakebit\LaravelCycle\Tests\Command\Migrate;

use Spiral\Database\DatabaseInterface;
use Spiral\Migrations\Config\MigrationConfig;
use Wakebit\LaravelCycle\Tests\TestCase;

final class MigrateCommandTest extends TestCase
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
        $this->assertNoTablesArePresent();

        $this->artisan('cycle:migrate')
            ->expectsOutput('Confirmation is required to run migrations!')
            ->expectsQuestion('<question>Would you like to continue?</question> ', false)
            ->expectsOutput('Cancelling operation...')
            ->assertExitCode(1);

        $this->assertNoTablesArePresent();
    }

    public function testConfirmationWhenEnvironmentIsNotSafe(): void
    {
        $this->setMigrationConfigValue('safe', false);
        $this->assertNoTablesArePresent();

        $this->artisan('cycle:migrate')
            ->expectsOutput('Confirmation is required to run migrations!')
            ->expectsQuestion('<question>Would you like to continue?</question> ', true)
            ->expectsOutput('Migration 0_default_create_articles was successfully executed.')
            ->expectsOutput('Migration 0_default_change_articles_add_description was successfully executed.')
            ->expectsOutput('Migration 0_default_create_customers was successfully executed.')
            ->assertExitCode(0);

        $this->assertAllTablesArePresent();
    }

    public function testForceRunningWhenEnvironmentIsNotSafe(): void
    {
        $this->setMigrationConfigValue('safe', false);

        $this->artisan('cycle:migrate --force')
            ->expectsOutput('Migration 0_default_create_articles was successfully executed.')
            ->expectsOutput('Migration 0_default_change_articles_add_description was successfully executed.')
            ->expectsOutput('Migration 0_default_create_customers was successfully executed.')
            ->assertExitCode(0);

        $this->assertAllTablesArePresent();
    }

    public function testRunningOnlyOneMigration(): void
    {
        $this->artisan('cycle:migrate --one')
            ->expectsOutput('Migration 0_default_create_articles was successfully executed.')
            ->assertExitCode(0);

        $tables = $this->db->getTables();
        $this->assertCount(2, $tables);
        $this->assertSame($this->migrationsTable, $tables[0]->getName());
        $this->assertSame('articles', $tables[1]->getName());
    }

    public function testRunningWithoutNewMigrations(): void
    {
        $this->assertNoTablesArePresent();

        $this->artisan('cycle:migrate')->assertExitCode(0);
        $this->assertAllTablesArePresent();

        $this->artisan('cycle:migrate')
            ->expectsOutput('No outstanding migrations were found.')
            ->assertExitCode(0);

        $this->assertAllTablesArePresent();
    }

    public function testWithInitiatedMigrator(): void
    {
        $this->artisan('cycle:migrate:init')->assertExitCode(0);
        $this->assertOnlyMigrationsTableIsPresent();

        $this->artisan('cycle:migrate')->assertExitCode(0);
        $this->assertAllTablesArePresent();
    }

    public function testWithoutInitiatedMigrator(): void
    {
        $this->assertNoTablesArePresent();

        $this->artisan('cycle:migrate')->assertExitCode(0);
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
}