<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = [
        'name',
    ];

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_warehouse');
    }

    public function productBranchDetails()
    {
        return $this->hasMany(ProductBranchDetails::class);
    }
    public function TransactionSellLines()
    {
        return $this->hasMany(TransactionSellLine::class, 'warehouse_id');
    }
    public function TransactionPurchaseLines()
    {
        return $this->hasMany(TransactionPurchaseLine::class, 'warehouse_id');
    }

    public function TransferLines()
    {
        return $this->hasMany(TransferLine::class, 'transaction_id');
    }
    public function SpoiledLines()
    {
        return $this->hasMany(SpoiledLine::class, 'transaction_id');
    }
}
