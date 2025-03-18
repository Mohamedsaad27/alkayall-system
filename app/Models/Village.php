<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Village extends Model
{
    protected $guarded = [];
    protected $table = 'villages';

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    use HasFactory;
}
