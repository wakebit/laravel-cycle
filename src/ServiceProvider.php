<?php

declare(strict_types=1);

namespace Wakebit\LaravelCycle;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\DatabaseManager;
use Cycle\Database\DatabaseProviderInterface;
use Cycle\Migrations\Config\MigrationConfig;
use Cycle\Migrations\FileRepository;
use Cycle\Migrations\Migrator;
use Cycle\Migrations\RepositoryInterface;
use Cycle\ORM\Factory;
use Cycle\ORM\FactoryInterface;
use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Transaction;
use Cycle\ORM\TransactionInterface;
use Cycle\Schema\Registry;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Spiral\Tokenizer\ClassesInterface;
use Spiral\Tokenizer\ClassLocator;
use Spiral\Tokenizer\Config\TokenizerConfig;
use Spiral\Tokenizer\Tokenizer;
use Wakebit\CycleBridge\Console\Command;
use Wakebit\CycleBridge\Contracts\Schema\CacheManagerInterface;
use Wakebit\CycleBridge\Contracts\Schema\CompilerInterface;
use Wakebit\CycleBridge\Contracts\Schema\GeneratorQueueInterface;
use Wakebit\CycleBridge\Schema\CacheManager;
use Wakebit\CycleBridge\Schema\Compiler;
use Wakebit\CycleBridge\Schema\Config\SchemaConfig;
use Wakebit\CycleBridge\Schema\GeneratorQueue;
use Wakebit\CycleBridge\Schema\SchemaFactory;

