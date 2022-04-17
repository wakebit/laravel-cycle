# Cycle ORM integration with Laravel
This package provides an integration [Cycle ORM](https://cycle-orm.dev) with Laravel. We are supporting integration both versions of the ORM. Internally, it uses [the bridge](https://github.com/wakebit/cycle-bridge) package that can be used with almost any framework.

## Requirements
* PHP >= 8.0
* Laravel 7, 8, 9 

The package uses Cycle ORM 1 or 2 (branches `v1.x` and `v2.x` accordingly).

## Installation
1. Install the package via composer:
```bash
composer require wakebit/laravel-cycle
```
2. Publish the config `cycle.php`:
```bash
php artisan vendor:publish --provider="Wakebit\LaravelCycle\ServiceProvider" --tag=config
```

## Usage
1. Configure database connection in the `database` config section. You don't need to make any changes if you are already using any of Laravel-compatible database driver. It uses same `DB_*` environment variables. The contents of the key should return a `\Cycle\Database\Config\DatabaseConfig` instance. See more [here](https://cycle-orm.dev/docs/database-connect/2.x/en).
2. Configure paths where your entities located in `tokenizer` section. By default, class locator looks them in app folder.

Define entity:
```php
<?php

declare(strict_types=1);

namespace App\Entity;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;

#[Entity]
class User
{
    #[Column(type: 'primary')]
    protected int $id;

    #[Column(type: 'string')]
    protected string $name;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
```

You can take DBAL, ORM and Entity Manager from the container. Quick example of usage:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Cycle\Database\DatabaseProviderInterface;
use Cycle\ORM\EntityManagerInterface;
use Cycle\ORM\ORMInterface;
use Illuminate\Http\Response;

final class HomeController
{    
    public function __construct(
        private DatabaseProviderInterface $dbal,
        private EntityManagerInterface $em,
        private ORMInterface $orm,
    ) {
    }
    
    public function __invoke()
    {
        // DBAL
        $tables = $this->dbal->database()->getTables();
        $tableNames = array_map(fn (\Cycle\Database\TableInterface $table): string => $table->getName(), $tables);
        dump($tableNames);
        
        // Create, modify, delete entities using Transaction
        $user = new \App\Entity\User();
        $user->setName("Hello World");
        $this->em->persist($user);
        $this->em->run();
        dump($user);
        
        // ORM
        $repository = $this->orm->getRepository(\App\Entity\User::class);
        $users = $repository->findAll();
        dump($users);
        
        $user = $repository->findByPK(1);
        dump($user);
    }
}
```
See more on [the official Cycle ORM documentation](https://cycle-orm.dev/docs/readme/2.x/en).

## Working with ORM schema
Schema can be compiled and stored in the cache (recommended for production usage). You can specify the caching driver in `cycle.orm.schema` config key:
```php
use Wakebit\CycleBridge\Schema\Config\SchemaConfig;

return [
    // ...
    'orm' => [
        'schema' => new SchemaConfig([
            'cache' => [
                'store' => env('CACHE_DRIVER', 'file'),           
            ],
        ]),
    ],
    // ...
]
```

#### Commands
| Command                            | Description                                                                    | Options                                                                      |
|------------------------------------|--------------------------------------------------------------------------------|:-----------------------------------------------------------------------------|
| `php artisan cycle:schema:migrate` | Generate ORM schema migrations                                                 | - `--run`: Automatically run generated migration.<br>- `-v`: Verbose output. |
| `php artisan cycle:schema:cache`   | Compile and cache ORM schema                                                   |                                                                              |
| `php artisan cycle:schema:clear`   | Clear cached schema (schema will be generated every request now)               |                                                                              |
| `php artisan cycle:schema:sync`    | Sync ORM schema with database without intermediate migration (risk operation!) |                                                                              |
| `php artisan cycle:schema:render`  | Render available Cycle ORM schemas                                             | - `--no-color`: Display output without colors.                               |

## Database migrations
You can specify the name of migrations table, the path where migrations will be created in `cycle.migrations` config key.

#### Commands
| Command                              | Description                                        | Options                                                                                                       |
|--------------------------------------|----------------------------------------------------|:--------------------------------------------------------------------------------------------------------------|
| `php artisan cycle:migrate:init`     | Initialize migrator: create a table for migrations |                                                                                                               |
| `php artisan cycle:migrate`          | Run all outstanding migrations                     | - `--one`: Execute only one (first) migration.<br>- `--force`: Force the operation to run when in production. |
| `php artisan cycle:migrate:rollback` | Rollback the last migration                        | - `--all`: Rollback all executed migrations.<br>- `--force`: Force the operation to run when in production.   |
| `php artisan cycle:migrate:status`   | Get a list of available migrations                 |                                                                                                               |

## Database commands
| Command                              | Description                                                     | Options                       |
|--------------------------------------|-----------------------------------------------------------------|:------------------------------|
| `php artisan cycle:db:list`          | Get list of available databases, their tables and records count | - `--database`: Database name |
| `php artisan cycle:db:table <table>` | Describe table schema of specific database                      | - `--database`: Database name |

## Writing tests
If you are using memory database (SQLite) you can just run migrations in the `setUp` method of the your test:
```php
public function setUp(): void
{
    parent::setUp();
    
    $this->artisan('cycle:migrate');
}
```
For another databases follow [this instruction](https://cycle-orm.dev/docs/advanced-testing/2.x/en) and drop all tables in the `tearDown` method.

## Advanced
### Manually defined ORM schema
If you want to use a manually defined ORM schema you can define it in the `cycle.orm.schema` `SchemaConfig`'s `map` config key (this key is not present by default):
```php
use Wakebit\CycleBridge\Schema\Config\SchemaConfig;

return [
    // ...
    'orm' => [
        'schema' => new SchemaConfig([
            'map' => require __DIR__ . '/../orm_schema.php',
            // ... 
        ]),
    ]
    // ...
]
```
Manually defined schema should be presented as array. It will be passed to `\Cycle\ORM\Schema` constructor. See more [here](https://cycle-orm.dev/docs/schema-manual/2.x/en).

### Custom schema compilation pipeline
You can redefine the ORM schema compilation generators in the `cycle.orm.schema` `SchemaConfig`'s `generators` config key (this key is not present by default):
```php
use Wakebit\CycleBridge\Schema\Config\SchemaConfig;

return [
    // ...
    'orm' => [
        'schema' => new SchemaConfig([
            'generators' => [
                'index' => [],
                'render' => [
                    \Cycle\Schema\Generator\ResetTables::class,         // re-declared table schemas (remove columns)
                    \Cycle\Schema\Generator\GenerateRelations::class,   // generate entity relations
                    \Cycle\Schema\Generator\GenerateModifiers::class,   // generate changes from schema modifiers
                    \Cycle\Schema\Generator\ValidateEntities::class,    // make sure all entity schemas are correct
                    \Cycle\Schema\Generator\RenderTables::class,        // declare table schemas
                    \Cycle\Schema\Generator\RenderRelations::class,     // declare relation keys and indexes
                    \Cycle\Schema\Generator\RenderModifiers::class,     // render all schema modifiers
                ],
                'postprocess' => [
                    \Cycle\Schema\Generator\GenerateTypecast::class,    // typecast non string columns
                ],
            ]
        ]),
    ]
    // ...
]
```
Classes will be resolved by DI container. Default pipeline you can see [here](https://github.com/wakebit/cycle-bridge/blob/v2.x/src/Schema/Config/SchemaConfig.php#L28) in the bridge package.

### Using custom collection
By default, ORM uses arrays for collection of items. If you want to use Laravel collections instead of or some another collection you can add the following key to config to `orm` section:
```php
return [
    // ...
    'orm' => [
        'default_collection_factory_class' => \Cycle\ORM\Collection\IlluminateCollectionFactory::class,
        // ...
    ],
    // ...
]
```
The factory class will be resolved by container. It should implement the `\Cycle\ORM\Collection\CollectionFactoryInterface` interface.

# Notes
- We don't have a plan to create Laravel Facades, magic helpers, etc. You can create them yourself if you need to.

# Credits
- [Cycle ORM](https://github.com/cycle), PHP DataMapper ORM and Data Modelling Engine by SpiralScout.
- [Spiral Scout](https://github.com/spiral), author of the Cycle ORM.
- [Spiral Framework Cycle Bridge](https://github.com/spiral/cycle-bridge/) for code samples, example of usage.
