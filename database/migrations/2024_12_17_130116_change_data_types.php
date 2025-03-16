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
            $table->time('presence_time')->nullable()->change();
            $table->time('leave_time')->nullable()->change();
        });
        Schema::table('user_attendances', function (Blueprint $table) {
            $table->time('clock_in')->nullable()->change();
            $table->time('clock_out')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
