<?php

declare(strict_types=1);

namespace Wakebit\LaravelCycle\Tests\Command\Schema;

use Cycle\Database\DatabaseInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Wakebit\LaravelCycle\Tests\TestCase;

final class MigrateCommandTest extends TestCase
{
    private \Illuminate\Contracts\Config\Repository $config;
    private DatabaseInterface $db;
    private Filesystem $files;

    /** @var array<string> */
    private array $migrationFiles;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var \Illuminate\Contracts\Config\Repository */
        $this->config = $this->app->get(\Illuminate\Contracts\Config\Repository::class);

        /** @var DatabaseInterface */
        $this->db = $this->app->get(DatabaseInterface::class);

        /** @var Filesystem */
        $this->files = $this->app->get(Filesystem::class);
        $this->migrationFiles = [
            'resources/migrations/20220210.160450_0_0_default_create_articles.php',
            'resources/migrations/20220210.160451_0_0_default_change_articles_add_description.php',
            'resources/migrations/20220210.160452_0_0_default_create_customers.php',
        ];
    }

    protected function tearDown(): void
    {
        $this->rollbackMigrationFiles();
        $this->deleteTestEntity();

        parent::tearDown();
    }

    private function rollbackMigrationFiles(): void
    {
        /** @var array<string> $files */
        $files = $this->files->allFiles('resources/migrations/');

        foreach ($files as $file) {
            if (!in_array($file, $this->migrationFiles)) {
                $this->files->delete($file);
            }
        }
    }

    public function testConfiguringMigrator(): void
    {
        $this->assertNoTablesArePresent();

        $this->artisan('cycle:schema:migrate');

        $this->assertMigrationsTableExist();
    }

    public function testOutstandingMigrations(): void
    {
        $this->artisan('cycle:schema:migrate')
            ->expectsOutput('Outstanding migrations found, run `cycle:migrate` first.')
            ->assertExitCode(Command::FAILURE);

        $this->assertNoChangesInMigrationFiles();
    }

    public function testMigrate(): void
    {
        $this->createTestEntity();
        $this->assertFalse($this->db->table('tags')->exists());

        $this->artisan('cycle:migrate');
        $this->artisan('cycle:schema:migrate', ['-v' => true])
            ->expectsOutput('Detecting schema changes:')
            ->expectsOutput('• default.tags')
            ->expectsOutput('    - create table')
            ->expectsOutput('    - add column id')
            ->assertExitCode(Command::SUCCESS);

        $this->assertHasChangesInMigrationFiles();
        $this->assertFalse($this->db->table('tags')->exists());
    }

    public function testMigrateWithRun(): void
    {
        $this->createTestEntity();
        $this->assertFalse($this->db->table('tags')->exists());

        $this->artisan('cycle:migrate');
        $this->artisan('cycle:schema:migrate', ['--run' => true])
            ->expectsOutput('Detecting schema changes:')
            ->expectsOutput('• default.tags')
            ->assertExitCode(Command::SUCCESS);

        $this->assertHasChangesInMigrationFiles();
        $this->assertTrue($this->db->table('tags')->exists());
    }

    private function assertNoTablesArePresent(): void
    {
        $this->assertCount(0, $this->db->getTables());
    }

    private function assertMigrationsTableExist(): void
    {
        $tables = $this->db->getTables();

        $this->assertCount(1, $tables);
        $this->assertSame('cycle_migrations', $tables[0]->getName());
    }

    private function assertNoChangesInMigrationFiles(): void
    {
        /** @var array<string> $files */
        $files = $this->files->allFiles('resources/migrations/');
        $this->assertCount(3, $files);

        foreach ($files as $file) {
            $this->assertContains($file, $this->migrationFiles);
        }
    }

    private function assertHasChangesInMigrationFiles(): void
    {
        /** @var array<string> $files */
        $files = $this->files->allFiles('resources/migrations/');
        $this->assertCount(4, $files);

        foreach ($this->migrationFiles as $migrationFile) {
            $this->assertContains($migrationFile, $files);
        }

        // New migration can be positioned in any order :|
        $hasNewMigration = strpos($files[0], 'default_create_tags') !== false
            || strpos($files[1], 'default_create_tags') !== false
            || strpos($files[2], 'default_create_tags') !== false
            || strpos($files[3], 'default_create_tags') !== false;

        $this->assertTrue($hasNewMigration);
    }

    private function createTestEntity(): void
    {
        $content = <<<'PHP'
<?php

declare(strict_types=1);

namespace Wakebit\LaravelCycle\TestsApp\Entity;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;

/**
 * @Entity
 */
class Tag
{
    /**
     * @Column(type="primary")
     */
    public int $id;
}
PHP;

        $this->files->put('App/Entity/Tag.php', $content);
    }

    private function deleteTestEntity(): void
    {
        $this->files->delete('App/Entity/Tag.php');
    }
}
