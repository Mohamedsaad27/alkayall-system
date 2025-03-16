<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Models\UserAttendance;
use App\Models\UserDiscount;
use App\Models\UserIncentive;
use App\Models\UserOverTime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class HumanResourceController extends Controller
{
    public function index()
    {
        $branches = Branch::active()->get();
        $date_from = request('date_from', date('Y-m-d'));
        $date_to = request('date_to', date('Y-m-d'));
        $branch_id = request('branch_id');
        $attendancesQuery = UserAttendance::whereBetween('date', [$date_from, $date_to]);
        if ($branch_id) {
            $attendancesQuery->whereHas('user', function ($query) use ($branch_id) {
                $query->where('branch_id', $branch_id);
            });
        }
        $attendances = $attendancesQuery
            ->selectRaw('
        user_id,
        COUNT(*) as total_days,
        SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as total_present,
        SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as total_absent,
        Sum(Case WHEN status = "half-day" THEN 1 ELSE 0 END ) as total_half_day,
        SUM(hours_worked) as total_hours,
        SUM(late_time) as total_late_time
    ')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $usersQuery = User::hrUsers()->with('mainBranch');
        if ($branch_id) {
            $usersQuery->where('branch_id', $branch_id);
        }
        $users = $usersQuery->get();
        return view('Dashboard.hr-module.index', compact(
            'branches',
            'attendances',
            'users',
            'date_from',
            'date_to',
            'branch_id'
        ));
    }

    public function attendanceReport(Request $request)
    {
        if ($request->ajax()) {
            $userAttendances = UserAttendance::with(['user', 'user.mainBranch'])
                ->select('id', 'user_id', 'date', 'clock_in', 'clock_out');

            // Apply branch filter
            if ($request->branch_id) {
                $userAttendances->whereHas('user', function ($query) use ($request) {
                    $query->where('branch_id', $request->branch_id);
                });
            }

            // Apply user filter
            if ($request->user_id) {
                $userAttendances->where('user_id', $request->user_id);
            }

            // Apply date range filter
            if ($request->date_from && $request->date_to) {
                $userAttendances->whereBetween('date', [$request->date_from, $request->date_to]);
            }

            // Get the filtered results
            $userAttendances = $userAttendances->get();

            return DataTables::of($userAttendances)
                ->addColumn('user', function ($userAttendance) {
                    return $userAttendance->user ? $userAttendance->user->name : 'N/A';
                })
                ->addColumn('branch', function ($userAttendance) {
                    return $userAttendance->user && $userAttendance->user->mainBranch
                        ? $userAttendance->user->mainBranch->name
                        : 'N/A';
                })
                ->addColumn('date', function ($userAttendance) {
                    return $userAttendance->date ? Carbon::parse($userAttendance->date)->format('Y-m-d') : 'N/A';
                })
                ->addColumn('clock_in', function ($userAttendance) {
                    return $userAttendance->clock_in ? Carbon::parse($userAttendance->clock_in)->format('h:i A') : 'Absence';
                })
                ->addColumn('clock_out', function ($userAttendance) {
                    return $userAttendance->clock_out ? Carbon::parse($userAttendance->clock_out)->format('h:i A') : 'Absence';
                })
                ->rawColumns(['user', 'branch', 'date', 'clock_in', 'clock_out'])
                ->make(true);
        }

        $branches = Branch::active()->get();
        $users = User::hrUsers()->with('mainBranch')->get();
        return view('Dashboard.hr-module.attendance-report', compact('branches', 'users'));
    }
}
