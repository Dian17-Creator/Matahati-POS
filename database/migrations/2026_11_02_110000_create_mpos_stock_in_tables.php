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
        if (!Schema::hasTable('mpos_stock_in_h')) {
            Schema::create('mpos_stock_in_h', function (Blueprint $table) {
                $table->id('nid');
                $table->string('cno_stock_in', 50)->unique();
                $table->dateTime('dstock_in');
                $table->unsignedBigInteger('nid_purchase')->nullable()->index();
                $table->unsignedBigInteger('nid_supplier')->nullable()->index();
                $table->unsignedBigInteger('nid_outlet')->index();
                $table->unsignedBigInteger('nid_user');
                $table->string('creceived_by', 255)->nullable();
                $table->string('cstatus', 20)->default('POSTED');
                $table->text('cnote')->nullable();
                $table->integer('ntotal_qty')->default(0);
                $table->decimal('ntotal_cost', 15, 2)->default(0);
                $table->dateTime('dcreated')->useCurrent();
                $table->dateTime('dupdated')->useCurrent()->useCurrentOnUpdate();

                $table->index('dstock_in');
            });
        }

        if (!Schema::hasTable('mpos_stock_in_d')) {
            Schema::create('mpos_stock_in_d', function (Blueprint $table) {
                $table->id('nid');
                $table->unsignedBigInteger('nid_stock_in')->index();
                $table->string('citem_type', 20)->default('PRODUCT');
                $table->unsignedBigInteger('nid_product')->nullable();
                $table->unsignedBigInteger('nid_ingredient')->nullable();
                $table->integer('nqty')->default(1);
                $table->decimal('ncost', 15, 2)->default(0);
                $table->decimal('nsubtotal', 15, 2)->default(0);
                $table->text('cserial_no')->nullable();
                $table->string('crack', 100)->nullable();
                $table->dateTime('dcreated')->useCurrent();

                $table->index(['nid_product', 'nid_ingredient']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mpos_stock_in_d');
        Schema::dropIfExists('mpos_stock_in_h');
    }
};

