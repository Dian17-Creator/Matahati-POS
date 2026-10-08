<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE mpos_product_price DROP FOREIGN KEY fk_product_price_category');
        DB::statement('ALTER TABLE mpos_product_price CHANGE nid_price_category nid_cust_type INT(11) NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE mpos_product_price CHANGE nid_cust_type nid_price_category INT(11) NOT NULL');
    }
};
