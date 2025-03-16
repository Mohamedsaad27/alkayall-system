<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'units';
    protected $guarded = [];

    public function products()
    {
        return $this->hasMany(Product::class, 'unit_id');
    }

    public function productUnitDetails()
    {
        return $this->hasMany(ProductUnitDetails::class, 'unit_id');
    }

    public function productPriceHistory()
    {
        return $this->hasMany(ProductPriceHistory::class, 'unit_id');
    }

    public function manufacturingRecipes()
    {
        return $this->hasMany(ManufacturingRecipe::class, 'unit_id');
    }

    public function manufacturingLines()
    {
        return $this->hasMany(ManufacturingRecipeIngredients::class, 'unit_id');
    }

    public function ProductionLineIngredients()
    {
        return $this->hasMany(ProductionLineIngredients::class, 'unit_id');
    }

    public function getUnit()
    {
        if ($this->Unit)
            return $this->Unit->actual_name;

        return '';
    }

    public function ProductionQuantity()
    {
        return $this->hasMany(ManufacturingProductionLines::class, 'quantity_unit_id');
    }

    public function WastageQuantity()
    {
        return $this->hasMany(ManufacturingProductionLines::class, 'wastage_rate_unit_id');
    }

    public function scopeMain($query)
    {
        $query->where('base_unit_id', null);
    }

    public function scopeActive($query)
    {
        $query;
    }

    public function getMultiplier()
    {
        if (!$this->base_unit_multiplier)
            return 1;

        if ($this->base_unit_is_largest == 1)
            return 1 / $this->base_unit_multiplier;

        if ($this->base_unit_is_largest == 0)
            return 1 * $this->base_unit_multiplier;

        return 1;
    }
}
