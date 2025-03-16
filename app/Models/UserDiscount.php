<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDiscount extends Model
{
    use HasFactory;
    protected $table = 'user_discounts';
    protected $fillable = ['user_id', 'amount', 'notes'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
