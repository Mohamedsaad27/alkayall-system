<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\ManufacturingProductionLines;
use App\Models\ManufacturingRecipes;
use App\Models\ProductBranchDetails;
use App\Models\Unit;
use App\Notifications\ProductionLineNotification;
use App\Services\ActivityLogsService;
use App\Services\ManufacturingService;
use App\Services\StockService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Yajra\DataTables\Facades\DataTables;

class ProductionController extends Controller
{
    public $activityLogsService;
    public $TransactionService;
    public $ManufacturingService;
    protected $stockService;

    public function __construct(ActivityLogsService $activityLogsService, TransactionService $transactionService, ManufacturingService $ManufacturingService, StockService $stockService)
    {
        $this->activityLogsService = $activityLogsService;
        $this->TransactionService = $transactionService;
        $this->ManufacturingService = $ManufacturingService;
        $this->stockService = $stockService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = ManufacturingProductionLines::with(['branch', 'recipe', 'productionQuantityUnit', 'wastageRateUnit'])
                ->orderBy('created_at', 'desc')
                ->get();
            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $btn = '<div class="btn-group" role="group" aria-label="Actions">';

                    // Show button - always enabled
                    if (auth('user')->user()->has_permission('read-production')) {
                        $btn .= '<a class="btn btn-primary btn-sm mr-1 fire-popup" data-url="' . route('dashboard.production.show', $row->id) . '"
                        href="#" data-toggle="modal" data-target="#modal-default-big">' . trans('admin.Show') . '</a>';
                    }

                    // Edit button - disabled if ended
                    if (auth('user')->user()->has_permission('update-production')) {
                        if ($row->is_ended) {
                            $btn .= '<button class="btn btn-warning btn-sm mr-1" disabled title="' . trans('admin.cant_edit_ended_production') . '">'
                                . trans('admin.Edit') . '</button>';
                        } else {
                            $btn .= '<a class="btn btn-warning btn-sm mr-1" href="' . route('dashboard.production.edit', $row->id) . '">'
                                . trans('admin.Edit') . '</a>';
                        }
                    }

                    // Delete button - disabled if ended
                    if (auth('user')->user()->has_permission('delete-production')) {
                        if ($row->is_ended) {
                            $btn .= '<button class="btn btn-danger btn-sm mr-1" disabled title="' . trans('admin.cant_delete_ended_production') . '">'
                                . trans('admin.Delete') . '</button>';
                        } else {
                            $btn .= '<button class="btn btn-danger btn-sm delete-popup mr-1" data-toggle="modal" data-target="#modal-default" 
                            data-url="' . route('dashboard.production.destroy', $row->id) . '">' . trans('admin.Delete') . '</button>';
                        }
                    }

                    // Change status button
                    if (auth('user')->user()->has_permission('change-status-production')) {
                        $btn .= '<a class="dropdown-item fire-popup bg-info" href="#" data-toggle="modal" data-target="#modal-default" data-url="'
                            . route('dashboard.production.change-status', $row->id) . '">' . trans('admin.change_production_line_status') . '</a>';
                    }

                    $btn .= '</div>';
                    return $btn;
                })
                ->addColumn('date', function ($row) {
                    return \carbon\carbon::parse($row->date)->format('Y-m-d');
                })
                ->addColumn('production_line_code', function ($row) {
                    return $row->production_line_code;
                })
                ->addColumn('branch', function ($row) {
                    return $row->branch->name;
                })
                ->addColumn('recipe', function ($row) {
                    return $row->recipe->finalProduct->name ?? '-';
                })
                ->addColumn('production_quantity', function ($row) {
                    return $row->production_quantity . ' ' . $row->productionQuantityUnit->actual_name;
                })
                ->addColumn('production_cost_value', function ($row) {
                    return $row->production_total_cost;
                })
                ->addColumn('is_ended', function ($row) {
                    return $row->is_ended;
                })
                ->rawColumns(['action', 'is_ended'])
                ->make(true);
        }
        return view('Dashboard.production.index');
    }

    public function create()
    {
        $branches = Branch::active()->get();
        $productRecipe = ManufacturingRecipes::with('finalProduct')->get();
        $units = Unit::all();
        return view('Dashboard.production.create', compact('branches', 'productRecipe', 'units'));
    }

    public function store(Request $request)
    {
        try {
            $productionLine = $this->ManufacturingService->storeProductionLine($request->all());
            if ($productionLine) {
                Notification::send(
                    auth()->user(),
                    new ProductionLineNotification(
                        $productionLine,
                        'create',
                        ' تم اضافة خط انتاج جديد ب اسم  ' . $productionLine->recipe->finalProduct->name . '  لانتاج كمية  ' . $productionLine->production_quantity
                            . ' ' . $productionLine->productionQuantityUnit->actual_name
                            . ' في الفرع ' . $productionLine->branch->name . ' بتكلفة ' . $productionLine->production_total_cost
                    )
                );
                $this->activityLogsService->insert([
                    'subject' => $productionLine,
                    'title' => 'تم اضافة خط اننتاج جديدة',
                    'description' => ' اسم خط الانتاج هو ' . $productionLine->recipe->finalProduct->name . ' في الفرع ' . $productionLine->branch->name
                        . 'لانتاج كمية ' . $productionLine->production_quantity . ' ' . $productionLine->productionQuantityUnit->actual_name . ' بتكلفة ' . $productionLine->production_total_cost,
                    'proccess_type' => 'create',
                    'user_id' => auth()->id(),
                ]);
            }
            return redirect()
                ->route('dashboard.production.index')
                ->with('success', trans('admin.production_line_created_successfully'));
        } catch (\Exception $e) {
            return redirect()
                ->route('dashboard.production.create')
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $production = ManufacturingProductionLines::with(['branch', 'recipe', 'productionQuantityUnit', 'wastageRateUnit', 'ProductionLineIngredients'])->find($id);
        return [
            'title' => trans('admin.Show') . ' ' . trans('admin.production_line') . ' الخاص بالرقم المرجعي ' . $production->production_line_code,
            'body' => view('Dashboard.production.show')->with([
                'production' => $production
            ])->render(),
        ];
    }

    public function edit(Request $request, $id)
    {
        $branches = Branch::active()->get();
        $productRecipe = ManufacturingRecipes::with('finalProduct')->get();
        $units = Unit::all();
        $production = ManufacturingProductionLines::with(['branch', 'recipe', 'productionQuantityUnit', 'wastageRateUnit'])->find($id);
        return view('Dashboard.production.edit', compact('branches', 'productRecipe', 'units', 'production'));
    }

    public function update(Request $request, $id)
    {
        // dd($request->all());
        try {
            $productionLine = ManufacturingProductionLines::findOrFail($id);

            if ($productionLine->is_ended) {
                return redirect()
                    ->route('dashboard.production.index')
                    ->with('error', trans('admin.cant_edit_ended_production'));
            }

            $updatedProductionLine = $this->ManufacturingService->updateProductionLine($request->all(), $id);

            // Send notification for update
            Notification::send(
                auth()->user(),
                new ProductionLineNotification(
                    $updatedProductionLine,
                    'update',
                    ' تم تحديث خط انتاج  ' . $updatedProductionLine->recipe->finalProduct->name
                        . '  لانتاج كمية  ' . $updatedProductionLine->production_quantity
                        . ' ' . $updatedProductionLine->productionQuantityUnit->actual_name
                        . ' في الفرع ' . $updatedProductionLine->branch->name
                        . ' بتكلفة ' . $updatedProductionLine->production_total_cost
                )
            );

            $this->activityLogsService->insert([
                'subject' => $updatedProductionLine,
                'title' => 'تم تحديث خط انتاج',
                'description' => ' تم تحديث خط انتاج  ' . $updatedProductionLine->recipe->finalProduct->name
                    . ' في الفرع ' . $updatedProductionLine->branch->name
                    . ' لانتاج كمية ' . $updatedProductionLine->production_quantity
                    . ' ' . $updatedProductionLine->productionQuantityUnit->actual_name
                    . ' بتكلفة ' . $updatedProductionLine->production_total_cost,
                'proccess_type' => 'update',
                'user_id' => auth()->id(),
            ]);
            return redirect()
                ->route('dashboard.production.index')
                ->with('success', trans('admin.production_line_updated_successfully'));
        } catch (\Exception $e) {
            dd($e);
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    public function changeStatus(Request $request, $id)
    {
        $production = ManufacturingProductionLines::find($id);
        return [
            'title' => trans('admin.change_production_line_status') . ' المعرف بالرقم المرجعي ' . $production->production_line_code,
            'body' => view('Dashboard.production.changeStatus')->with([
                'production' => $production
            ])->render(),
        ];
    }

    public function changeStatusPost(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $productionLine = ManufacturingProductionLines::with(['ProductionLineIngredients', 'recipe.finalProduct'])
                ->findOrFail($id);

            $newStatus = !$productionLine->is_ended;
            $oldStatus = $productionLine->is_ended;
            if ($newStatus) {
                foreach ($productionLine->ProductionLineIngredients as $ingredient) {
                    $availableQty = ProductBranchDetails::where('product_id', $ingredient->raw_material_id)
                        ->where('branch_id', $productionLine->branch_id)
                        ->first()
                        ->qty_available ?? 0;

                    if ($availableQty < $ingredient->quantity) {
                        throw new \Exception('لا يوجد كمية كافية ل' . $ingredient->rawMaterial->name);
                    }

                    $this->stockService->SubtractFromStock(
                        $ingredient->raw_material_id,
                        $productionLine->branch_id,
                        $ingredient->quantity,
                        null,
                        $ingredient->unit_id
                    );
                }

                $finalQuantity = $productionLine->production_quantity;
                $this->stockService->addToStock(
                    $productionLine->recipe->final_product_id,
                    $productionLine->branch_id,
                    $finalQuantity,
                    null,
                    $productionLine->quantity_unit_id
                );
            } else {
                $this->stockService->SubtractFromStock(
                    $productionLine->recipe->final_product_id,
                    $productionLine->branch_id,
                    $productionLine->production_quantity,
                    null,
                    $productionLine->quantity_unit_id
                );

                foreach ($productionLine->recipe->ingredients as $ingredient) {
                    $this->stockService->addToStock(
                        $ingredient->raw_material_id,
                        $productionLine->branch_id,
                        $ingredient->quantity,
                        null,
                        $ingredient->unit_id
                    );
                }
            }
            $productionLine->is_ended = $newStatus;
            $productionLine->save();
            Notification::send(
                auth()->user(),
                new ProductionLineNotification(
                    $productionLine,
                    'update',
                    ' تم تغيير حالة خط انتاج  ' . $productionLine->recipe->finalProduct->name
                        . ' من ' . ($oldStatus ? 'منتهي' : 'جاري')
                        . ' الى ' . ($newStatus ? 'منتهي' : 'جاري')
                )
            );

            $this->activityLogsService->insert([
                'subject' => $productionLine,
                'title' => 'تم تغيير حالة خط انتاج',
                'description' => ' تم تغيير حالة خط انتاج  ' . $productionLine->recipe->finalProduct->name
                    . ' من ' . ($oldStatus ? 'منتهي' : 'جاري')
                    . ' الى ' . ($newStatus ? 'منتهي' : 'جاري'),
                'proccess_type' => 'update',
                'user_id' => auth()->id(),
            ]);
            DB::commit();
            return redirect()
                ->route('dashboard.production.index')
                ->with('success', trans('admin.production_line_status_changed_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->route('dashboard.production.index')
                ->with('error', $e->getMessage());
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $production = ManufacturingProductionLines::find($id);
            if ($production->is_ended) {
                return redirect()
                    ->route('dashboard.production.index')
                    ->with('error', trans('admin.cant_delete_production_line'));
            }
            $production->delete();
            return redirect()
                ->route('dashboard.production.index')
                ->with('success', trans('admin.production_line_deleted_successfully'));
        } catch (\Exception $e) {
            return redirect()
                ->route('dashboard.production.index')
                ->with('error', $e->getMessage());
        }
    }

    public function production_line_code(Request $request)
    {
        $recipe = ManufacturingRecipes::where('id', $request->recipe_id)->first();

        if (!$recipe) {
            return response()->json(['error' => 'Recipe not found'], 404);
        }

        $lastCode = ManufacturingProductionLines::latest('id')->value('production_line_code');

        $code = $lastCode ? intval(str_replace('PL-', '', $lastCode)) : 0;

        $productionLineCode = 'PL-' . ($code + 1);

        return response()->json($productionLineCode);
    }
}
