<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserAttendance extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'user_attendances';
    protected $guarded = [];
    protected $dates = [
        'date',
        'clock_in',
        'clock_out',
        'late_time',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
    public function scopeUserBranch($query, $branchId)
    {
        return $query->whereHas('user', function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        });
    }
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    // Calculate total hours worked
    public function calculateHoursWorked()
    {
        if ($this->clock_in && $this->clock_out) {
            return $this->clock_in->diffInHours($this->clock_out);
        }
        return 0;
    }
    public function hasAttendanceToday($userId)
    {
        return UserAttendance::where('user_id', $userId)
            ->where('date', Carbon::now()->format('Y-m-d'))
            ->exists();
    }
    // Calculate salary based on hours worked and hourly rate
    public function calculateSalary(User $user)
    {
        $lateTime = $this->late_time;
        $hoursWorked = $this->hours_worked;
        $overtimeHours = $user->overtimes->sum('hours');
        $incentivesTotal = $user->incentives->sum('amount');
        $discountsTotal = $user->discounts->sum('amount');
        return ($hoursWorked * $user->hour_price) + ($overtimeHours * $user->overtime_hour_price) + $incentivesTotal - $discountsTotal ;
    }
}
