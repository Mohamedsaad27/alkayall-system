<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Warehouse;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ProductBranchDetails;
use App\Models\ProductWarehouseDetail;
use Yajra\DataTables\Facades\DataTables;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Warehouse::query();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn =  '<div class="btn-group"><button type="button" class="btn btn-success">' . trans('admin.Actions') . '</button><button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown"></button><div class="dropdown-menu" role="menu">';

                    if (auth('user')->user()->has_permission('update-warehouses'))
                        $btn .= '<a class="dropdown-item" href="' . route('dashboard.warehouses.edit', $row->id) . '">' . trans("admin.Edit") . '</a>';

                    if (auth('user')->user()->has_permission('delete-warehouses'))
                        $btn .= '<a class="dropdown-item delete-popup" href="#" data-toggle="modal" data-target="#modal-default" data-url="' . route("dashboard.warehouses.destroy", $row->id) . '">' . trans('admin.Delete') . '</a>';

                    $btn .= '</div></div>';
                    return $btn;
                })
                ->addColumn('name', function ($row) {
                    return $row->name;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('Dashboard.warehouses.index');
    }

    public function create()
    {
        return view('Dashboard.warehouses.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Warehouse::create($request->all());
        return redirect()->route('dashboard.warehouses.index')->with('success', 'Warehouse created successfully.');
    }

    public function show(Request $request)
    {
        return view('Dashboard.warehouses.show', compact('warehouse'));
    }

    public function edit(Request $request)
    {
        $warehouse = Warehouse::find($request->id);

        return view('Dashboard.warehouses.edit', compact('warehouse'));
    }

    public function update(Request $request)
    {
        $warehouse = Warehouse::find($request->id);
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $warehouse->update($request->all());
        return redirect()->route('dashboard.warehouses.index')->with('success', 'Warehouse updated successfully.');
    }

    public function destroy(Request $request)
    {
        $warehouse = Warehouse::find($request->id);

        $warehouse->delete();
        return redirect()->route('dashboard.warehouses.index')->with('success', 'Warehouse deleted successfully.');
    }
    public function getWarehouseQuantity(Request $request)
    {

        $validated = $request->validate([
            'product_id' => 'required|integer',
            'branch_id' => 'required|integer',
            'warehouse_id' => 'integer', // Expect an array
        ]);
    
        $quantity = ProductBranchDetails::where('product_id', $validated['product_id'])
            ->where('branch_id', $validated['branch_id'])
            ->where('warehouse_id', $validated['warehouse_id'])
            ->sum('qty_available');
    
        return response()->json([
            'quantity' => $quantity,
        ]);
    }

}
