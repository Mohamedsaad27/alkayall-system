<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('payment_method', ['monthly', 'weekly', 'daily'])->nullable();
            $table->decimal('working_hours_count', 10, 2)->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->integer('vacation_days_count')->nullable();
            $table->decimal('hour_price', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
