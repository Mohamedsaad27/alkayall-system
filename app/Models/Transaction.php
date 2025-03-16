<?php

namespace App\Models;

use App\Traits\generalModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'transactions';
    public $guarded = [];

    const TYPE = [
        'opening_stock' => 1,
        'purchase' => 1,
        'sell' => -1,
        'spoiled_stock' => -1,
        'sell_return' => 1,
        'transfer' => -1,
        'purchase_return' => -1,
    ];

    public function getCreatedAt()
    {
        return $this->date_format($this->created_at);
    }

    public function TransactionFromReturnTransaction()
    {
        return $this->belongsTo(Transaction::class, 'return_transaction_id');
    }

    public function isSettle()
    {
        return $this->is_settle;
    }

    public function CreatedBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parentPurchase()
    {
        return $this->belongsTo(Transaction::class, 'return_transaction_id');
    }

    public function parentSell()
    {
        return $this->belongsTo(Transaction::class, 'return_transaction_id');
    }

    public function ReturnTransactions()
    {
        return $this->hasMany(Transaction::class, 'return_transaction_id');
    }

    public function TransactionSellLines()
    {
        return $this->hasMany(TransactionSellLine::class, 'transaction_id');
    }

    public function TransactionPurchaseLines()
    {
        return $this->hasMany(TransactionPurchaseLine::class, 'transaction_id');
    }

    public function purchaseUpdateHistories()
    {
        return $this->hasMany(PurchaseUpdateHistory::class);
    }

    public function sellUpdateHistories()
    {
        return $this->hasMany(SaleUpdateHistory::class);
    }

    public function PaymentTransaction()
    {
        return $this->hasOne(PaymentTransaction::class, 'transaction_id');
    }

    public function PaymentsTransaction()
    {
        return $this->hasMany(PaymentTransaction::class, 'transaction_id');
    }

    public function Branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function TransferLines()
    {
        return $this->hasMany(TransferLine::class, 'transaction_id');
    }

    public function branchTo()
    {
        return $this->belongsTo(Branch::class, 'branch_to_id');
    }

    public function warehouseTo()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_to_id');
    }

    public function Contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function SpoiledLines()
    {
        return $this->hasMany(SpoiledLine::class, 'transaction_id');
    }

    public function TransactionTaxes()
    {
        return $this->hasMany(TransactionTax::class, 'transaction_id');
    }

    public function manufacturingProductionLines()
    {
        return $this->hasMany(ManufacturingProductionLines::class, 'transaction_id');
    }
}
