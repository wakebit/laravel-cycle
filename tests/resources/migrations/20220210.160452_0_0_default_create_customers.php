<?php

/** @noinspection PhpIllegalPsrClassPathInspection */

use Spiral\Migrations\Migration;

class OrmDefaultF1d8224406ed3137f7c69875721c3747 extends Migration
{
    protected const DATABASE = 'default';

    public function up(): void
    {
        $this->table('customers')
            ->addColumn('id', 'primary', [
                'nullable' => false,
                'default'  => null,
            ])
            ->addColumn('name', 'string', [
                'nullable' => false,
                'default'  => null,
                'size'     => 255,
            ])
            ->setPrimaryKeys(['id'])
            ->create();
    }

    public function down(): void
    {
        $this->table('customers')->drop();
    }
}
