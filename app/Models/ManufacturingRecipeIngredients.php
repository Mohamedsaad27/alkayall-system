<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManufacturingRecipeIngredients extends Model
{
    use HasFactory;

    protected $table = 'manufacturing_recipe_ingredients';
    protected $guarded = [];

    public function rawMaterial()
    {
        return $this->belongsTo(Product::class, 'raw_material_id');
    }

    public function recipe()
    {
        return $this->belongsTo(ManufacturingRecipes::class, 'recipe_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function getRawMaterialPrice()
    {
        return $this->product->getPurchasePriceByUnit($this->unit_id);
    }
}
