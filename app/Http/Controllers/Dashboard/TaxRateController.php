<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\TaxRate;
use App\Services\ActivityLogsService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class TaxRateController extends Controller
{
    protected $ActivityLogsService;

    public function __construct(ActivityLogsService $ActivityLogsService)
    {
        $this->ActivityLogsService = $ActivityLogsService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $tax_rates = TaxRate::with('createdBy')->get();
            return DataTables::of($tax_rates)
                ->addIndexColumn()
                ->addColumn('name', function ($tax_rate) {
                    return $tax_rate->name;
                })
                ->addColumn('rate', function ($tax_rate) {
                    return $tax_rate->rate . ' %';
                })
                ->addColumn('is_active', function ($tax_rate) {
                    return $tax_rate->is_active;
                })
                ->addColumn('created_by', function ($tax_rate) {
                    return $tax_rate->createdBy->name;
                })
                ->addColumn('created_at', function ($tax_rate) {
                    return \Carbon\Carbon::parse($tax_rate->created_at)->format('Y-m-d');
                })
                ->addColumn('route', function ($tax_rate) {
                    return route('dashboard.settings.tax-rates.show', $tax_rate->id);
                })
                ->addColumn('action', function ($tax_rate) {
                    $btn = '<div class="btn-group"><button type="button" class="btn btn-success">' . trans('admin.Actions') . '</button><button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown"></button><div class="dropdown-menu" role="menu">';
                    if (auth('user')->user()->has_permission('read-tax-rates'))
                        $btn .= '<a class="dropdown-item fire-popup" href="#" data-url="' . route('dashboard.settings.tax-rates.show', $tax_rate->id)
                            . '" data-toggle="modal" data-target="#modal-default-big">' . trans('admin.Show') . '</a>';

                    if (auth('user')->user()->has_permission('update-tax-rates'))
                        $btn .= '<a class="dropdown-item" href="' . route('dashboard.settings.tax-rates.edit', $tax_rate->id) . '">' . trans('admin.Edit') . '</a>';

                    if (auth('user')->user()->has_permission('delete-tax-rates'))
                        $btn .= '<a class="dropdown-item delete-popup" href="#" data-toggle="modal" data-target="#modal-default" data-url="' . route('dashboard.settings.destroy', $tax_rate->id) . '">' . trans('admin.Delete') . '</a>';
                    $btn .= '</div></div>';
                    return $btn;
                })
                ->rawColumns(['action', 'route', 'created_by', 'created_at'])
                ->make(true);
        }
        return view('Dashboard.settings.tax-rates.index');
    }

    public function create()
    {
        return view('Dashboard.settings.tax-rates.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0|max:100',
            'is_active' => 'sometimes|boolean',  // Change this line
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['created_by'] = auth('user')->user()->id;

        $taxRate = TaxRate::create($validated);
        if ($taxRate) {
            $this->ActivityLogsService->insert([
                'subject' => $taxRate,
                'title' => 'تم إضافة ضريبة جديدة',
                'description' => 'تم إضافة الضريبة ' . $taxRate->name,
                'proccess_type' => 'create',
                'user_id' => auth()->id(),
            ]);
        }
        return redirect()->route('dashboard.settings.tax-rates.index')->with('success', 'تم إضافة الضريبة بنجاح');
    }

    public function show(TaxRate $taxRate)
    {
        $taxRate = TaxRate::find($taxRate->id);
        return [
            'title' => trans('admin.Show') . ' ' . $taxRate->name,
            'body' => view('Dashboard.settings.tax-rates.show')->with([
                'taxRate' => $taxRate,
            ])->render(),
        ];
    }

    public function edit(TaxRate $taxRate)
    {
        return view('Dashboard.settings.tax-rates.edit', compact('taxRate'));
    }

    public function update(Request $request, TaxRate $taxRate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);
        if ($request->is_active == null) {
            $validated['is_active'] = 0;
        }
        $validated['created_by'] = auth()->user()->id;
        $taxRate->update($validated);
        $this->ActivityLogsService->insert([
            'subject' => $taxRate,
            'title' => 'تم تعديل  ضريبة جديدة',
            'description' => 'تم تعديل الضريبة ' . $taxRate->name,
            'proccess_type' => 'update',
            'user_id' => auth()->id(),
        ]);
        return redirect()->route('dashboard.settings.tax-rates.index')->with('success', 'تم تعديل الضريبة بنجاح');
    }

    public function destroy($id)
    {
        $taxRate = TaxRate::find($id);
        $taxRate->delete();
        $this->ActivityLogsService->insert([
            'subject' => $taxRate,
            'title' => 'تم حذف  ضريبة جديدة',
            'description' => 'تم حذف الضريبة ' . $taxRate->name,
            'proccess_type' => 'delete',
            'user_id' => auth()->id(),
        ]);
        return redirect()->route('dashboard.settings.tax-rates.index')->with('success', 'تم حذف الضريبة بنجاح');
    }
}
