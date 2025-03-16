<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\UserIncentive;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\ActivityLogsService;
use Yajra\DataTables\Facades\DataTables;
use App\Notifications\IncentiveNotification;
use Illuminate\Support\Facades\Notification;

class IncentiveController extends Controller
{
    protected $ActivityLogsService;
    public function __construct(ActivityLogsService $activityLogsService)
    {
        $this->ActivityLogsService = $activityLogsService;
    }
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $incentives = UserIncentive::where('amount', '>', 0)->with('user')->orderBy('id', 'desc');
            if ($request->user_id) {
                $incentives = $incentives->where('user_id', $request->user_id);
            }
            if ($request->date_from && $request->date_to) {
                $incentives = $incentives->whereBetween('created_at', [$request->date_from, $request->date_to]);
            }
            $totalAmount = $incentives->sum('amount');
            return DataTables::of($incentives)
                ->addColumn('action', function ($incentive) {
                    $btn = '<div class="btn-group"><button type="button" class="btn btn-success">' . trans('admin.Actions') . '</button><button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown"></button><div class="dropdown-menu" role="menu">';
                    $btn .= '<a class="dropdown-item fire-popup" data-url="' . route('dashboard.hr.incentive.edit', $incentive->id) . '"
                href="#" data-toggle="modal" data-target="#modal-default">' . trans("admin.Edit") . '</a>';
                    $btn .= '<a class="dropdown-item delete-popup" href="#" data-toggle="modal" data-target="#modal-default" data-url="' . route('delete-incentive', $incentive->id) . '" >' . trans('admin.Delete') . '</a>';
                    $btn .= '</div></div>';
                    return $btn;
                })
                ->addColumn('user', function ($incentive) {
                    return $incentive->user->name;
                })
                ->addColumn('amount', function ($incentive) {
                    return $incentive->amount;
                })
                ->addColumn('notes', function ($incentive) {
                    return $incentive->notes;
                })
                ->addColumn('created_at', function ($incentive) {
                    return \Carbon\Carbon::parse($incentive->created_at)->format('Y-m-d');
                })
                ->with('total', $totalAmount)
                ->rawColumns(['action', 'user', 'amount', 'notes', 'created_at'])
                ->make(true);
        }
        $users = User::hrUsers()->get();
        return view('Dashboard.hr-module.incentives.index', compact('users'));
    }
    public function create()
    {
        $users = User::hrUsers()->get();
        return view('Dashboard.hr-module.incentives.create', compact('users'));
    }
    public function store(Request $request)
    {
        if ($request->has('add_incentive_for_all')) {
            $validatedData = $request->validate([
                'amount' => 'required|numeric|min:0',
                'notes' => 'nullable|string'
            ]);
            $incentive = $this->createIncentiveForAllUsers($validatedData);
            $users = User::hrUsers()->get();
            $users->push(auth()->user());
            Notification::send($users, new IncentiveNotification($incentive, 'create', 'تم إضافة حوافز جديدة بقيمة ' . $request->amount . ' للعاملين'));
            $this->ActivityLogsService->insert([
                'subject' => $incentive,
                'title' => 'تم إضافة حوافز جديدة لجميع العاملين',
                'description' => 'تم إضافة حوافز جديدة بقيمة ' . $request->amount . ' لجميع العاملين',
                'proccess_type' => 'incentive',
                'user_id' => auth()->id(),
            ]);
        } else {
            $validatedData = $request->validate([
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,id',
                'amount' => 'array',
                'amount.*' => 'nullable|numeric|min:0',
                'notes' => 'nullable|array',
                'notes.*' => 'nullable|string'
            ]);

            $incentive = $this->createIncentiveForSpecificUsers($validatedData);
            $users = User::whereIn('id', $validatedData['user_ids'])->get();
            $users->push(auth()->user());
            Notification::send($users, new IncentiveNotification($incentive, 'create', 'تم إضافة حوافز جديدة بقيمة ' . implode(', ', $request->amount) . ' للعاملين ' . $users->implode('name', ', ')));

            $this->ActivityLogsService->insert([
                'subject' => $incentive,
                'title' => 'تم إضافة حوافز جديدة  ',
                'description' => 'تم إضافة حوافز جديدة بقيمة ' . implode(', ', $request->amount) . ' للعاملين ' . $users->implode('name', ', ') . ' في تاريخ ' . \Carbon\Carbon::parse($incentive->created_at)->format('Y-m-d'),
                'proccess_type' => 'incentive',
                'user_id' => auth()->id(),
            ]);
        }

        return redirect()->route('dashboard.hr.incentive.index')
            ->with('success', trans('admin.Incentive created successfully'));
    }

    private function createIncentiveForAllUsers($validatedData)
    {
        $users = User::hrUsers()->get();
        foreach ($users as $user) {
            $userIncentive = UserIncentive::create([
                'user_id' => $user->id,
                'amount' => $validatedData['amount'],
                'notes' => $validatedData['notes'],
            ]);
        }
        return $userIncentive;
    }

    private function createIncentiveForSpecificUsers($validatedData)
    {
        $userIds = $validatedData['user_ids'];
        foreach ($userIds as $userId) {
            $user = User::find($userId);
            $userIncentive = UserIncentive::create([
                'user_id' => $userId,
                'amount' => $validatedData['amount'][$userId] ?? $user->userIncentive->amount ?? 0,
                'notes' => $validatedData['notes'][$userId] ?? null,
            ]);
        }
        return $userIncentive;
    }
    public function edit(UserIncentive $incentive)
    {
        return [
            'title' => trans('admin.Edit') . ' حوافز',
            'body' => view('Dashboard.hr-module.incentives.edit')->with([
                'incentive' => $incentive,
            ])->render(),
        ];
    }
    public function update(Request $request, UserIncentive $incentive)
    {
        $validatedData = $request->validate([
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);
        $incentive->update($validatedData);
        Notification::send($incentive->user, new IncentiveNotification($incentive, 'update', 'تم تعديل الحوافز بقيمة ' . $validatedData['amount'] . ' للعامل ' . $incentive->user->name));
        $this->ActivityLogsService->insert([
            'subject' => $incentive,
            'title' => 'تم تعديل الحوافز',
            'description' => 'تم تعديل الحوافز بقيمة ' . $validatedData['amount'] . ' في تاريخ ' . \Carbon\Carbon::parse($incentive->created_at)->format('Y-m-d') . ' للعامل ' . $incentive->user->name,
            'proccess_type' => 'incentive',
            'user_id' => auth()->id(),
        ]);
        return redirect()->route('dashboard.hr.incentive.index')->with('success', trans('admin.Incentive updated successfully'));
    }
    public function destroy($incentiveId)
    {
        $incentive = UserIncentive::findOrFail($incentiveId);
        
        $user = $incentive->user;
        
        $notifiedUsers = collect([
            auth()->user(),
            $user
        ])->filter();
    
        if ($notifiedUsers->isNotEmpty()) {
            Notification::send($notifiedUsers, new IncentiveNotification(
                $incentive, 
                'delete', 
                'تم حذف الحوافز بقيمة ' . $incentive->amount . ' للعامل ' . ($user ? $user->name : 'Unknown')
            ));
        }
      
        $this->ActivityLogsService->insert([
            'subject' => $incentive,
            'title' => 'تم حذف الحوافز',
            'description' => 'تم حذف الحوافز بقيمة ' . $incentive->amount . ' في تاريخ ' . \Carbon\Carbon::parse($incentive->created_at)->format('Y-m-d') . ' للعامل ' . ($user ? $user->name : 'Unknown'),
            'proccess_type' => 'delete',
            'user_id' => 1,
        ]);
    
        $incentive->delete();
    
        return redirect()->route('dashboard.hr.incentive.index')->with('success', trans('admin.Incentive deleted successfully'));
    }

}
