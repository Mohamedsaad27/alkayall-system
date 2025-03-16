<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionLineIngredients extends Model
{
    use HasFactory;

    protected $table = 'production_line_ingredients';
    protected $guarded = [];

    public function productionLine()
    {
        return $this->belongsTo(ManufacturingProductionLines::class, 'production_line_id');
    }

    public function rawMaterial()
    {
        return $this->belongsTo(Product::class, 'raw_material_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
