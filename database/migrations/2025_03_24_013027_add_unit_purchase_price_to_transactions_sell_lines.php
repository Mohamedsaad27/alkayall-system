<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions_sell_lines', function (Blueprint $table) {
            $table->decimal('unit_purchase_price', 15, 2)->nullable()->after('unit_price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions_sell_lines', function (Blueprint $table) {
            $table->dropColumn('unit_purchase_price');
        });
    }
};
