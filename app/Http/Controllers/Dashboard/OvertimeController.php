<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\User;
use App\Models\UserOverTime;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Http\Controllers\Controller;
use App\Services\ActivityLogsService;
use App\Notifications\OverTimeNotification;
use Illuminate\Support\Facades\Notification;

class OvertimeController extends Controller
{
    protected $ActivityLogsService;
    public function __construct(ActivityLogsService $activityLogsService)
    {
        $this->ActivityLogsService = $activityLogsService;
    }
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $overtimes = UserOverTime::where('hours','>',0)->with('user')->orderBy('id', 'desc');
            if ($request->user_id) {
                $overtimes = $overtimes->where('user_id', $request->user_id);
            }
            if ($request->date_from && $request->date_to) {
                $overtimes = $overtimes->whereBetween('created_at', [$request->date_from, $request->date_to]);
            }
            $totalAmount = $overtimes->sum('hours');
            return DataTables::of($overtimes)
                ->addColumn('action', function ($overtime) {
                    $btn = '<div class="btn-group"><button type="button" class="btn btn-success">'. trans('admin.Actions') .'</button><button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown"></button><div class="dropdown-menu" role="menu">';
                    $btn .= '<a class="dropdown-item fire-popup" data-url="' . route('dashboard.hr.overtime.edit', $overtime->id) . '"
                    href="#" data-toggle="modal" data-target="#modal-default">' . trans("admin.Edit") . '</a>';                    
                    $btn .= '<a class="dropdown-item delete-popup" href="#" data-toggle="modal" data-target="#modal-default" data-url="' . route('dashboard.hr.delete-overtime', $overtime->id) . '" >' . trans('admin.Delete') . '</a>';
                    $btn .= '</div></div>';
                    return $btn;
                })
                ->addColumn('user', function ($overtime) {
                    return $overtime->user->name;
                })
                ->addColumn('hours', function ($overtime) {
                    return $overtime->hours;
                })
                ->addColumn('notes', function ($overtime) {
                    return $overtime->notes;
                })
                ->addColumn('created_at', function ($overtime) {
                    return \Carbon\Carbon::parse($overtime->created_at)->format('Y-m-d');
                })
                ->rawColumns(['action', 'user', 'hours', 'notes', 'created_at'])
                ->with('total', $totalAmount)
                ->make(true);
        }
        $users = User::hrUsers()->get();
        return view('Dashboard.hr-module.over-time.index', compact('users'));
    }
    public function create()
    {
        $users = User::hrUsers()->get();
        return view('Dashboard.hr-module.over-time.create', compact('users'));
    }
    public function store(Request $request)
    {
        if ($request->has('add_overtime_for_all')) {
            $validatedData = $request->validate([
                'hours' => 'required|numeric|min:0',
                'notes' => 'nullable|string'
            ]);
            $overtime = $this->createOvertimeForAllUsers($validatedData);
            $users = User::hrUsers()->get();
            $users->push(auth()->user());
            Notification::send($users, new OverTimeNotification($overtime, 'create', 'تم إضافة ساعات إضافية بقيمة ' . $request->hours . ' للعاملين'));
            $this->ActivityLogsService->insert([
                'subject' => $overtime,
                'title' => 'تم إضافة ساعات إضافية لجميع العاملين',
                'description' => 'تم إضافة ساعات إضافية بقيمة ' . $request->hours . ' لجميع العاملين',
                'proccess_type' => 'over-time',
                'user_id' => auth()->id(),
            ]);
        } else {
            $validatedData = $request->validate([
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,id',
                'hours' => 'array',
                'hours.*' => 'nullable|numeric|min:0',
                'notes' => 'nullable|array',
                'notes.*' => 'nullable|string'
            ]);

            $overtime = $this->createOvertimeForSpecificUsers($validatedData);
            $users = User::whereIn('id', $validatedData['user_ids'])->get();
            $users->push(auth()->user());
            Notification::send($users, new OverTimeNotification($overtime, 'create', 'تم إضافة ساعات إضافية بقيمة ' . implode(', ', $request->hours) . ' للعاملين ' . $users->implode('name', ', ')));

            $this->ActivityLogsService->insert([
                'subject' => $overtime,
                'title' => 'تم إضافة ساعات إضافية  ',
                'description' => 'تم إضافة ساعات إضافية بقيمة ' . implode(', ', $request->hours) . ' للعاملين ' . $users->implode('name', ', ') . ' في تاريخ ' . \Carbon\Carbon::parse($overtime->created_at)->format('Y-m-d'),
                'proccess_type' => 'over-time',
                'user_id' => auth()->id(),
            ]);
        }

        return redirect()->route('dashboard.hr.overtime.index')
            ->with('success', trans('admin.Overtime created successfully'));
    }

    private function createOvertimeForAllUsers($validatedData)
    {
        $users = User::hrUsers()->get();
        foreach ($users as $user) {
            $userOvertime = UserOverTime::create([
                'user_id' => $user->id,
                'hours' => $validatedData['hours'],
                'notes' => $validatedData['notes'],
            ]);
        }
        return $userOvertime;
    }

    private function createOvertimeForSpecificUsers($validatedData)
    {
        $userIds = $validatedData['user_ids'];
        foreach ($userIds as $userId) {
            $user = User::find($userId);
            $userOvertime = UserOverTime::create([
                'user_id' => $userId,
                'hours' => $validatedData['hours'][$userId] ?? $user->userOvertime->hours ?? 0,
                'notes' => $validatedData['notes'][$userId] ?? null,
            ]);
        }
        return $userOvertime;
    }
    public function edit(UserOverTime $overtime)
    {
        return [
            'title' => trans('admin.Edit') . ' ساعات إضافية',
            'body'  => view('Dashboard.hr-module.over-time.edit')->with([
                'overtime' => $overtime,
            ])->render(),
        ];
    }
    public function update(Request $request, UserOverTime $overtime)
    {
        $validatedData = $request->validate([
            'hours' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);
        $overtime->update($validatedData);
        $users = User::where('id', $overtime->user_id)->get();
        $users->push(auth()->user());
        Notification::send($users, new OverTimeNotification($overtime, 'update', 'تم تعديل ساعات إضافية بقيمة ' . $request->hours . ' للعامل ' . $overtime->user->name));
        $this->ActivityLogsService->insert([
            'subject' => $overtime,
            'title' => 'تم تعديل ساعات إضافية',
            'description' => 'تم تعديل ساعات إضافية بقيمة ' . $request->hours . ' للعامل ' . $overtime->user->name,
            'proccess_type' => 'over-time',
            'user_id' => auth()->id(),
        ]);
        return redirect()->route('dashboard.hr.overtime.index')->with('success', trans('admin.Overtime updated successfully'));
    }
    public function destroy($overtimeId){
        $overtime = UserOverTime::find($overtimeId);
        $user = User::find($overtime->user_id);
        $notifiedUser[] = auth()->user();
        $notifiedUser[] = $user;
        Notification::send($notifiedUser, new OverTimeNotification($overtime, 'delete', 'تم حذف ساعات إضافية بقيمة ' . $overtime->hours . ' للعامل ' . $overtime->user->name));
        $this->ActivityLogsService->insert([
            'subject' => $overtime,
            'title' => 'تم حذف ساعات إضافية',
            'description' => 'تم حذف ساعات إضافية بقيمة ' . $overtime->hours . ' للعامل ' . $overtime->user->name,
            'proccess_type' => 'over-time',
            'user_id' => auth()->id(),
        ]);
        $overtime->delete();
        return redirect()->route('dashboard.hr.overtime.index')->with('success', trans('admin.Overtime deleted successfully'));
    }
}
