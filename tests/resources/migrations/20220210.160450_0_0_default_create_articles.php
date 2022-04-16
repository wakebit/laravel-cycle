<?php

/** @noinspection PhpIllegalPsrClassPathInspection */

use Cycle\Migrations\Migration;

class OrmDefaultF1d8224406ed3137f7c69875721c3745 extends Migration
{
    protected const DATABASE = 'default';

    public function up(): void
    {
        $this->table('articles')
            ->addColumn('id', 'primary', [
                'nullable' => false,
                'default'  => null,
            ])
            ->addColumn('title', 'string', [
                'nullable' => false,
                'default'  => null,
                'size'     => 255,
            ])
            ->setPrimaryKeys(['id'])
            ->create();
    }

    public function down(): void
    {
        $this->table('articles')->drop();
    }
}
