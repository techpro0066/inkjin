<?php

namespace App\Database;

use Illuminate\Support\Facades\Schema;

trait SafelyDropsTables
{
    /**
     * Drop tables during rollback without FK constraint errors.
     *
     * @param  string  ...$tables
     */
    protected function dropTablesSafely(string ...$tables): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }
}
