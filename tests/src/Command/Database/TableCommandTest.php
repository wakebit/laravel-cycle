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
use Cycle\Database\Exception\DBALException;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Command\Command;
use Wakebit\LaravelCycle\Tests\TestCase;

final class TableCommandTest extends TestCase
{
    public function testDescribeWrongDB(): void
    {
        $this->expectException(DBALException::class);

        $this->artisan('cycle:db:table', [
            '--database' => 'missing',
            'table'      => 'missing',
        ]);
    }

    public function testDescribeWrongTable(): void
    {
        $this->expectException(DBALException::class);

        $this->artisan('cycle:db:table', [
            '--database' => 'runtime',
            'table'      => 'missing',
        ]);
    }

    public function testDescribeExisted(): void
    {
        /** @var Database $db */
        $db = $this->app->get(DatabaseInterface::class);

        $table = $db->table('sample1')->getSchema();
        $table->primary('primary_id');
        $table->string('some_string');
        $table->index(['some_string'])->setName('custom_index_1');
        $table->save();

        $table = $db->table('sample')->getSchema();
        $table->primary('primary_id');
        $table->integer('primary1_id');
        $table->foreignKey(['primary1_id'])->references('sample1', ['primary_id']);
        $table->integer('some_int');
        $table->index(['some_int'])->setName('custom_index');
        $table->save();

        /** @var Kernel $console */
        $console = $this->app->get(Kernel::class);
        $exitCode = $console->call('cycle:db:table', ['--database' => 'default', 'table' => 'sample']);
        $this->assertSame(Command::SUCCESS, $exitCode);

        $realOutput = $console->output();
        $expectedOutput = [
            'Columns of default.sample',
            'Column:', 'Database Type:', 'Abstract Type:', 'PHP Type:', 'Default Value:',

            'primary_id', 'int', /* 'primary', */ 'int', '---', // differs by dbal version
            'primary1_id', 'int', 'integer', 'int', '---',
            'some_int', 'int', 'integer', 'int', '---',

            'Indexes of default.sample:',
            'Name:', 'Type:', 'Columns:',

            'custom_index', 'INDEX', 'some_int',
            'sample_index_primary1_id_', 'INDEX', 'primary1_id',

            'Foreign Keys of default.sample:',
            'Name:', 'Column:', 'Foreign Table:', 'Foreign Column:', 'On Delete:', 'On Update:',
            'sample_primary1_id_fk', 'primary1_id', 'sample1', 'primary_id', 'NO ACTION', 'NO ACTION',
        ];

        $constraint = class_exists(\Illuminate\Testing\Constraints\SeeInOrder::class)
            ? new \Illuminate\Testing\Constraints\SeeInOrder($realOutput)
            : new \Illuminate\Foundation\Testing\Constraints\SeeInOrder($realOutput);

        $this->assertThat($expectedOutput, $constraint);
    }
}
