<?php

declare(strict_types=1);

namespace Wakebit\LaravelCycle\Tests\Command\Migrate;

use Cycle\Database\DatabaseInterface;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Command\Command;
use Wakebit\LaravelCycle\Tests\TestCase;

final class ReplayCommandTest extends TestCase
{
    public function testOneMigration(): void
    {
        $dbal = $this->app->get(DatabaseInterface::class);
        $this->assertSame([], $dbal->getTables());

        $this->artisan('cycle:migrate')->assertExitCode(Command::SUCCESS);
        $this->assertCount(3, $dbal->getTables());

        $console = $this->app->get(Kernel::class);
        $exitCode = $console->call('cycle:migrate:replay');
        $realOutput = $console->output();
        $expectedOutput = [
            'Rolling back executed migration(s)...',
            'Migration 0_default_create_customers was successfully rolled back.',

            'Executing outstanding migration(s)...',
            'Migration 0_default_create_customers was successfully executed.',
        ];

        $constraint = class_exists(\Illuminate\Testing\Constraints\SeeInOrder::class)
            ? new \Illuminate\Testing\Constraints\SeeInOrder($realOutput)
            : new \Illuminate\Foundation\Testing\Constraints\SeeInOrder($realOutput);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertCount(3, $dbal->getTables());
        $this->assertThat($expectedOutput, $constraint);
        $this->assertStringNotContainsString('0_default_change_articles_add_description', $realOutput);
        $this->assertStringNotContainsString('0_default_create_articles', $realOutput);
    }

    public function testAllMigrations(): void
    {
        $dbal = $this->app->get(DatabaseInterface::class);
        $this->assertSame([], $dbal->getTables());

        $this->artisan('cycle:migrate')->assertExitCode(Command::SUCCESS);
        $this->assertCount(3, $dbal->getTables());

        $console = $this->app->get(Kernel::class);
        $exitCode = $console->call('cycle:migrate:replay', ['--all' => true]);
        $realOutput = $console->output();
        $expectedOutput = [
            'Rolling back executed migration(s)...',
            'Migration 0_default_create_customers was successfully rolled back.',
            'Migration 0_default_change_articles_add_description was successfully rolled back.',
            'Migration 0_default_create_articles was successfully rolled back.',

            'Executing outstanding migration(s)...',
            'Migration 0_default_create_articles was successfully executed.',
            'Migration 0_default_change_articles_add_description was successfully executed.',
            'Migration 0_default_create_customers was successfully executed.',
        ];

        $constraint = class_exists(\Illuminate\Testing\Constraints\SeeInOrder::class)
            ? new \Illuminate\Testing\Constraints\SeeInOrder($realOutput)
            : new \Illuminate\Foundation\Testing\Constraints\SeeInOrder($realOutput);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertCount(3, $dbal->getTables());
        $this->assertThat($expectedOutput, $constraint);
    }
}
