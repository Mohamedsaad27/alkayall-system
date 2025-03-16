<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ManufacturingRecipes extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'manufacturing_recipes';
    protected $guarded = [];

    public function finalProduct()
    {
        return $this->belongsTo(Product::class, 'final_product_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ingredients()
    {
        return $this->hasMany(ManufacturingRecipeIngredients::class, 'recipe_id');
    }

    public function ManufactringproductionLine()
    {
        return $this->hasMany(ManufacturingProductionLines::class, 'recipe_id');
    }

    public function hasEndedProductionLine()
    {
        return $this->ManufactringproductionLine()->where('is_ended', 1)->exists();
    }
}
