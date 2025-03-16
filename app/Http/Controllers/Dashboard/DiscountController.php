<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\User;
use App\Models\UserDiscount;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Http\Controllers\Controller;
use App\Services\ActivityLogsService;
use App\Notifications\DiscountNotification;
use Illuminate\Support\Facades\Notification;

class DiscountController extends Controller
{
    protected $ActivityLogsService;
    public function __construct(ActivityLogsService $activityLogsService)
    {
        $this->ActivityLogsService = $activityLogsService;
    }
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $discounts = UserDiscount::where('amount', '>', 0)->with('user')->orderBy('id', 'desc');
            if ($request->user_id) {
                $discounts = $discounts->where('user_id', $request->user_id);
            }
            if ($request->date_from && $request->date_to) {
                $discounts = $discounts->whereBetween('created_at', [$request->date_from, $request->date_to]);
            }
            $totalAmount = $discounts->sum('amount');
            return DataTables::of($discounts)
                ->addColumn('action', function ($discount) {
                    $btn = '<div class="btn-group"><button type="button" class="btn btn-success">'. trans('admin.Actions') .'</button><button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown"></button><div class="dropdown-menu" role="menu">';
                    $btn .= '<a class="dropdown-item fire-popup" data-url="' . route('dashboard.hr.discount.edit', $discount->id) . '" href="#" data-toggle="modal" data-target="#modal-default">' . trans("admin.Edit") . '</a>';
                    $btn .= '<a class="dropdown-item delete-popup" href="#" data-toggle="modal" data-target="#modal-default" data-url="' . route('dashboard.hr.delete-discount', $discount->id) . '" >' . trans('admin.Delete') . '</a>';
                    $btn .= '</div></div>';
                    return $btn;
                })
                ->addColumn('user', function ($discount) {
                    return $discount->user->name;
                })
                ->addColumn('amount', function ($discount) {
                    return $discount->amount;
                })
                ->addColumn('notes', function ($discount) {
                    return $discount->notes;
                })
                ->addColumn('created_at', function ($discount) {
                    return \Carbon\Carbon::parse($discount->created_at)->format('Y-m-d');
                })
                ->with('total', $totalAmount)
                ->rawColumns(['action', 'user', 'amount', 'notes', 'created_at'])
                ->make(true);
        }
        $users = User::hrUsers()->get();
        return view('Dashboard.hr-module.discounts.index', compact('users'));
    }
    public function create()
    {
        $users = User::hrUsers()->get();
        return view('Dashboard.hr-module.discounts.create', compact('users'));
    }
    public function store(Request $request)
    {
        if ($request->has('add_discount_for_all')) {
            $validatedData = $request->validate([
                'amount' => 'required|numeric|min:0',
                'notes' => 'nullable|string'
            ]);
            $discount = $this->createDiscountForAllUsers($validatedData);
            $users = User::hrUsers()->get();
            $users->push(auth()->user());
            Notification::send($users, new DiscountNotification($discount, 'create', 'تم إضافة خصم جديدة بقيمة ' . $request->amount . ' للعاملين'));
            $this->ActivityLogsService->insert([
                'subject' => $discount,
                'title' => 'تم إضافة خصم جديدة لجميع العاملين',
                'description' => 'تم إضافة خصم جديدة بقيمة ' . $request->amount . ' لجميع العاملين',
                'proccess_type' => 'discount',
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

            $discount = $this->createDiscountForSpecificUsers($validatedData);
            $users = User::whereIn('id', $validatedData['user_ids'])->get();
            $users->push(auth()->user());
            Notification::send($users, new DiscountNotification($discount, 'create', 'تم إضافة خصم جديدة بقيمة ' . implode(', ', $request->amount) . ' للعاملين ' . $users->implode('name', ', ')));

            $this->ActivityLogsService->insert([
                'subject' => $discount,
                'title' => 'تم إضافة خصم جديدة  ',
                'description' => 'تم إضافة خصم جديدة بقيمة ' . implode(', ', $request->amount) . ' للعاملين ' . $users->implode('name', ', ') . ' في تاريخ ' . \Carbon\Carbon::parse($discount->created_at)->format('Y-m-d'),
                'proccess_type' => 'discount',
                'user_id' => auth()->id(),
            ]);
        }

        return redirect()->route('dashboard.hr.discount.index')
            ->with('success', trans('admin.Discount created successfully'));
    }

    private function createDiscountForAllUsers($validatedData)
    {
        $users = User::hrUsers()->get();
        foreach ($users as $user) {
            $userDiscount = UserDiscount::create([
                'user_id' => $user->id,
                'amount' => $validatedData['amount'],
                'notes' => $validatedData['notes'],
            ]);
        }
        return $userDiscount;
    }

    private function createDiscountForSpecificUsers($validatedData)
    {
        $userIds = $validatedData['user_ids'];
        foreach ($userIds as $userId) {
            $user = User::find($userId);
            $userDiscount = UserDiscount::create([
                'user_id' => $userId,
                'amount' => $validatedData['amount'][$userId] ?? $user->userDiscount->amount ?? 0,
                'notes' => $validatedData['notes'][$userId] ?? null,
            ]);
        }
        return $userDiscount;
    }
    public function edit(UserDiscount $discount)
    {
        return [
            'title' => trans('admin.Edit') . ' خصم',
            'body'  => view('Dashboard.hr-module.discounts.edit')->with([
                'discount' => $discount,
            ])->render(),
        ];
    }
    public function update(Request $request, UserDiscount $discount)
    {
        $validatedData = $request->validate([
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);
        $discount->update($validatedData);
        $users = User::where('id', $discount->user_id)->get();
        $users->push(auth()->user());
        Notification::send($users, new DiscountNotification($discount, 'update', 'تم تعديل خصم بقيمة ' . $request->amount . ' للعامل ' . $discount->user->name));
        $this->ActivityLogsService->insert([
            'subject' => $discount,
            'title' => 'تم تعديل خصم',
            'description' => 'تم تعديل خصم بقيمة ' . $request->amount . ' للعامل ' . $discount->user->name,
            'proccess_type' => 'discount',
            'user_id' => auth()->id(),
        ]);
        return redirect()->route('dashboard.hr.discount.index')->with('success', trans('admin.Discount updated successfully'));
    }
    public function destroy($userDiscountId)
    {
        $userDiscount = UserDiscount::find($userDiscountId);
        $user = User::find($userDiscount->user_id);
        $notifiedUser[] = auth()->user();
        $notifiedUser[] = $user;
        Notification::send($notifiedUser, new DiscountNotification($userDiscount, 'delete', 'تم حذف خصم بقيمة ' . $userDiscount->amount . ' للعامل ' . $user->name));
        $this->ActivityLogsService->insert([
            'subject' => $userDiscount,
            'title' => 'تم حذف خصم',
            'description' => 'تم حذف خصم بقيمة ' . $userDiscount->amount . ' للعامل ' . $user->name,
            'proccess_type' => 'discount',
            'user_id' => auth()->id(),
        ]);

        $userDiscount->delete();
        return redirect()->route('dashboard.hr.discount.index')->with('success', trans('admin.Discount deleted successfully'));
    }
}
