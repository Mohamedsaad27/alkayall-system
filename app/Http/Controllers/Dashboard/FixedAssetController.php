<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Branch;
use App\Models\FixedAsset;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\ActivityLogsService;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Controllers\Controller;


class FixedAssetController extends Controller
{
    protected $ActivityLogsService;

    public function __construct(ActivityLogsService $activityLogsService)
    {
        $this->ActivityLogsService = $activityLogsService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $assets = FixedAsset::with(['branch', 'createdBy'])->orderBy('created_at', 'desc');

            if ($request->branch_id) {
                $assets->where('branch_id', $request->branch_id);
            }

            return DataTables::of($assets)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = '<div class="btn-group">
                                <button type="button" class="btn btn-success">' . trans('admin.Actions') . '</button>
                                <button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown"></button>
                                <div class="dropdown-menu" role="menu">';

                    if (auth('user')->user()->has_permission('read-fixed-assets')) {
                        $btn .= '<a class="dropdown-item fire-popup" data-url="' . route('dashboard.fixed-assets.show', $row->id) . '"
                                    href="#" data-toggle="modal" data-target="#modal-default-big">' . trans('admin.Show') . '</a>';
                    }

                    if (auth('user')->user()->has_permission('update-fixed-assets')) {
                        $btn .= '<a class="dropdown-item" href="' . route('dashboard.fixed-assets.edit', $row->id) . '">' . trans('admin.Edit') . '</a>';
                    }

                    if (auth('user')->user()->has_permission('delete-fixed-assets')) {
                        $btn .= '<a class="dropdown-item delete-popup" href="#" data-toggle="modal" data-target="#modal-default" data-url="' . route('dashboard.fixed-assets.destroy', $row->id) . '">' . trans('admin.Delete') . '</a>';
                    }

                    $btn .= '</div></div>';
                    return $btn;
                })
                ->addColumn('branch', function ($row) {
                    return $row->branch?->name ?? '-';
                })
                ->addColumn('amount', function ($row) {
                    return number_format($row->price, 2) . ' ' . trans('admin.currency');
                })
                ->addColumn('status', function ($row) {
                    return trans('admin.' . $row->status);
                })
                ->addColumn('route', function ($row) {
                    return route('dashboard.fixed-assets.show', $row->id);
                })
                ->addColumn('created_by', function ($row) {
                    return $row->createdBy?->name ?? '-';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $totalPrice = FixedAsset::when($request->branch_id, function($query) use ($request) {
            return $query->where('branch_id', $request->branch_id);
        })->sum('price');

        $branches = Branch::all();
        return view('Dashboard.fixed-assets.index', compact('branches' , 'totalPrice'));
    }

    public function create(Request $request)
    {
        $branches = Branch::all();
        $users = User::all();
        $defaultValues = [];

        // Check if request contains predefined values
        if ($request->has(['branch_id', 'name', 'price', 'created_by'])) {
            $defaultValues = [
                'branch_id' => $request->branch_id,
                'name' => $request->name,
                'price' => $request->price,
                'created_by' => $request->created_by,
                'status' => $request->status ?? 'active', // Default status
                'note' => $request->note ?? '',
            ];
        }

        return view('Dashboard.fixed-assets.create', compact('branches', 'users', 'defaultValues'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'branch_id' => 'required|integer|exists:branchs,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'created_by' => 'required|integer|exists:users,id',
            'status' => 'required|in:active,inactive,sold',
            'note' => 'nullable|string',
        ]);

        // Create fixed asset
        $fixedAsset = FixedAsset::create($validatedData);

        // Log activity
        $this->ActivityLogsService->insert([
            'subject' => $fixedAsset,
            'title' => 'تم إضافة أصل ثابت جديد',
            'description' => 'تم إضافة أصل ثابت جديد بقيمة ' . $validatedData['price'] .
                ' ضمن الفرع ' . $fixedAsset->branch->name . '.',
            'proccess_type' => 'create', //fixed_assets
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('dashboard.fixed-assets.index')->with('success', trans('admin.created_successfully_fixed_assets '));
    }

    public function edit($id)
    {
        $fixedAsset = FixedAsset::find($id);
        $branches = Branch::all();
        $users = User::all();
        return view('Dashboard.fixed-assets.edit', compact('fixedAsset', 'branches', 'users'));
    }

    public function update(Request $request, FixedAsset $fixedAsset)
    {
        $validatedData = $request->validate([
            'branch_id' => 'required|integer|exists:branchs,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'status' => 'required|in:active,inactive,sold',
            'note' => 'nullable|string',
        ]);

        // Update fixed asset
        $fixedAsset->update($validatedData);

        // Log activity
        $this->ActivityLogsService->insert([
            'subject' => $fixedAsset,
            'title' => 'تم تعديل الأصل الثابت',
            'description' => 'تم تعديل الأصل الثابت بقيمة ' . $validatedData['price'] .
                ' ضمن الفرع ' . $fixedAsset->branch->name . '.',
            'proccess_type' => 'update', // fixed_assets
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('dashboard.fixed-assets.index')->with('success', trans('admin.updated_successfully_fixed_assets'));
    }

    public function destroy(FixedAsset $fixedAsset)
    {
        $fixedAsset->delete();

        // Log activity
        $this->ActivityLogsService->insert([
            'subject' => $fixedAsset,
            'title' => 'تم حذف أصل ثابت',
            'description' => 'تم حذف الأصل الثابت: ' . $fixedAsset->name . '.',
            'proccess_type' => 'delete',
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('dashboard.fixed-assets.index')->with('success', trans('admin.deleted_successfully_fixed_assets'));
    }

    public function show(FixedAsset $fixedAsset)
    {
        return [
            'title' => trans('admin.Show'),
            'body' => view('Dashboard.fixed-assets.show')->with([
                'fixedAsset' => $fixedAsset,
            ])->render(),
        ];
    }

}
