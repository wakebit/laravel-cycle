<?php

declare(strict_types=1);

namespace Wakebit\LaravelCycle\Tests;

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Migrations\Config\MigrationConfig;
use Illuminate\Support\Arr;
use Spiral\Tokenizer\Config\TokenizerConfig;
use Wakebit\CycleBridge\Schema\Config\SchemaConfig;
use Wakebit\LaravelCycle\ServiceProvider;

/**
 * @psalm-suppress MissingConstructor
 */
class TestCase extends \Orchestra\Testbench\TestCase
{
    private \Illuminate\Contracts\Config\Repository $config;

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

    protected function setDatabaseConfigValue(string $key, mixed $value): void
    {
        /** @var DatabaseConfig $databaseConfig */
        $databaseConfig = $this->config->get('cycle.database');
        $databaseConfigAsArray = $databaseConfig->toArray();

        Arr::set($databaseConfigAsArray, $key, $value);
        $this->config->set('cycle.database', new DatabaseConfig($databaseConfigAsArray));
    }

    protected function setSchemaConfigValue(string $key, mixed $value): void
    {
        /** @var SchemaConfig $schemaConfig */
        $schemaConfig = $this->config->get('cycle.orm.schema');
        $schemaConfigAsArray = $schemaConfig->toArray();

        Arr::set($schemaConfigAsArray, $key, $value);
        $this->config->set('cycle.orm.schema', new SchemaConfig($schemaConfigAsArray));
    }

    protected function setTokenizerConfigValue(string $key, mixed $value): void
    {
        /** @var TokenizerConfig $tokenizerConfig */
        $tokenizerConfig = $this->config->get('cycle.orm.tokenizer');
        $tokenizerConfigAsArray = $tokenizerConfig->toArray();

        Arr::set($tokenizerConfigAsArray, $key, $value);
        $this->config->set('cycle.orm.tokenizer', new TokenizerConfig($tokenizerConfigAsArray));
    }

    protected function setMigrationConfigValue(string $key, mixed $value): void
    {
        /** @var MigrationConfig $migrationConfig */
        $migrationConfig = $this->config->get('cycle.migrations');
        $migrationConfigAsArray = $migrationConfig->toArray();

        Arr::set($migrationConfigAsArray, $key, $value);
        /** @var array{directory?: string|null, table?: string|null, safe?: bool|null} $migrationConfigAsArray */
        $this->config->set('cycle.migrations', new MigrationConfig($migrationConfigAsArray));
    }
}
