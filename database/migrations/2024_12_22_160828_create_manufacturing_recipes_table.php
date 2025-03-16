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
        Schema::create('manufacturing_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('final_product_id')
                ->constrained('products')
                ->onDelete('cascade');
            $table->text('description')->nullable();
            $table->decimal('total_wastage_rate', 8, 2)->default(0);
            $table->decimal('final_quantity', 8, 2)->default(0);
            $table->foreignId('unit_id')->constrained('units')->nullable();
            $table->enum('production_cost_type', ['percentage', 'fixed'])->default('fixed');
            $table->decimal('production_cost_value', 8, 2)->default(0);
            $table->decimal('materials_cost', 10, 2)->default(0);
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('cascade');
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
        Schema::dropIfExists('manufacturing_recipes');
    }
};
