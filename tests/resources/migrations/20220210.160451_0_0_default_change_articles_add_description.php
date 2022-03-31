<?php

/** @noinspection PhpIllegalPsrClassPathInspection */

use Spiral\Migrations\Migration;

class OrmDefaultF1d8224406ed3137f7c69875721c3746 extends Migration
{
    protected const DATABASE = 'default';

    public function up(): void
    {
        $this->table('articles')
            ->addColumn('description', 'string', [
                'nullable' => false,
                'default'  => null,
                'size'     => 255,
            ])
            ->update();
    }

    public function down(): void
    {
        $this->table('articles')
            ->dropColumn('description')
            ->update();
    }
}