final class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    private const DATABASE_CONFIG_KEY = 'cycle.database';
    private const MIGRATIONS_CONFIG_KEY = 'cycle.migrations';
    private const ORM_SCHEMA_CONFIG_KEY = 'cycle.orm.schema';
    private const ORM_TOKENIZER_CONFIG_KEY = 'cycle.orm.tokenizer';

    /** {@inheritDoc} */
    public function register(): void
    {
        /** @psalm-suppress DeprecatedMethod */
        AnnotationRegistry::registerLoader('class_exists');

        $configPath = __DIR__ . '/../config/cycle.php';
        $this->mergeConfigFrom($configPath, 'cycle');

        $this->initiateDatabaseConfig();
        $this->initiateDatabaseManager();
        $this->initiateTokenizer();
        $this->initiateORM();
        $this->initiateTransaction();
        $this->initiateMigrator();
    }

    private function initiateDatabaseConfig(): void
    {
        $this->app->singleton(DatabaseConfig::class, static function (Container $app): DatabaseConfig {
            /** @var ConfigRepository $laravelConfig */
            $laravelConfig = $app->make(ConfigRepository::class);

            /** @var DatabaseConfig */
            return $laravelConfig->get(self::DATABASE_CONFIG_KEY);
        });
    }

    private function initiateDatabaseManager(): void
    {
        $this->app->singleton(DatabaseProviderInterface::class, static function (Container $app): DatabaseProviderInterface {
            /** @var DatabaseConfig $databaseConfig */
            $databaseConfig = $app->make(DatabaseConfig::class);

            return new DatabaseManager($databaseConfig);
        });

        $this->app->bind(DatabaseInterface::class, static function (Container $app): DatabaseInterface {
            return $app->get(DatabaseProviderInterface::class)->database();
        });

        $this->app->alias(DatabaseProviderInterface::class, DatabaseManager::class);
    }

    private function initiateTokenizer(): void
    {
        $this->app->singleton(TokenizerConfig::class, static function (Container $app): TokenizerConfig {
            /** @var ConfigRepository $laravelConfig */
            $laravelConfig = $app->make(ConfigRepository::class);

            /** @var TokenizerConfig */
            return $laravelConfig->get(self::ORM_TOKENIZER_CONFIG_KEY);
        });

        $this->app->singleton(Tokenizer::class, static function (Container $app): Tokenizer {
            /** @var TokenizerConfig $tokenizerConfig */
            $tokenizerConfig = $app->make(TokenizerConfig::class);

            return new Tokenizer($tokenizerConfig);
        });

        $this->app->singleton(ClassesInterface::class, static function (Container $app): ClassesInterface {
            /** @var Tokenizer $tokenizer */
            $tokenizer = $app->make(Tokenizer::class);

            return $tokenizer->classLocator();
        });

        $this->app->alias(ClassesInterface::class, ClassLocator::class);
    }

    private function initiateORM(): void
    {
        $this->app->singleton(SchemaConfig::class, static function (Container $app): SchemaConfig {
            /** @var ConfigRepository $laravelConfig */
            $laravelConfig = $app->make(ConfigRepository::class);

            /** @var SchemaConfig */
            return $laravelConfig->get(self::ORM_SCHEMA_CONFIG_KEY);
        });

        $this->app->singleton(FactoryInterface::class, static function (Container $app): FactoryInterface {
            /** @var DatabaseProviderInterface $dbal */
            $dbal = $app->make(DatabaseProviderInterface::class);

            return new Factory($dbal);
        });

        $this->app->singleton(\Cycle\Annotated\Embeddings::class, static function (Container $app): \Cycle\Annotated\Embeddings {
            /** @var ClassLocator $classLocator */
            $classLocator = $app->make(ClassLocator::class);

            return new \Cycle\Annotated\Embeddings($classLocator);
        });

        $this->app->singleton(\Cycle\Annotated\Entities::class, static function (Container $app): \Cycle\Annotated\Entities {
            /** @var ClassLocator $classLocator */
            $classLocator = $app->make(ClassLocator::class);

            return new \Cycle\Annotated\Entities($classLocator);
        });

        $this->app->singleton(CacheManagerInterface::class, static function (Container $app): CacheManagerInterface {
            /** @var SchemaConfig $schemaConfig */
            $schemaConfig = $app->make(SchemaConfig::class);

            /** @var CacheFactory $cacheFactory */
            $cacheFactory = $app->make(CacheFactory::class);

            /** @var string|null $cacheStoreName */
            $cacheStoreName = $schemaConfig->getCacheStore();
            $cacheStore = $cacheFactory->store($cacheStoreName);

            return new CacheManager($cacheStore);
        });

        $this->app->singleton(GeneratorQueueInterface::class, static function (Container $app): GeneratorQueueInterface {
            /** @var SchemaConfig $schemaConfig */
            $schemaConfig = $app->make(SchemaConfig::class);

            return new GeneratorQueue($app, $schemaConfig);
        });

        $this->app->singleton(CompilerInterface::class, static function (Container $app): CompilerInterface {
            /** @var Registry $registry */
            $registry = $app->make(Registry::class);

            return new Compiler($registry);
        });

        $this->app->singleton(SchemaInterface::class, static function (Container $app): SchemaInterface {
            /** @var SchemaFactory $factory */
            $factory = $app->make(SchemaFactory::class);

            return $factory->create();
        });

        $this->app->singleton(ORMInterface::class, static function (Container $app): ORMInterface {
            /** @var FactoryInterface $factory */
            $factory = $app->make(FactoryInterface::class);

            /** @var SchemaInterface $schema */
            $schema = $app->make(SchemaInterface::class);

            return new ORM($factory, $schema);
        });

        $this->app->alias(FactoryInterface::class, Factory::class);
        $this->app->alias(CacheManagerInterface::class, CacheManager::class);
        $this->app->alias(GeneratorQueueInterface::class, GeneratorQueue::class);
        $this->app->alias(CompilerInterface::class, Compiler::class);
        $this->app->alias(SchemaInterface::class, Schema::class);
        $this->app->alias(ORMInterface::class, ORM::class);
    }

    private function initiateTransaction(): void
    {
        $this->app->bind(TransactionInterface::class, static function (Container $app): TransactionInterface {
            /** @var ORMInterface $orm */
            $orm = $app->make(ORMInterface::class);

            return new Transaction($orm);
        });

        $this->app->alias(TransactionInterface::class, Transaction::class);
    }

    private function initiateMigrator(): void
    {
        $this->app->singleton(MigrationConfig::class, static function (Container $app): MigrationConfig {
            /** @var ConfigRepository $laravelConfig */
            $laravelConfig = $app->make(ConfigRepository::class);

            /** @var MigrationConfig */
            return $laravelConfig->get(self::MIGRATIONS_CONFIG_KEY);
        });

        $this->app->singleton(RepositoryInterface::class, static function (Container $app): RepositoryInterface {
            /** @var MigrationConfig $migrationConfig */
            $migrationConfig = $app->make(MigrationConfig::class);

            return new FileRepository($migrationConfig);
        });

        $this->app->singleton(Migrator::class, static function (Container $app): Migrator {
            /** @var MigrationConfig $migrationConfig */
            $migrationConfig = $app->make(MigrationConfig::class);

            /** @var DatabaseManager $dbal */
            $dbal = $app->make(DatabaseProviderInterface::class);

            /** @var RepositoryInterface $repository */
            $repository = $app->make(RepositoryInterface::class);

            return new Migrator($migrationConfig, $dbal, $repository);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $configPath = __DIR__ . '/../config/cycle.php';

        $this->publishes([$configPath => $this->app->configPath('cycle.php')], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Command\Database\ListCommand::class,
                Command\Database\TableCommand::class,
                Command\Migrate\InitCommand::class,
                Command\Migrate\MigrateCommand::class,
                Command\Migrate\RollbackCommand::class,
                Command\Migrate\StatusCommand::class,
                Command\Schema\CacheCommand::class,
                Command\Schema\ClearCommand::class,
                Command\Schema\MigrateCommand::class,
                Command\Schema\SyncCommand::class,
            ]);
        }
    }
}
