<?php

declare(strict_types=1);

namespace Wakebit\LaravelCycle\Tests\Command\Schema;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Select\Repository;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\Console\Command\Command;
use Wakebit\LaravelCycle\Tests\TestCase;
use Wakebit\LaravelCycle\TestsApp\Entity\Article;
use Wakebit\LaravelCycle\TestsApp\Entity\Customer;

final class RenderCommandTest extends TestCase
{
    public function testRender(): void
    {
        /** @var Kernel $console */
        $console = $this->app->get(Kernel::class);
        $exitCode = $console->call('cycle:schema:render', ['-nc' => true]);
        $realOutput = $console->output();
        $articlesOutput = [
            '[customer] :: default.customers',
            'Entity:', Customer::class,
            'Mapper:', Mapper::class,
            'Repository:', Repository::class,
            'Primary key:', 'id',
            'Fields', '(property -> db.field -> typecast)', 'id -> id -> int', 'name -> name',
            'Relations:', 'not defined',
        ];

        $customersOutput = [
            '[article] :: default.articles',
            'Entity:', Article::class,
            'Mapper:', Mapper::class,
            'Repository:', Repository::class,
            'Primary key:', 'id',
            'Fields', '(property -> db.field -> typecast)', 'id -> id -> int', 'title -> title', 'description -> description',
            'Relations:', 'not defined',
        ];

        $constraint = class_exists(\Illuminate\Testing\Constraints\SeeInOrder::class)
            ? new \Illuminate\Testing\Constraints\SeeInOrder($realOutput)
            : new \Illuminate\Foundation\Testing\Constraints\SeeInOrder($realOutput);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertThat($articlesOutput, $constraint);
        $this->assertThat($customersOutput, $constraint);
    }
}
