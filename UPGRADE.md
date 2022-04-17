# Upgrade Guide

## Upgrading from 2.x from 1.x
2.x is using Cycle ORM v2.

### Minimum PHP version
PHP 8.0 is now the minimum required version.

1. [Replace](#namespaces) namespaces.
2. [Replace](#config) database config `connections` section.
3. Update the following dependency in your composer.json file:
`wakebit/laravel-cycle` to `^2.0`

### Namespaces
- `spiral/database` is moved to a new repository `cycle/database` so now it has new namespace. To accommodate for these changes you need to replace all namespaces start from `Cycle\Database` with `Cycle\Database`.
- `spiral/migrations` is moved to a new repository `cycle/migrations` so now it has new namespace. To accommodate for these changes you need to replace all namespaces start from `Cycle\Migrations` with `Cycle\Migrations`. Also, don't forget to change extending class in your migration files.

### Config
- Since `cycle/database` v2.0 connection configuration has been changed. You don't need to configure arrays anymore. Use config DTO's instead of. Replace `connections` section's content in the `DatabaseConfig` of your config `cycle.php`:
```php
'connections' => [
    'sqlite' => new \Cycle\Database\Config\SQLiteDriverConfig(
        connection: new \Cycle\Database\Config\SQLite\MemoryConnectionConfig(),
        queryCache: true,
    ),

    'mysql' => new \Cycle\Database\Config\MySQLDriverConfig(
        connection: new \Cycle\Database\Config\MySQL\TcpConnectionConfig(
            database: env('DB_DATABASE', 'forge'),
            host: env('DB_HOST', '127.0.0.1'),
            port: env('DB_PORT', 3306),
            user: env('DB_USERNAME', 'forge'),
            password: env('DB_PASSWORD', ''),
        ),
        queryCache: true,
    ),

    'postgres' => new \Cycle\Database\Config\PostgresDriverConfig(
        connection: new \Cycle\Database\Config\Postgres\TcpConnectionConfig(
            database: env('DB_DATABASE', 'forge'),
            host: env('DB_HOST', '127.0.0.1'),
            port: env('DB_PORT', 5432),
            user: env('DB_USERNAME', 'forge'),
            password: env('DB_PASSWORD', ''),
        ),
        schema: 'public',
        queryCache: true,
    ),

    'sqlServer' => new \Cycle\Database\Config\SQLServerDriverConfig(
        connection: new \Cycle\Database\Config\SQLServer\TcpConnectionConfig(
            database: env('DB_DATABASE', 'forge'),
            host: env('DB_HOST', '127.0.0.1'),
            port: env('DB_PORT', 1433),
            user: env('DB_USERNAME', 'forge'),
            password: env('DB_PASSWORD', ''),
        ),
        queryCache: true,
    ),
],
```
Read more on [database connection](https://cycle-orm.dev/docs/database-connect/2.x/en) page.
Or you can just republish config using command:
```bash
php artisan vendor:publish --provider="Wakebit\LaravelCycle\ServiceProvider" --tag=config
```

### Entity Manager instead of Transaction
`Cycle\ORM\Transaction` class was marked as deprecated in the Cycle ORM v2. Use `Cycle\ORM\EntityManager` instead.
Entity manager can be taken from container.
```php
<?php

/** @var \Cycle\ORM\EntityManagerInterface $em */
$em = $this->app->get(\Cycle\ORM\EntityManagerInterface::class);
$em->persist(...);
$em->run();
```
See usage [here](https://cycle-orm.dev/docs/advanced-entity-manager/2.x/en).
`Transaction` and `TransactionInterface` don't supported more in this library. You can add this fallback code to some of your service providers for gradual migration:
```php
public function register()
{
    $this->app->bind(\Cycle\ORM\TransactionInterface::class, static function (\Illuminate\Contracts\Container\Container $app): \Cycle\ORM\TransactionInterface {
        /** @var \Cycle\ORM\ORMInterface $orm */
        $orm = $app->make(\Cycle\ORM\ORMInterface::class);

        return new \Cycle\ORM\Transaction($orm);
    }
}
```

### Default schema compilation pipeline changed
It may be useful to know that now it looks so:
```php
[
    GeneratorQueueInterface::GROUP_INDEX => [
        \Cycle\Annotated\Embeddings::class,                 // register embeddable entities
        \Cycle\Annotated\Entities::class,                   // register annotated entities
        \Cycle\Annotated\TableInheritance::class,           // register STI/JTI
        \Cycle\Annotated\MergeColumns::class,               // add @Table column declarations
    ],
    GeneratorQueueInterface::GROUP_RENDER => [
        \Cycle\Schema\Generator\ResetTables::class,         // re-declared table schemas (remove columns)
        \Cycle\Schema\Generator\GenerateRelations::class,   // generate entity relations
        \Cycle\Schema\Generator\GenerateModifiers::class,   // generate changes from schema modifiers
        \Cycle\Schema\Generator\ValidateEntities::class,    // make sure all entity schemas are correct
        \Cycle\Schema\Generator\RenderTables::class,        // declare table schemas
        \Cycle\Schema\Generator\RenderRelations::class,     // declare relation keys and indexes
        \Cycle\Schema\Generator\RenderModifiers::class,     // render all schema modifiers
        \Cycle\Annotated\MergeIndexes::class,               // add @Table column declarations
    ],
    GeneratorQueueInterface::GROUP_POSTPROCESS => [
        \Cycle\Schema\Generator\GenerateTypecast::class,    // typecast non string columns
    ],
];
```

### New commands
- `cycle:schema:render` - Render available schemas.
- `cycle:migrate:replay` - Replay (down, up) one or multiple migrations.

See [readme](README.md#working-with-orm-schema) for more info.

### Custom collection factory support
Cycle ORM v2.0 added support for using custom collections instead of just Doctrine Collections. Read [here](README.md#using-custom-collection) how to use it.

Also, it may be useful to read the Cycle ORM v2 [upgrading guide](https://cycle-orm.dev/docs/intro-upgrade/2.x/en).
