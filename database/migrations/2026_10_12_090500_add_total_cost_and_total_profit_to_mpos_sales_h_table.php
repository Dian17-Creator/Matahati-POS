<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Kolom ntotal_cost dan ntotal_profit dibuat secara manual oleh user pada mpos_sales_h.
     */
    public function up(): void
    {
        // No-op: kolom dibuat manual oleh user.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
