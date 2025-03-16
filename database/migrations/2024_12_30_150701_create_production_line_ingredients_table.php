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
        Schema::create('production_line_ingredients', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('production_line_id')
                ->constrained('manufacturing_production_lines')
                ->onDelete('cascade');
            $table
                ->foreignId('raw_material_id')
                ->constrained('products')
                ->onDelete('cascade');
            $table->decimal('quantity', 10, 2)->default(0);
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
        Schema::dropIfExists('production_line_ingredients');
    }
};
