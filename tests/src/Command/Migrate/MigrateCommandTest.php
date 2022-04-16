<?php

declare(strict_types=1);

namespace Wakebit\LaravelCycle\Tests\Command\Migrate;

use Cycle\Database\DatabaseInterface;
use Cycle\Migrations\Config\MigrationConfig;
use Symfony\Component\Console\Command\Command;
use Wakebit\LaravelCycle\Tests\TestCase;

final class MigrateCommandTest extends TestCase
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
        $this->assertNoTablesArePresent();

        $this->artisan('cycle:migrate')
            ->expectsOutput('Confirmation is required to run migrations!')
            ->expectsQuestion('<question>Would you like to continue?</question> ', false)
            ->expectsOutput('Cancelling operation...')
            ->assertExitCode(Command::FAILURE);

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
            ->assertExitCode(Command::SUCCESS);

        $this->assertAllTablesArePresent();
    }

    public function testForceRunningWhenEnvironmentIsNotSafe(): void
    {
        $this->setMigrationConfigValue('safe', false);

        $this->artisan('cycle:migrate --force')
            ->expectsOutput('Migration 0_default_create_articles was successfully executed.')
            ->expectsOutput('Migration 0_default_change_articles_add_description was successfully executed.')
            ->expectsOutput('Migration 0_default_create_customers was successfully executed.')
            ->assertExitCode(Command::SUCCESS);

        $this->assertAllTablesArePresent();
    }

    public function testRunningOnlyOneMigration(): void
    {
        $this->artisan('cycle:migrate --one')
            ->expectsOutput('Migration 0_default_create_articles was successfully executed.')
            ->assertExitCode(Command::SUCCESS);

        $tables = $this->db->getTables();
        $this->assertCount(2, $tables);
        $this->assertSame($this->migrationsTable, $tables[0]->getName());
        $this->assertSame('articles', $tables[1]->getName());
    }

    public function testRunningWithoutNewMigrations(): void
    {
        $this->assertNoTablesArePresent();

        $this->artisan('cycle:migrate')->assertExitCode(Command::SUCCESS);
        $this->assertAllTablesArePresent();

        $this->artisan('cycle:migrate')
            ->expectsOutput('No outstanding migrations were found.')
            ->assertExitCode(Command::SUCCESS);

        $this->assertAllTablesArePresent();
    }

    public function testWithInitiatedMigrator(): void
    {
        $this->artisan('cycle:migrate:init')->assertExitCode(Command::SUCCESS);
        $this->assertOnlyMigrationsTableIsPresent();

        $this->artisan('cycle:migrate')->assertExitCode(Command::SUCCESS);
        $this->assertAllTablesArePresent();
    }

    public function testWithoutInitiatedMigrator(): void
    {
        $this->assertNoTablesArePresent();

        $this->artisan('cycle:migrate')->assertExitCode(Command::SUCCESS);
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
