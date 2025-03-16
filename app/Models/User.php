<?php

namespace App\Models;

use App\Models\Scopes\GetBranchByUser;
use App\Traits\helper;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laratrust\Traits\LaratrustUserTrait;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use helper, Notifiable;
    use LaratrustUserTrait;
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'users';

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'verified_at' => 'datetime',
        'presence_time' => 'datetime:H:i',
        'leave_time' => 'datetime:H:i',
    ];

    public function salaryHistories()
    {
        return $this->hasMany(SalaryHistory::class);
    }

    public function mainBranch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function Branches()
    {
        return $this->belongsToMany(Branch::class, 'user_branch', 'user_id', 'branch_id');
    }

    // relations
    public function Image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }

    public function Activity_logs()
    {
        return $this->hasMany(Activity_log::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'created_by');
    }

    public function PriceHistories()
    {
        return $this->hasMany(ProductPriceHistory::class);
    }

    //
    public function getImage()
    {
        if ($this->Image != null) {
            return url('uploads/users/' . $this->Image->src);
        } else {
            return url('uploads/users/default.jpg');
        }
    }

    public function getRole()
    {
        if (count($this->roles) > 0) {
            return $this->roles[0]->name;
        } else {
            return null;
        }
    }

    public function taxRates()
    {
        return $this->hasMany(TaxRate::class);
    }

    public function getRoleId()
    {
        if (count($this->roles) > 0) {
            return $this->roles[0]->id;
        } else {
            return null;
        }
    }

    public function getCreatedAtAttribute()
    {
        return $this->date_format($this->attributes['created_at']);
    }

    public function has_permission($permission)
    {
        if ($this->super == 1)
            return true;

        if ($this->isAbleTo($permission))
            return true;

        return false;
    }

    public function attendances()
    {
        return $this->hasMany(UserAttendance::class);
    }

    public function scopeHrUsers($query)
    {
        $hr_users = User::whereNotNull('payment_method')->get();
        return $query->whereIn('id', $hr_users->pluck('id'));
    }

    public function discounts()
    {
        return $this->hasMany(UserDiscount::class);
    }

    public function incentives()
    {
        return $this->hasMany(UserIncentive::class);
    }

    public function overtimes()
    {
        return $this->hasMany(UserOverTime::class);
    }

    public function manufacturingRecipes()
    {
        return $this->hasMany(ManufacturingRecipes::class,'created_by');
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'type' => 'user_api'
        ];
    }
}
