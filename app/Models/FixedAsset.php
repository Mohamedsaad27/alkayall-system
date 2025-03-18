<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAsset extends Model
{
    use HasFactory;

    protected $fillable = ['branch_id' , 'name' , 'price' , 'created_by' , 'status' , 'note'];
    protected $table = 'fixed_assets';

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function createdBy(){
        return $this->belongsTo(User::class,'created_by');
    }
}
