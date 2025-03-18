<?php

namespace App\Models;

use App\Traits\helper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Contact extends Authenticatable implements JWTSubject
{
    use HasFactory, helper, Notifiable;

    protected $table = 'contacts';

    protected $fillable = [
        'name',
        'phone',
        'password',
        'email',
        'address',
        'type',
        'balance',
        'opening_balance',
        'is_active',
        'activity_type_id',
        'city_id',
        'governorate_id',
        'is_default',
        'credit_limit',
        'village_id',
        'sales_segment_id',
        'code',
        'contact_code',
        'contact_type',
        'latitude',
        'longitude',
        'remember_token',
        'verified_at',
    ];

    protected $hidden = [
        'remember_token',
        'password',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        $query->where('is_active', 1);
    }

    public function scopeCustomer($query)
    {
        $query->where('type', 'customer');
    }

    public function getCreatedAtAttribute()
    {
        return $this->date_format($this->attributes['created_at']);
    }

    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function salesSegment()
    {
        return $this->belongsTo(SalesSegment::class, 'sales_segment_id', 'id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'contact_id');
    }

    public function governorate()
    {
        return $this->belongsTo(Governorate::class, 'governorate_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function village()
    {
        return $this->belongsTo(Village::class, 'village_id');
    }

    public function getBranch()
    {
        $branch = DB::table('branchs')->where('governorate_id', $this->governorate_id)->first();

        return $branch;
    }

    public function activityType()
    {
        return $this->belongsTo(ActivityType::class, 'activity_type_id');
    }

    public function generateCode()
    {
        $this->contact_code = rand(1000, 9999);
        $this->save();
    }

    public function getCreditLimitAttribute($value)
    {
        // Remove trailing zeros if it's a decimal number
        if (strpos($value, '.') !== false) {
            $value = rtrim(rtrim($value, '0'), '.');
        }

        return $value;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'contact_user', 'contact_id', 'user_id');
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function getStatistics($date_from = null, $date_to = null)
    {
        $statistics = Transaction::where('contact_id', $this->id)
            ->with(['TransactionSellLines', 'TransactionPurchaseLines', 'PaymentsTransaction'])
            ->when($date_from && $date_to, function ($query) use ($date_from, $date_to) {
                $query->whereBetween('created_at', [$date_from, $date_to]);
            })
            ->get();
        $total_sales = $statistics->where('contact_id', $this->id)->where('type', 'sell')->where('status', 'final')->sum('final_price');

        $total_purchases = $statistics->where('contact_id', $this->id)->where('type', 'purchase')->where('status', 'final')->sum('final_price');

        $total_return_purchases = $statistics
            ->where('type', 'purchase_return')
            ->where('status', 'final')
            ->where('contact_id', $this->id)
            ->sum('final_price');

        $total_return_sales = $statistics
            ->where('type', 'sell_return')
            ->where('status', 'final')
            ->where('contact_id', $this->id)
            ->sum('final_price');

        $total_payments = $statistics->sum(function ($transaction) {
            return $transaction->PaymentsTransaction->sum('amount');
        });
        $total_discounts = $statistics->where('contact_id', $this->id)->sum('discount_value');
        $contact = Contact::find($this->id);
        $opening_balance = $contact->opening_balance;
        return [
            'opening_balance' => $opening_balance,
            'total_sales' => $total_sales,
            'total_purchases' => $total_purchases,
            'total_return_purchases' => $total_return_purchases,
            'total_return_sales' => $total_return_sales,
            'total_payments' => $total_payments,
            'total_discounts' => $total_discounts,
        ];
    }

    public function generateNewCode($sku)
    {
        if ($sku && !$this->where('sku', $sku)->exists()) {
            return $sku;
        }

        $maxSku = $this->selectRaw('MAX(CAST(code AS UNSIGNED)) as max_sku')->value('max_sku');

        if (is_null($maxSku)) {
            return 1;
        }

        return intval($maxSku) + 1;
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
