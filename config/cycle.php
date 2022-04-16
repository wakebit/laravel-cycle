<?php

use Cycle\Database\Config\DatabaseConfig;
use Cycle\Migrations\Config\MigrationConfig;
use Spiral\Tokenizer\Config\TokenizerConfig;
use Wakebit\CycleBridge\Schema\Config\SchemaConfig;

return [
    'database' => new DatabaseConfig([
        'default' => 'default',

        'databases' => [
            'default' => [
                'connection' => env('DB_CONNECTION', 'mysql'),
            ],
        ],

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
    ]),

    'orm' => [
        'schema' => new SchemaConfig([
            'cache' => [
                'store' => env('CACHE_DRIVER', 'file'),
            ],
        ]),

        'tokenizer' => new TokenizerConfig([
            'directories' => [
                app_path(),
            ],

            'exclude' => [
            ],
        ]),
    ],

    'migrations' => new MigrationConfig([
        'directory' => database_path('migrations/cycle'),
        'table'     => 'cycle_migrations',
        'safe'      => env('APP_ENV') !== 'production',
    ]),
];
