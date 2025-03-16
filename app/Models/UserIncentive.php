<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserIncentive extends Model
{
    use HasFactory;
    protected $table = 'user_incentive';
    protected $fillable = ['user_id', 'amount', 'notes'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
