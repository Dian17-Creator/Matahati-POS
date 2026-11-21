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
        if (! Schema::hasTable('mpos_voucher') || Schema::hasColumn('mpos_voucher', 'nid_outlet')) {
            return;
        }

        Schema::table('mpos_voucher', function (Blueprint $table): void {
            $table->integer('nid_outlet')->nullable()->after('nid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('mpos_voucher') || ! Schema::hasColumn('mpos_voucher', 'nid_outlet')) {
            return;
        }

        Schema::table('mpos_voucher', function (Blueprint $table): void {
            $table->dropColumn('nid_outlet');
        });
    }
};
