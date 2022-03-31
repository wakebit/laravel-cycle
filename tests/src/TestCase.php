<?php

declare(strict_types=1);

namespace Wakebit\LaravelCycle\Tests;

use Illuminate\Support\Arr;
use Spiral\Database\Config\DatabaseConfig;
use Spiral\Migrations\Config\MigrationConfig;
use Spiral\Tokenizer\Config\TokenizerConfig;
use Wakebit\CycleBridge\Schema\Config\SchemaConfig;
use Wakebit\LaravelCycle\ServiceProvider;

/**
 * @psalm-suppress MissingConstructor
 */
class TestCase extends \Orchestra\Testbench\TestCase
{
    /** @var \Illuminate\Contracts\Config\Repository */
    private $config;

    /** {@inheritDoc} */
    protected function getPackageProviders($app): array
    {
        return [
            ServiceProvider::class,
        ];
    }

    /** {@inheritDoc} */
    protected function defineEnvironment($app): void
    {
        /** @var \Illuminate\Contracts\Config\Repository */
        $this->config = $app->get(\Illuminate\Contracts\Config\Repository::class);
        $this->config->set('filesystems.disks.local.root', __DIR__ . '/../');
        $this->config->set('cache.default', 'array');

        $this->setDatabaseConfigValue('databases.default.connection', 'sqlite');
        $this->setTokenizerConfigValue('directories', [__DIR__ . '/../App/Entity']);
        $this->setMigrationConfigValue('directory', __DIR__ . '/../resources/migrations');
    }

    /**
     * @param mixed $value
     */
    protected function setDatabaseConfigValue(string $key, $value): void
    {
        /** @var DatabaseConfig $databaseConfig */
        $databaseConfig = $this->config->get('cycle.database');
        $databaseConfigAsArray = $databaseConfig->toArray();

        Arr::set($databaseConfigAsArray, $key, $value);
        $this->config->set('cycle.database', new DatabaseConfig($databaseConfigAsArray));
    }

    /**
     * @param mixed $value
     */
    protected function setSchemaConfigValue(string $key, $value): void
    {
        /** @var SchemaConfig $schemaConfig */
        $schemaConfig = $this->config->get('cycle.orm.schema');
        $schemaConfigAsArray = $schemaConfig->toArray();

        Arr::set($schemaConfigAsArray, $key, $value);
        $this->config->set('cycle.orm.schema', new SchemaConfig($schemaConfigAsArray));
    }

    /**
     * @param mixed $value
     */
    protected function setTokenizerConfigValue(string $key, $value): void
    {
        /** @var TokenizerConfig $tokenizerConfig */
        $tokenizerConfig = $this->config->get('cycle.orm.tokenizer');
        $tokenizerConfigAsArray = $tokenizerConfig->toArray();

        Arr::set($tokenizerConfigAsArray, $key, $value);
        $this->config->set('cycle.orm.tokenizer', new TokenizerConfig($tokenizerConfigAsArray));
    }

    /**
     * @param mixed $value
     */
    protected function setMigrationConfigValue(string $key, $value): void
    {
        /** @var MigrationConfig $migrationConfig */
        $migrationConfig = $this->config->get('cycle.migrations');
        $migrationConfigAsArray = $migrationConfig->toArray();

        Arr::set($migrationConfigAsArray, $key, $value);
        /** @var array{directory?: string|null, table?: string|null, safe?: bool|null} $migrationConfigAsArray */
        $this->config->set('cycle.migrations', new MigrationConfig($migrationConfigAsArray));
    }
}
