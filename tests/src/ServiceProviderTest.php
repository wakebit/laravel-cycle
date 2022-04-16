<?php

declare(strict_types=1);

namespace Wakebit\LaravelCycle\Tests;

use Cycle\Annotated\Embeddings;
use Cycle\Annotated\Entities;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use Cycle\Database\DatabaseProviderInterface;
use Cycle\ORM\Factory;
use Cycle\ORM\FactoryInterface;
use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Transaction;
use Cycle\ORM\TransactionInterface;
use Spiral\Migrations\Config\MigrationConfig;
use Spiral\Migrations\FileRepository;
use Spiral\Migrations\Migrator;
use Spiral\Tokenizer\ClassesInterface;
use Spiral\Tokenizer\ClassLocator;
use Spiral\Tokenizer\Config\TokenizerConfig;
use Spiral\Tokenizer\Tokenizer;
use Wakebit\CycleBridge\Contracts\Schema\CacheManagerInterface;
use Wakebit\CycleBridge\Contracts\Schema\CompilerInterface;
use Wakebit\CycleBridge\Contracts\Schema\GeneratorQueueInterface;
use Wakebit\CycleBridge\Schema\CacheManager;
use Wakebit\CycleBridge\Schema\Compiler;
use Wakebit\CycleBridge\Schema\GeneratorQueue;

final class ServiceProviderTest extends TestCase
{
    public function testDatabaseConfig(): void
    {
        $this->assertInstanceOf(
            DatabaseConfig::class,
            $this->app->get(DatabaseConfig::class)
        );
    }

    public function testDatabaseProvider(): void
    {
        $this->assertInstanceOf(
            DatabaseProviderInterface::class,
            $this->app->get(DatabaseProviderInterface::class)
        );

        $this->assertInstanceOf(
            DatabaseManager::class,
            $this->app->get(DatabaseProviderInterface::class)
        );
    }

    public function testDatabase(): void
    {
        $this->assertInstanceOf(
            DatabaseInterface::class,
            $this->app->get(DatabaseInterface::class)
        );
    }

    public function testTokenizerConfig(): void
    {
        $this->assertInstanceOf(
            TokenizerConfig::class,
            $this->app->get(TokenizerConfig::class)
        );
    }

    public function testTokenizer(): void
    {
        $this->assertInstanceOf(
            Tokenizer::class,
            $this->app->get(Tokenizer::class)
        );
    }

    public function testClassesInterface(): void
    {
        $this->assertInstanceOf(
            ClassesInterface::class,
            $this->app->get(ClassesInterface::class)
        );
    }

    public function testClassLocator(): void
    {
        $this->assertInstanceOf(
            ClassLocator::class,
            $this->app->get(ClassesInterface::class)
        );
    }

    public function testORMFactory(): void
    {
        $this->assertInstanceOf(
            FactoryInterface::class,
            $this->app->get(FactoryInterface::class)
        );

        $this->assertInstanceOf(
            Factory::class,
            $this->app->get(FactoryInterface::class)
        );
    }

    public function testORMEmbeddingsGenerator(): void
    {
        $this->assertInstanceOf(
            Embeddings::class,
            $this->app->get(Embeddings::class)
        );
    }

    public function testORMEntitiesGenerator(): void
    {
        $this->assertInstanceOf(
            Entities::class,
            $this->app->get(Entities::class)
        );
    }

    public function testORMSchemaCacheManager(): void
    {
        $this->assertInstanceOf(
            CacheManagerInterface::class,
            $this->app->get(CacheManagerInterface::class)
        );

        $this->assertInstanceOf(
            CacheManager::class,
            $this->app->get(CacheManagerInterface::class)
        );
    }

    public function testORMGeneratorQueue(): void
    {
        $this->assertInstanceOf(
            GeneratorQueueInterface::class,
            $this->app->get(GeneratorQueueInterface::class)
        );

        $this->assertInstanceOf(
            GeneratorQueue::class,
            $this->app->get(GeneratorQueueInterface::class)
        );
    }

    public function testORMSchemaCompiler(): void
    {
        $this->assertInstanceOf(
            CompilerInterface::class,
            $this->app->get(CompilerInterface::class)
        );

        $this->assertInstanceOf(
            Compiler::class,
            $this->app->get(CompilerInterface::class)
        );
    }

    public function testORMSchema(): void
    {
        $this->assertInstanceOf(
            SchemaInterface::class,
            $this->app->get(SchemaInterface::class)
        );

        $this->assertInstanceOf(
            Schema::class,
            $this->app->get(SchemaInterface::class)
        );
    }

    public function testORM(): void
    {
        $this->assertInstanceOf(
            ORMInterface::class,
            $this->app->get(ORMInterface::class)
        );

        $this->assertInstanceOf(
            ORM::class,
            $this->app->get(ORMInterface::class)
        );
    }

    public function testTransaction(): void
    {
        $this->assertInstanceOf(
            TransactionInterface::class,
            $this->app->get(TransactionInterface::class)
        );

        $this->assertInstanceOf(
            Transaction::class,
            $this->app->get(TransactionInterface::class)
        );
    }

    public function testMigratorConfig(): void
    {
        $this->assertInstanceOf(
            MigrationConfig::class,
            $this->app->get(MigrationConfig::class)
        );
    }

    public function testMigratorFileRepository(): void
    {
        $this->assertInstanceOf(
            FileRepository::class,
            $this->app->get(FileRepository::class)
        );
    }

    public function testMigrator(): void
    {
        $this->assertInstanceOf(
            Migrator::class,
            $this->app->get(Migrator::class)
        );
    }
}
