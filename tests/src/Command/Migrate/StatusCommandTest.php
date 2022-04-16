<?php

declare(strict_types=1);

namespace Wakebit\LaravelCycle\Tests\Command\Migrate;

use Spiral\Database\DatabaseInterface;
use Spiral\Migrations\Config\MigrationConfig;
use Symfony\Component\Console\Command\Command;
use Wakebit\LaravelCycle\Tests\TestCase;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class StatusCommandTest extends TestCase
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

    public function testConfiguringMigrator(): void
    {
        $this->assertNoTablesArePresent();

        $this->artisan('cycle:migrate:status')->assertExitCode(Command::SUCCESS);
        $this->assertOnlyMigrationsTableIsPresent();
    }

    public function testWithoutAnyMigration(): void
    {
        $this->setMigrationConfigValue('directory', __DIR__ . '/../resources/migrations2');

        $this->artisan('cycle:migrate:status')
            ->expectsOutput('No migrations were found.')
            ->assertExitCode(Command::FAILURE);
    }

    public function testOutputTable(): void
    {
        $formattedCurrentTime = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->prepareMigrations();

        $command = $this->artisan('cycle:migrate:status');
        $command->assertExitCode(Command::SUCCESS);

        if (version_compare($this->app->version(), '7.0.0', '>=') > 0) {
            $command->expectsTable(
                ['Migration', 'Created at', 'Executed at'],
                [
                    ['0_default_create_articles', '2022-02-10 16:04:50', $formattedCurrentTime],
                    ['0_default_change_articles_add_description', '2022-02-10 16:04:51', $formattedCurrentTime],
                    ['0_default_create_customers', '2022-02-10 16:04:52', 'not executed yet'],
                ]
            );
        }
    }

    private function prepareMigrations(): void
    {
        $this->artisan('cycle:migrate')->assertExitCode(Command::SUCCESS);
        $this->assertAllTablesArePresent();

        $this->artisan('cycle:migrate:rollback')->assertExitCode(Command::SUCCESS);
        $this->assertAllTablesExceptLatestArePresent();
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
