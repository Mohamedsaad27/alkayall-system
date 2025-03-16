<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class UserAttendanceController extends Controller
{
    public function index()
    {
        return view('Dashboard.hr-module.attendance.index');
    }

    public function create(Request $request)
    {
        if ($request->has('users')) {
            $userIds = explode(',', $request->input('users'));
            $users = User::hrUsers()->whereIn('id', $userIds)->get();
        } else {
            $users = User::hrUsers()->get();
        }

        return view('Dashboard.hr-module.attendance.create', compact('users'));
    }

    public function store(Request $request)
    {
        // dd($request->all());

        $validatedData = $request->validate([
            'attendance_date' => 'nullable|date',
            'user_ids' => 'required|array',
            'user_ids.*' => 'required|exists:users,id',
            'status' => 'required|array',
            'status.*' => 'required|in:present,absent,late,half-day',
            'clock_in' => 'nullable|array',
            'clock_in.*' => 'nullable|date_format:H:i',
            'clock_out' => 'nullable|array',
            'clock_out.*' => 'nullable|date_format:H:i|after:clock_in.*',
            'notes' => 'nullable|array',
            'notes.*' => 'nullable|string',
            'all_present' => 'nullable'
        ]);
        $today = Carbon::now()->format('Y-m-d');
        $userIdsToRecord = array_filter($request->user_ids, function ($userId) use ($today) {
            return !UserAttendance::where('user_id', $userId)
                ->where('date', $today)
                ->exists();
        });
        if (empty($userIdsToRecord)) {
            return redirect()
                ->back()
                ->with('error', trans('admin.all_users_already_have_attendance'));
        }

        // Update the request with filtered user IDs
        $request->merge(['user_ids' => $userIdsToRecord]);
        if ($request->all_present === null) {
            $attendanceRecords = $this->createDefaultAttendanceRecords($request->user_ids);
            UserAttendance::insert($attendanceRecords);
            return redirect()->back()->with('success', trans('admin.attendance_recorded_successfully'));
        }

        $hasChanges = $this->checkForAttendanceChanges($request);

        if (!$hasChanges) {
            return redirect()
                ->back()
                ->with('error', trans('admin.no_attendance_changes'));
        }
        try {
            DB::beginTransaction();

            $attendanceRecords = $this->createDetailedAttendanceRecords($request);

            UserAttendance::insert($attendanceRecords);

            DB::commit();

            return redirect()->back()->with('success', trans('admin.attendance_recorded_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function createDefaultAttendanceRecords($userIds)
    {
        $attendanceRecords = [];
        $today = Carbon::now()->format('Y-m-d');

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            $clockIn = Carbon::createFromFormat('H:i:s', Carbon::parse($user->presence_time)->format('H:i:s'));
            $clockOut = Carbon::createFromFormat('H:i:s', Carbon::parse($user->leave_time)->format('H:i:s'));
            $totalMinutes = $clockIn->diffInMinutes($clockOut);
            $hoursWorked = $totalMinutes / 60;

            $attendanceRecords[] = [
                'user_id' => $userId,
                'date' => $today,
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'status' => 'present',
                'hours_worked' => round($hoursWorked, 2),
                'overtime_hours' => 0,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        return $attendanceRecords;
    }

    private function createDetailedAttendanceRecords(Request $request)
    {
        $attendanceRecords = [];
        $today = Carbon::now()->format('Y-m-d');

        foreach ($request->user_ids as $userId) {
            $user = User::findOrFail($userId);
            $status = $request->status[$userId] ?? 'present';

            $presenceTime = Carbon::createFromFormat('H:i:s', Carbon::parse($user->presence_time)->format('H:i:s'));
            $leaveTime = Carbon::createFromFormat('H:i:s', Carbon::parse($user->leave_time)->format('H:i:s'));
            // dd($presenceTime, $leaveTime);
            $clockIn = null;
            $clockOut = null;
            $hoursWorked = 0;
            $lateTime = 0;
            $overtimeHours = 0;

            if ($status !== 'absent') {
                if (!empty($request->clock_in[$userId])) {
                    $clockIn = Carbon::parse($today . ' ' . $request->clock_in[$userId]);
                } else {
                    $clockIn = $presenceTime;
                }

                if (!empty($request->clock_out[$userId])) {
                    $clockOut = Carbon::parse($today . ' ' . $request->clock_out[$userId]);
                } else {
                    $clockOut = $leaveTime;
                }

                switch ($status) {
                    case 'present':
                        $hoursWorked = $clockIn->floatDiffInHours($clockOut);
                        if ($clockIn->gt($presenceTime)) {
                            $lateTime = $presenceTime->floatDiffInHours($clockIn);
                        }
                        $expectedHours = $presenceTime->floatDiffInHours($leaveTime);
                        $overtimeHours = max(0, $hoursWorked - $expectedHours);
                        break;
                        
                        case 'late':
                            $hoursWorked = $clockIn->floatDiffInHours($clockOut);
                            $lateTime = $presenceTime->floatDiffInHours($clockIn);
                            $status = 'present';
                            
                            $expectedHours = $presenceTime->floatDiffInHours($leaveTime);
                            $overtimeHours = max(0, $hoursWorked - $expectedHours);
                            break;
                            
                            case 'half-day':
                                $normalHours = $presenceTime->floatDiffInHours($leaveTime);
                                $hoursWorked = $normalHours / 2;
                                $clockOut = $clockIn->copy()->addHours($hoursWorked);
                                $status = 'present';
                        break;
                }
            }

            $attendanceRecords[] = [
                'user_id' => $userId,
                'date' => $today,
                'clock_in' => $clockIn ? $clockIn : null,
                'clock_out' => $clockOut ? $clockOut : null,
                'status' => $status,
                'hours_worked' => round($hoursWorked, 2),
                'late_time' => round($lateTime, 2),
                'overtime_hours' => round($overtimeHours, 2),
                'notes' => $request->notes[$userId] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        return $attendanceRecords;
    }

    private function checkForAttendanceChanges(Request $request): bool
    {
        $hasNonDefaultStatus = collect($request->status)
            ->contains(fn($status) => $status !== 'present');

        $hasDetailedEntries = collect($request->user_ids)->some(function ($userId) use ($request) {
            return ($request->status[$userId] ?? 'present') !== 'present' ||
                !empty($request->clock_in[$userId]) ||
                !empty($request->clock_out[$userId]) ||
                !empty($request->notes[$userId]);
        });

        return $hasNonDefaultStatus || $hasDetailedEntries;
    }
}
