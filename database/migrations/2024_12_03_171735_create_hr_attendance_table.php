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
        Schema::create('user_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('date');
            $table->timestamp('clock_in')->nullable();
            $table->timestamp('clock_out')->nullable();
            $table->decimal('hours_worked', 10, 2)->nullable();
            $table->decimal('overtime_hours', 10, 2)->nullable();
            $table->enum('status', ['present', 'absent', 'late', 'half-day'])->default('absent');
            $table->text('notes')->nullable();
            $table->decimal('incentive_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hr_attendance');
    }
};
