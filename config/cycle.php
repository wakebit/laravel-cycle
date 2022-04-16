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
            'sqlite' => [
                'driver'  => \Cycle\Database\Driver\SQLite\SQLiteDriver::class,
                'options' => [
                    'connection' => 'sqlite::memory:',
                    'username'   => '',
                    'password'   => '',
                ],
            ],
            'mysql' => [
                'driver'  => \Cycle\Database\Driver\MySQL\MySQLDriver::class,
                'options' => [
                    'connection' => sprintf(
                        'mysql:host=%s;dbname=%s',
                        env('DB_HOST', '127.0.0.1'),
                        env('DB_DATABASE', 'forge')
                    ),
                    'username'   => env('DB_USERNAME', 'forge'),
                    'password'   => env('DB_PASSWORD', ''),
                ],
            ],
            'postgres'  => [
                'driver'  => \Cycle\Database\Driver\Postgres\PostgresDriver::class,
                'options' => [
                    'connection' => sprintf(
                        'pgsql:host=%s;dbname=%s',
                        env('DB_HOST', '127.0.0.1'),
                        env('DB_DATABASE', 'forge')
                    ),
                    'username'   => env('DB_USERNAME', 'forge'),
                    'password'   => env('DB_PASSWORD', ''),
                ],
            ],
            'sqlServer' => [
                'driver'  => \Cycle\Database\Driver\SQLServer\SQLServerDriver::class,
                'options' => [
                    'connection' => sprintf(
                        'sqlsrv:Server=%s;Database=%s',
                        env('DB_HOST', '127.0.0.1'),
                        env('DB_DATABASE', 'forge')
                    ),
                    'username'   => env('DB_USERNAME', 'forge'),
                    'password'   => env('DB_PASSWORD', ''),
                ],
            ],
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
