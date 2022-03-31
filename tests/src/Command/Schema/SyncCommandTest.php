<?php

declare(strict_types=1);

namespace Wakebit\LaravelCycle\Tests\Command\Schema;

use Illuminate\Contracts\Filesystem\Filesystem;
use Spiral\Database\DatabaseInterface;
use Wakebit\LaravelCycle\Tests\TestCase;

final class SyncCommandTest extends TestCase
{
    /** @var DatabaseInterface */
    private $db;

    /** @var Filesystem */
    private $files;

    /** @var array<string> */
    private $migrationFiles;

    protected function setUp(): void
    {
        parent::setUp();

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
        $this->deleteTestEntity();

        parent::tearDown();
    }

    public function testSync(): void
    {
        $this->createTestEntity();
        $this->assertFalse($this->db->table('tags')->exists());

        $this->artisan('cycle:migrate');
        $this->artisan('cycle:schema:sync -v')
            ->expectsOutput('Detecting schema changes:')
            ->expectsOutput('• default.tags')
            ->expectsOutput('    - create table')
            ->expectsOutput('    - add column id')
            ->expectsOutput(sprintf("\nORM Schema has been synchronized."))
            ->assertExitCode(0);

        $this->assertNoChangesInMigrationFiles();
        $this->assertTrue($this->db->table('tags')->exists());
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

    private function createTestEntity(): void
    {
        $content = <<<'PHP'
<?php

declare(strict_types=1);

namespace Wakebit\LaravelCycle\TestsApp\Entity;

use Cycle\Annotated\Annotation\Column;use Cycle\Annotated\Annotation\Entity;

/**
 * @Entity
 */
class Tag
{
    /**
     * @Column(type="primary")
     *
     * @var int
     */
    public $id;
}
PHP;

        $this->files->put('App/Entity/Tag.php', $content);
    }

    private function deleteTestEntity(): void
    {
        $this->files->delete('App/Entity/Tag.php');
    }
}
