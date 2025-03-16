<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Contact;
use App\Models\ManufacturingProductionLines;
use App\Models\ManufacturingRecipeIngredients;
use App\Models\ManufacturingRecipes;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\Unit;
use App\Notifications\ManufacturingRecipesNotification;
use App\Services\ActivityLogsService;
use App\Services\ManufacturingService;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Yajra\DataTables\Facades\DataTables;

class ManufacturingRecipesController extends Controller
{
    public $activityLogsService;
    public $TransactionService;
    public $ManufacturingService;

    public function __construct(ActivityLogsService $activityLogsService, TransactionService $transactionService, ManufacturingService $ManufacturingService)
    {
        $this->activityLogsService = $activityLogsService;
        $this->TransactionService = $transactionService;
        $this->ManufacturingService = $ManufacturingService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = ManufacturingRecipes::with(
                'finalProduct',
                'unit',
                'created_by',
                'ingredients',
                'ManufactringproductionLine'  // Add this relation to load production lines
            )
                ->orderBy('created_at', 'desc')
                ->get();
            // dd($data);
            return DataTables::of($data)
                ->addColumn('action', function ($row) {
                    $hasEndedProductionLine = $row
                        ->ManufactringproductionLine()
                        ->where('is_ended', 1)
                        ->exists();
                    $btn = '<div class="btn-group" role="group" aria-label="Actions">';

                    if (auth('user')->user()->has_permission('read-manufacturing-recipes')) {
                        $btn .= '<a class="btn btn-primary btn-sm mr-1 fire-popup" data-url="' . route('dashboard.manufacturing.show', $row->id) . '"
                            href="#" data-toggle="modal" data-target="#modal-default-big">' . trans('admin.Show') . '</a>';
                    }
                    // Only show edit button if no ended production lines exist
                    if (!$hasEndedProductionLine && auth('user')->user()->has_permission('update-manufacturing-recipes')) {
                        $btn .= '<a class="btn btn-warning btn-sm mr-1" href="' . route('dashboard.manufacturing.edit', $row->id) . '">' . trans('admin.Edit') . '</a>';
                    }

                    // Only show delete button if no ended production lines exist
                    if (!$hasEndedProductionLine && auth('user')->user()->has_permission('delete-manufacturing-recipes')) {
                        $btn .= '<button class="btn btn-danger btn-sm delete-popup mr-1" data-toggle="modal" data-target="#modal-default" 
                        data-url="' . route('dashboard.manufacturing.destroy', $row->id) . '">' . trans('admin.Delete') . '</button>';
                    }

                    $btn .= '</div>';
                    return $btn;
                })
                ->addColumn('recipe', function ($row) {
                    $hasEndedProductionLine = $row
                        ->ManufactringproductionLine()
                        ->where('is_ended', 1)
                        ->exists();
                    $icon = $hasEndedProductionLine
                        ? '<i class="fas fa-industry text-success" data-toggle="tooltip mr-3" title="' . trans('admin.has_production_lines') . '"></i> '
                        : '';
                    return $row->finalProduct->name . $icon;
                })
                ->addColumn('final_quantity', function ($row) {
                    return $row->final_quantity . ' ' . $row->unit->actual_name;
                })
                ->addColumn('materials_cost', function ($row) {
                    return $row->materials_cost;
                })
                ->addColumn('production_cost_value', function ($row) {
                    return $row->production_cost_value;
                })
                ->addColumn('total_cost', function ($row) {
                    return $row->total_cost;
                })
                ->rawColumns(['action', 'recipe'])
                ->make(true);
        }
        return view('Dashboard.manufacturing.index');
    }

    public function create()
    {
        $branches = Branch::all();
        $brands = Brand::all();
        $settings = Setting::first();
        $productsCollection = Product::where('for_sale', true)->get();

        $products = [];
        $units = Unit::all();
        return view('Dashboard.manufacturing.create', compact('products', 'units', 'branches', 'brands', 'settings', 'productsCollection'));
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $recipe = $this->ManufacturingService->createManufacturing($request);
            DB::commit();
            if ($recipe) {
                Notification::send(
                    auth()->user(),
                    new ManufacturingRecipesNotification(
                        $recipe,
                        'create',
                        'تم اضافة وصفة جديدة ب اسم ' . $recipe->finalProduct->name . 'لانتاج كمية ' . $recipe->final_quantity . ' ' . $recipe->unit->actual_name . ' بتكلفة ' . $recipe->total_cost
                    )
                );

                // Fetch recipe integrants
                $recipeIntegrants = $recipe->ingredients->map(function ($ingredient) {
                    return $ingredient->rawMaterial->name . ' (' . $ingredient->quantity . ' ' . $ingredient->unit->actual_name . ')';
                })->join(', ');

                // Log the activity
                $this->activityLogsService->insert([
                    'subject' => $recipe,
                    'title' => 'تم اضافة وصفة جديدة',
                    'description' => 'اسم الوصفة هي ' . $recipe->finalProduct->name . ' بمكونات: ' . $recipeIntegrants . ' . '
                        . 'لانتاج كمية ' . $recipe->final_quantity . ' ' . $recipe->unit->actual_name . ' بتكلفة ' . $recipe->total_cost,
                    'proccess_type' => 'create',
                    'user_id' => auth()->id(),
                ]);
            }

            return redirect()->route('dashboard.manufacturing.index')->with('success', trans('admin.recipe_created_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', trans('admin.recipe_creation_failed') . ' ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $recipe = ManufacturingRecipes::with(
            'finalProduct',
            'unit',
            'created_by',
            'ingredients'
        )->find($id);
        return [
            'title' => trans('admin.Show') . ' ' . $recipe->finalProduct->name,
            'body' => view('Dashboard.manufacturing.show')->with([
                'recipe' => $recipe,
            ])->render()
        ];
    }

    public function edit($id)
    {
        $branches = Branch::all();
        $brands = Brand::all();
        $settings = Setting::first();
        $productsCollection = Product::where('for_sale', true)->get();

        $products = [];
        $units = Unit::all();
        $recipe = ManufacturingRecipes::with(
            'finalProduct',
            'unit',
            'created_by',
            'ingredients'
        )->find($id);
        return view('Dashboard.manufacturing.edit', compact('recipe', 'products', 'units', 'branches', 'brands', 'settings', 'productsCollection'));
    }

    public function update(Request $request, $id)
    {
        dd($request->all());
        try {
            DB::beginTransaction();
            $recipe = $this->ManufacturingService->updateManufacturing($request, $id);
            DB::commit();
            if ($recipe) {
                $this->activityLogsService->insert([
                    'subject' => $recipe,
                    'title' => 'تم تعديل وصفة',
                    'description' => 'اسم الوصفة هي ' . $recipe->finalProduct->name . ' بمكونات: ' . $recipe->ingredients->pluck('rawMaterial.name')->join(', ') . ' . '
                        . 'لانتاج كمية ' . $recipe->final_quantity . ' ' . $recipe->unit->actual_name . ' بتكلفة ' . $recipe->total_cost,
                    'proccess_type' => 'update',
                    'user_id' => auth()->id(),
                ]);
            }
            return redirect()->route('dashboard.manufacturing.index')->with('success', trans('admin.recipe_updated_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);
            // return redirect()->back()->withInput()->with('error', trans('admin.recipe_update_failed') . ' ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $recipe = ManufacturingRecipes::find($id);
        $recipeIntegrants = $recipe->ManufactringproductionLine()->count();
        if ($recipeIntegrants > 0) {
            return redirect()->route('dashboard.manufacturing.index')->with('error', trans('admin.recipe_delete_failed'));
        }
        Notification::send(
            auth()->user(),
            new ManufacturingRecipesNotification(
                $recipe,
                'create',
                'تم حذف وصفة  ب اسم ' . $recipe->finalProduct->name . ' الموجودة بكمية ' . $recipe->final_quantity . ' ' . $recipe->unit->actual_name . ' بتكلفة ' . $recipe->total_cost
            )
        );
        $recipeIntegrants = $recipe->ingredients->map(function ($ingredient) {
            return $ingredient->rawMaterial->name . ' (' . $ingredient->quantity . ' ' . $ingredient->unit->actual_name . ')';
        })->join(', ');
        $this->activityLogsService->insert([
            'subject' => $recipe,
            'title' => 'تم حذف وصفة ',
            'description' => 'اسم الوصفة هي ' . $recipe->finalProduct->name . ' بمكونات: ' . $recipeIntegrants . ' . '
                . 'لانتاج كمية ' . $recipe->final_quantity . ' ' . $recipe->unit->actual_name . ' بتكلفة ' . $recipe->total_cost,
            'proccess_type' => 'create',
            'user_id' => auth()->id(),
        ]);
        $recipe->delete();
        $recipe->ingredients()->delete();
        return redirect()->route('dashboard.manufacturing.index')->with('success', trans('admin.recipe_deleted_successfully'));
    }

    public function searchProducts(Request $request)
    {
        $products = Product::where('for_sale', false)->get();
        $productsWithStock = $products->map(function ($product) {
            $purchasePriceByMainUnit = $product->getPurchasePriceByUnit($product->unit_id);
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'purchase_price' => $purchasePriceByMainUnit ? number_format($purchasePriceByMainUnit, 2) : null,
            ];
        });

        return response()->json($productsWithStock);
    }

    public function ManufacturingReports()
    {
        $data = [
            'totalRecipes' => ManufacturingRecipes::count(),
            'monthlyProduction' => $this->getMonthlyProduction(),
            'averageWastage' => $this->getAverageWastage(),
            'totalProductionCost' => $this->getTotalProductionCost(),
            'recipes' => ManufacturingRecipes::with('finalProduct')->get(),
            'branches' => Branch::all(),
            'productionLines' => ManufacturingProductionLines::all(),
            'rawMaterials' => Product::whereHas('manufacturingLines')->get(),
        ];

        return view('Dashboard.manufacturing.reports', $data);
    }

    public function getIngredients(ManufacturingRecipes $recipe)
    {
        $ingredients = $recipe->ingredients()->with(['rawMaterial', 'unit'])->get();
        return response()->json(['ingredients' => $ingredients]);
    }

    public function getRecipeDetails(ManufacturingRecipes $recipe)
    {
        return response()->json([
            'recipe' => $recipe,
            'ingredients' => $recipe->ingredients()->with(['rawMaterial', 'unit'])->get()
        ]);
    }

    private function getMonthlyProduction()
    {
        return ManufacturingProductionLines::whereMonth('date', Carbon::now()->month)
            ->sum('production_quantity');
    }

    private function getAverageWastage()
    {
        return ManufacturingProductionLines::avg('wastage_rate');
    }

    private function getTotalProductionCost()
    {
        return ManufacturingProductionLines::sum('production_total_cost');
    }

    public function recipeCostReport(Request $request)
    {
        $data = ManufacturingProductionLines::where('recipe_id', $request->recipe_id)
            ->whereBetween('date', [$request->start_date, $request->end_date])
            ->select(
                DB::raw('DATE(date) as production_date'),
                DB::raw('SUM(production_total_cost) as total_cost'),
                DB::raw('SUM(production_quantity) as total_quantity')
            )
            ->groupBy('date')
            ->get();

        return response()->json($data);
    }

    public function productionPerformance(Request $request)
    {
        $data = ManufacturingProductionLines::where('branch_id', $request->branch_id)
            ->whereYear('date', Carbon::parse($request->period)->year)
            ->whereMonth('date', Carbon::parse($request->period)->month)
            ->select(
                'production_line_code',
                DB::raw('SUM(production_quantity) as total_production'),
                DB::raw('AVG(wastage_rate) as avg_wastage')
            )
            ->groupBy('production_line_code')
            ->get();

        return response()->json($data);
    }

    public function wastageReport(Request $request)
    {
        $data = ManufacturingProductionLines::where('id', $request->production_line_id)
            ->whereBetween('date', [$request->start_date, $request->end_date])
            ->select(
                DB::raw('DATE(date) as production_date'),
                'wastage_rate',
                'production_quantity'
            )
            ->get();

        return response()->json($data);
    }

    public function materialUsage(Request $request)
    {
        $data = DB::table('manufacturing_recipe_ingredients')
            ->join('manufacturing_production_lines', 'manufacturing_recipe_ingredients.recipe_id', '=', 'manufacturing_production_lines.recipe_id')
            ->where('manufacturing_recipe_ingredients.raw_material_id', $request->raw_material_id)
            ->whereYear('manufacturing_production_lines.date', Carbon::parse($request->period)->year)
            ->whereMonth('manufacturing_production_lines.date', Carbon::parse($request->period)->month)
            ->select(
                DB::raw('DATE(manufacturing_production_lines.date) as usage_date'),
                DB::raw('SUM(manufacturing_recipe_ingredients.quantity * manufacturing_production_lines.production_quantity) as total_usage'),
                DB::raw('AVG(manufacturing_recipe_ingredients.raw_material_price) as avg_price')
            )
            ->groupBy('manufacturing_production_lines.date')
            ->get();

        return response()->json($data);
    }
}
