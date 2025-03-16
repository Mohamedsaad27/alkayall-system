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
        Schema::create('manufacturing_production_lines', function (Blueprint $table) {
            $table->id();
            $table->string('production_line_code', 50);
            $table->date('date');
            $table->foreignId('branch_id')->constrained('branchs')->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained('manufacturing_recipes')->cascadeOnDelete();
            $table->decimal('production_quantity', 8, 2)->default(0);
            $table->foreignId('quantity_unit_id')->constrained('units')->cascadeOnDelete();
            $table->enum('production_cost_type', ['percentage', 'fixed'])->default('fixed');
            $table->decimal('production_cost_value', 8, 2)->default(0);
            $table->decimal('wastage_rate', 8, 2)->default(0);
            $table->foreignId('wastage_rate_unit_id')->constrained('units')->cascadeOnDelete();
            $table->decimal('production_total_cost', 10, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('manufacturing_production_lines');
    }
};
