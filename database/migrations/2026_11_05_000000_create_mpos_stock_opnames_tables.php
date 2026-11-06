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
        if (!Schema::hasTable('mpos_stock_opname_h')) {
            Schema::create('mpos_stock_opname_h', function (Blueprint $table) {
                $table->id('nid');
                $table->string('cno_opname', 50)->unique();
                $table->date('dopname');
                $table->unsignedBigInteger('nid_outlet')->index();
                $table->unsignedBigInteger('nid_user');
                $table->string('cstatus', 20)->default('DRAFT');
                $table->text('cnote')->nullable();
                $table->decimal('ntotal_system_qty', 15, 2)->default(0);
                $table->decimal('ntotal_actual_qty', 15, 2)->default(0);
                $table->decimal('ntotal_diff_qty', 15, 2)->default(0);
                $table->decimal('ntotal_diff_amount', 15, 2)->default(0);
                $table->dateTime('dposted')->nullable();
                $table->dateTime('dcreated')->useCurrent();
                $table->dateTime('dupdated')->nullable()->useCurrentOnUpdate();

                $table->index(['nid_outlet', 'cstatus']);
                $table->index('dopname');
            });
        }

        if (!Schema::hasTable('mpos_stock_opname_d')) {
            Schema::create('mpos_stock_opname_d', function (Blueprint $table) {
                $table->id('nid');
                $table->unsignedBigInteger('nid_opname');
                $table->string('citem_type', 20)->default('PRODUK');
                $table->unsignedBigInteger('nid_product')->nullable();
                $table->unsignedBigInteger('nid_ingredient')->nullable();
                $table->decimal('nqty_system', 15, 2)->default(0);
                $table->decimal('nqty_actual', 15, 2)->default(0);
                $table->decimal('nqty_diff', 15, 2)->default(0);
                $table->decimal('ncost', 15, 2)->default(0);
                $table->decimal('nsubtotal_diff', 15, 2)->default(0);
                $table->date('dexpired')->nullable();
                $table->string('crack', 100)->nullable();
                $table->string('cnote', 255)->nullable();
                $table->time('dtime')->nullable();
                $table->dateTime('dcreated')->useCurrent();

                $table->foreign('nid_opname')
                    ->references('nid')
                    ->on('mpos_stock_opname_h')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');

                $table->index('nid_opname');
                $table->index('nid_product');
                $table->index('nid_ingredient');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mpos_stock_opname_d');
        Schema::dropIfExists('mpos_stock_opname_h');
    }
};

