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
        Schema::create('manufacturing_recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')
                ->constrained('manufacturing_recipes')
                ->onDelete('cascade');
            $table->foreignId('raw_material_id')
                ->constrained('products')
                ->onDelete('cascade');
                $table->decimal('wastage_rate', 8, 2)->default(0);
            $table->decimal('quantity', 8, 2)->default(0);
            $table->foreignId('unit_id')
                ->constrained('units')
                ->onDelete('cascade');
            $table->decimal('raw_material_price', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('manufacturing_recipe_ingredients');
    }
};
