<?php

namespace App\Services;

use App\Models\ManufacturingProductionLines;
use App\Models\ManufacturingRecipeIngredients;
use App\Models\ManufacturingRecipes;
use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\ProductBranchDetails;
use App\Models\ProductionLineIngredients;
use App\Models\ProductPriceHistory;
use App\Models\ProductUnitDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManufacturingService
{
    protected $stockService;
    protected $transactionService;

    public function __construct(StockService $stockService, TransactionService $transactionService)
    {
        $this->stockService = $stockService;
        $this->transactionService = $transactionService;
    }

    public function createManufacturing($request)
    {
        $materialsCost = collect($request->manufacturing_recipes)->sum(function ($ingredient) {
            return $ingredient['quantity'] * $ingredient['unit_price'];
        });

        // Create recipe
        $recipe = ManufacturingRecipes::create([
            'final_product_id' => $request->final_product_id,
            'description' => $request->description,
            'total_wastage_rate' => $request->total_wastage_rate,
            'final_quantity' => $request->final_quantity,
            'unit_id' => $request->final_quantity_unit_id,
            'production_cost_type' => 'fixed',
            'production_cost_value' => $request->production_cost_value,
            'materials_cost' => $materialsCost,
            'total_cost' => $request->total_cost,
            'created_by' => auth()->id()
        ]);

        // Create ingredients
        foreach ($request->manufacturing_recipes as $ingredient) {
            ManufacturingRecipeIngredients::create([
                'recipe_id' => $recipe->id,
                'raw_material_id' => $ingredient['product_id'],
                'wastage_rate' => $ingredient['wastage_rate'],
                'quantity' => $ingredient['quantity'],
                'unit_id' => $ingredient['unit_id'],
                'raw_material_price' => $ingredient['unit_price']
            ]);
        }
        return $recipe;
    }

    public function updateManufacturing($request, $id)
    {
        // Find existing recipe
        $recipe = ManufacturingRecipes::findOrFail($id);
        $materialsCost = collect($request->manufacturing_recipes)->sum(function ($ingredient) {
            return $ingredient['quantity'] * $ingredient['unit_price'];
        });
        // Update recipe
        $recipe->update([
            'final_product_id' => $request->final_product_id,
            'description' => $request->description,
            'total_wastage_rate' => $request->total_wastage_rate,
            'final_quantity' => $request->final_quantity,
            'unit_id' => $request->final_quantity_unit_id,
            'production_cost_type' => $request->production_cost_type,
            'production_cost_value' => $request->production_cost_value,
            'materials_cost' => $materialsCost,
            'total_cost' => $request->total_cost,
        ]);

        // Delete existing ingredients
        ManufacturingRecipeIngredients::where('recipe_id', $id)->delete();

        // Create new ingredients - modified to handle the new request structure
        foreach ($request->manufacturing_recipes as $key => $ingredient) {
            // Skip entries that don't have necessary data
            if (empty($ingredient['quantity']) || empty($ingredient['unit_id'])) {
                continue;
            }

            ManufacturingRecipeIngredients::create([
                'recipe_id' => $recipe->id,
                'raw_material_id' => $ingredient['product_id'] ?? null,
                'wastage_rate' => $ingredient['wastage_rate'] ?? 0,
                'quantity' => $ingredient['quantity'],
                'unit_id' => $ingredient['unit_id'],
                'raw_material_price' => $ingredient['unit_price'] ?? 0
            ]);
        }

        return $recipe;
    }

    public function storeProductionLine($request)
    {
        // dd($request);
        try {
            DB::beginTransaction();
            if (!isset($request['is_ended'])) {
                $request['is_ended'] = 0;
            }
            $recipe = ManufacturingRecipes::with(['ingredients.rawMaterial', 'ingredients.unit', 'finalProduct'])
                ->findOrFail($request['recipe_id']);

            // Change Purchase Price Of Final Product
            $newPurcahsePrice = $recipe->materials_cost / $request['quantity'];
            // dd($newPurcahsePrice);
            $finalProduct = ProductUnitDetails::where('product_id', $recipe->final_product_id)
                ->where('unit_id', $request['quantity_unit_id'])
                ->first();

            $finalProductUpdated = ProductUnitDetails::updateOrCreate(
                [
                    'product_id' => $recipe->final_product_id,
                    'unit_id' => $request['quantity_unit_id']
                ],
                [
                    'purchase_price' => $newPurcahsePrice,
                    'sale_price' => $finalProduct->sale_price ?? 0,
                ]
            );
            // Create Record In ProductPriceHistory For ChangeOnPrices Report
            ProductPriceHistory::create([
                'product_id' => $recipe->final_product_id,
                'old_unit_price' => $finalProduct->purchase_price,
                'new_unit_price' => $newPurcahsePrice,
                'unit_id' => $request['quantity_unit_id'],
                'changed_by' => auth()->id()
            ]);
            if ($request['is_ended'] == 1) {
                foreach ($request['ingredients'] as $rawMaterialId => $ingredientData) {
                    $requiredQty = $ingredientData['quantity'];
                    // dd($requiredQty);
                    $availableQty = ProductBranchDetails::where('product_id', $rawMaterialId)
                        ->where('branch_id', $request['branch_id'])
                        ->first()
                        ->qty_available ?? 0;
                    $ProductName = Product::find($rawMaterialId)->name;
                    if ($availableQty < $requiredQty) {
                        throw new \Exception('لا يوجد كمية كافية ل' . $ProductName);
                    }
                    $this->stockService->SubtractFromStock(
                        $rawMaterialId,
                        $request['branch_id'],
                        $requiredQty,
                        null,
                        $ingredientData['unit_id']
                    );
                }
            }

            $productionLineCode = $this->production_line_code($request);
            $transaction = $this->createProductionTransaction($request, $recipe);
            $finalQuantity = $request['quantity'] - ($request['quantity'] * $request['total_wastage_rate'] / 100);

            $productionLine = ManufacturingProductionLines::create([
                'production_line_code' => $request['production_line_code'] ?? $productionLineCode,
                'transaction_id' => $transaction->id,
                'date' => now(),
                'branch_id' => $request['branch_id'],
                'recipe_id' => $request['recipe_id'],
                'production_quantity' => $finalQuantity,
                'quantity_unit_id' => $request['quantity_unit_id'],
                'production_cost_type' => $request['production_cost_type'],
                'production_cost_value' => $request['production_cost_value'],
                'wastage_rate' => $request['total_wastage_rate'],
                'wastage_rate_unit_id' => $request['wastage_rate_unit_id'],
                'production_total_cost' => $request['total_cost'],
                'created_by' => auth()->id(),
                'is_ended' => $request['is_ended'] ?? false,
                'materials_cost' => $request['total_material_cost']
            ]);
            foreach ($request['ingredients'] as $rawMaterialId => $ingredientData) {
                ProductionLineIngredients::create([
                    'production_line_id' => $productionLine->id,
                    'raw_material_id' => $rawMaterialId,
                    'quantity' => $ingredientData['quantity'],
                    'unit_id' => $ingredientData['unit_id'],
                    'raw_material_price' => $ingredientData['unit_price'] * $ingredientData['quantity'],
                ]);
            }
            if ($request['is_ended'] == 1) {
                $this->stockService->addToStock(
                    $recipe->final_product_id,
                    $request['branch_id'],
                    $finalQuantity,
                    null,
                    $request['quantity_unit_id']
                );
            }
            $finalProductStock = ProductBranchDetails::where('product_id', $recipe->final_product_id)->where('branch_id', $request['branch_id']);
            if (!$finalProductStock) {
                ProductBranchDetails::create([
                    'branch_id' => $request['branch_id'],
                    'product_id' => $recipe->final_product_id,
                    'qty_available' => $finalQuantity
                ]);
            }
            $productBranch = ProductBranch::where('product_id', $recipe->final_product_id)->where('branch_id', $request['branch_id'])->first();
            // dd($productBranch);
            if ($productBranch === null) {
                ProductBranch::create([
                    'product_id' => $recipe->final_product_id,
                    'branch_id' => $request['branch_id'],
                ]);
            }

            DB::commit();
            return $productionLine;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    private function createProductionTransaction($request, $recipe)
    {
        $transactionData = [
            'branch_id' => $request['branch_id'],
            'type' => 'manufacturing',
            'status' => 'final',
            'transaction_date' => now(),
            'ref_no' => $request['production_line_code'],
            'created_by' => auth()->id()
        ];

        return $this->transactionService->CreateTransaction($transactionData);
    }

    public function updateProductionLine($request, $id)
    {
        try {
            DB::beginTransaction();

            $productionLine = ManufacturingProductionLines::with([
                'ProductionLineIngredients',
                'recipe.finalProduct'
            ])->findOrFail($id);

            if ($productionLine->is_ended) {
                $this->stockService->SubtractFromStock(
                    $productionLine->prodProductionLineIngredients->raw_material_id,
                    $productionLine->branch_id,
                    $productionLine->prodProductionLineIngredients->quantity,
                    null,
                    $productionLine->quantity_unit_id
                );

                foreach ($productionLine->ProductionLineIngredients as $ingredient) {
                    $this->stockService->addToStock(
                        $ingredient->raw_material_id,
                        $productionLine->branch_id,
                        $ingredient->quantity,
                        null,
                        $ingredient->unit_id
                    );
                }
            }

            $recipe = ManufacturingRecipes::with(['ingredients.rawMaterial', 'ingredients.unit', 'finalProduct'])
                ->findOrFail($request['recipe_id']);

            $finalQuantity = $request['quantity'] - ($request['quantity'] * $request['total_wastage_rate'] / 100);

            if ($request['is_ended'] == 1) {
                foreach ($recipe->ingredients as $ingredient) {
                    $requiredQty = $ingredient->quantity;
                    $availableQty = ProductBranchDetails::where('product_id', $ingredient->raw_material_id)
                        ->where('branch_id', $request['branch_id'])
                        ->first()
                        ->qty_available ?? 0;

                    if ($availableQty < $requiredQty) {
                        throw new \Exception('لا يوجد كمية كافية ل' . $ingredient->rawMaterial->name);
                    }
                }
            }

            // Update production line
            $productionLine->update([
                'branch_id' => $request['branch_id'],
                'recipe_id' => $request['recipe_id'],
                'production_quantity' => $finalQuantity,
                'quantity_unit_id' => $request['quantity_unit_id'],
                'production_cost_type' => $request['production_cost_type'],
                'production_cost_value' => $request['production_cost_value'],
                'wastage_rate' => $request['total_wastage_rate'],
                'wastage_rate_unit_id' => $request['wastage_rate_unit_id'],
                'production_total_cost' => $request['total_cost'],
                'is_ended' => $request['is_ended'] ?? false,
                'materials_cost' => $request['total_material_cost'],
            ]);
            foreach ($request['ingredients'] as $rawMaterialId => $ingredientData) {
                $productionLineIngredient = ProductionLineIngredients::where('production_line_id', $productionLine->id)
                    ->where('raw_material_id', $rawMaterialId)
                    ->first();

                $productionLineIngredient->update([
                    'quantity' => $ingredientData['quantity'],
                    'unit_id' => $ingredientData['unit_id'],
                    'raw_material_price' => $ingredientData['unit_price'] * $ingredientData['quantity'],
                ]);
            }
            if ($request['is_ended'] == 1) {
                $newPurchasePrice = $recipe->materials_cost / $request['quantity'];
                $product = ProductUnitDetails::find($recipe->final_product_id);
                ProductUnitDetails::updateOrCreate(
                    [
                        'product_id' => $recipe->final_product_id,
                        'unit_id' => $request['quantity_unit_id']
                    ],
                    [
                        'purchase_price' => $newPurchasePrice,
                        'sale_price' => $product->sale_price ?? 0,
                    ]
                );

                foreach ($request['ingredients'] as $rawMaterialId => $ingredientData) {
                    $this->stockService->SubtractFromStock(
                        $rawMaterialId,
                        $request['branch_id'],
                        $ingredientData['quantity'],
                        null,
                        $ingredientData['unit_id']
                    );
                }

                $this->stockService->addToStock(
                    $recipe->final_product_id,
                    $request['branch_id'],
                    $finalQuantity,
                    null,
                    $request['quantity_unit_id']
                );
            }

            // Update transaction
            if ($productionLine->transaction_id) {
                $this->transactionService->UpdateProductionTransaction([
                    'branch_id' => $request['branch_id'],
                    'ref_no' => $productionLine->production_line_code,
                    'transaction_date' => now(),
                ], $productionLine->transaction_id);
            }

            DB::commit();
            return $productionLine;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    public function deleteProductionLine($id)
    {
        try {
            DB::beginTransaction();

            $productionLine = ManufacturingProductionLines::with([
                'recipe.finalProduct',
                'recipe.ingredients'
            ])->findOrFail($id);

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

            if ($productionLine->transaction_id) {
                $this->transactionService->DeleteTransaction($productionLine->transaction_id);
            }

            $productionLine->delete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    private function production_line_code(array $request)
    {
        $recipe = ManufacturingRecipes::find($request['recipe_id']);

        if (!$recipe) {
            throw new \Exception('الوصفة غير موجودة');
        }

        $lastCode = ManufacturingProductionLines::latest('id')->value('production_line_code');

        $code = $lastCode ? intval(str_replace('PL-', '', $lastCode)) : 0;

        $productionLineCode = 'PL-' . ($code + 1);

        return $productionLineCode;
    }
}
