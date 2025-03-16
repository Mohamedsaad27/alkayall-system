<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManufacturingProductionLines extends Model
{
    use HasFactory;

    protected $table = 'manufacturing_production_lines';

    protected $guarded = [];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function recipe()
    {
        return $this->belongsTo(ManufacturingRecipes::class, 'recipe_id');
    }

    public function productionQuantityUnit()
    {
        return $this->belongsTo(Unit::class, 'quantity_unit_id');
    }

    public function wastageRateUnit()
    {
        return $this->belongsTo(Unit::class, 'wastage_rate_unit_id');
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function ProductionLineIngredients()
    {
        return $this->hasMany(ProductionLineIngredients::class, 'production_line_id');
    }

    public function isEnded()
    {
        return $this->where('is_ended', 1);
    }
}
