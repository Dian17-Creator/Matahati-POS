<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('mpos_voucher')) {
            return;
        }

        Schema::table('mpos_voucher', function (Blueprint $table): void {
            $table->date('dend')->nullable()->change();
            $table->integer('nqty')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('mpos_voucher')) {
            return;
        }

        Schema::table('mpos_voucher', function (Blueprint $table): void {
            $table->date('dend')->nullable(false)->change();
            $table->integer('nqty')->nullable(false)->change();
        });
    }
};
