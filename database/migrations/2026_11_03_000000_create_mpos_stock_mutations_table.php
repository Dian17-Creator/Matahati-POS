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
        if (!Schema::hasTable('mpos_stock_mutations')) {
            Schema::create('mpos_stock_mutations', function (Blueprint $table) {
                $table->id('nid');
                $table->string('cno_ref', 50)->index();
                $table->dateTime('dmutation');
                $table->string('ctype', 10)->default('IN');
                $table->unsignedBigInteger('nid_outlet')->index();
                $table->unsignedBigInteger('nid_target_outlet')->nullable()->index();
                $table->unsignedBigInteger('nid_user');
                $table->string('citem_type', 20)->default('PRODUCT');
                $table->unsignedBigInteger('nid_product')->nullable();
                $table->unsignedBigInteger('nid_ingredient')->nullable();
                $table->decimal('nqty', 12, 2)->default(1);
                $table->decimal('ncost', 15, 2)->default(0);
                $table->decimal('nsubtotal', 15, 2)->default(0);
                $table->string('creason', 100)->nullable();
                $table->string('cstatus', 20)->default('POSTED');
                $table->text('cnote')->nullable();
                $table->date('dexpired')->nullable();
                $table->string('crack', 100)->nullable();
                $table->dateTime('dcreated')->useCurrent();
                $table->dateTime('dupdated')->useCurrent()->useCurrentOnUpdate();

                $table->index(['ctype', 'dmutation']);
                $table->index(['nid_product', 'nid_ingredient']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mpos_stock_mutations');
    }
};

