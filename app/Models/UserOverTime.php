<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOverTime extends Model
{
    use HasFactory;
    protected $table = 'user_overtime';
    protected $fillable = ['user_id', 'hours', 'notes'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
