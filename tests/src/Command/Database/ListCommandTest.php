<?php

/**
 * Credits: Spiral Cycle Bridge.
 *
 * @see https://github.com/spiral/cycle-bridge
 */

declare(strict_types=1);

namespace Wakebit\LaravelCycle\Tests\Command\Database;

use Cycle\Database\Database;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use Cycle\Database\DatabaseProviderInterface;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Command\Command;
use Wakebit\LaravelCycle\Tests\TestCase;

final class ListCommandTest extends TestCase
{
    public function testList(): void
    {
        /** @var Database $db */
        $db = $this->app->get(DatabaseInterface::class);

        $table = $db->table('sample')->getSchema();
        $table->primary('primary_id');
        $table->string('some_string');
        $table->index(['some_string'])->setName('custom_index');
        $table->integer('b_id');
        $table->foreignKey(['b_id'])->references('outer', ['id']);
        $table->save();

        $tableB = $db->table('outer')->getSchema();
        $tableB->primary('id');
        $tableB->save();

        /** @var Kernel $console */
        $console = $this->app->get(Kernel::class);
        $exitCode = $console->call('cycle:db:list');
        $this->assertSame(Command::SUCCESS, $exitCode);

        $realOutput = $console->output();
        $expectedOutput = [
            'Name (ID):', 'Database:', 'Driver:', 'Prefix:', 'Status:', 'Tables:', 'Count Records:',
            'default', ':memory:', 'SQLite', '---', 'connected', 'sample', 'outer', '0', '0',
        ];

        $constraint = class_exists(\Illuminate\Testing\Constraints\SeeInOrder::class)
            ? new \Illuminate\Testing\Constraints\SeeInOrder($realOutput)
            : new \Illuminate\Foundation\Testing\Constraints\SeeInOrder($realOutput);

        $this->assertThat($expectedOutput, $constraint);
    }

    public function testBrokenList(): void
    {
        /** @var DatabaseManager $dm */
        $dm = $this->app->get(DatabaseProviderInterface::class);

        $dm->addDatabase(
            new Database(
                'sqlite',
                '',
                $dm->driver('sqlite')
            )
        );

        /** @var Kernel $console */
        $console = $this->app->get(Kernel::class);
        $exitCode = $console->call('cycle:db:list', ['database' => 'sqlite']);
        $this->assertSame(Command::SUCCESS, $exitCode);

        $realOutput = $console->output();
        $expectedOutput = [
            'Name (ID):', 'Database:', 'Driver:', 'Prefix:', 'Status:', 'Tables:', 'Count Records:',
            'sqlite', ':memory:', 'SQLite', '---', 'connected', 'no tables', 'no records',
        ];

        $constraint = class_exists(\Illuminate\Testing\Constraints\SeeInOrder::class)
            ? new \Illuminate\Testing\Constraints\SeeInOrder($realOutput)
            : new \Illuminate\Foundation\Testing\Constraints\SeeInOrder($realOutput);

        $this->assertThat($expectedOutput, $constraint);
    }
}
