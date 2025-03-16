<?php

namespace App\Services;

use App\Models\ManufacturingProductionLines;
use App\Models\Product;
use App\Models\ProductBranchDetails;
use App\Models\ProductWarehouseDetail;
use App\Models\Setting;
use App\Models\Transaction;
use App\Traits\Stock;

class StockService
{
    use Stock;

    public function getTypeOperation($type)
    {
        return Transaction::TYPE[$type];
    }

    public function bulckAddToStockByPurchaseLines($transaction, $TransactionPurchaseLines)
    {
        foreach ($TransactionPurchaseLines as $line) {
            if ($line->quantity) {
                $this->addToStock(
                    $line->product_id,
                    $transaction->branch_id,
                    $line->main_unit_quantity,
                    $line->warehouse_id
                );
            }
        }
    }

    public function bulckAddToStockBySpoiledLinesLines($transaction, $TransactionSpoiledLines)
    {
        foreach ($TransactionSpoiledLines as $line) {
            if ($line->quantity) {
                $this->addToStock(
                    $line->product_id,
                    $transaction->branch_id,
                    $line->main_unit_quantity,
                    $line->warehouse_id
                );
            }
        }
    }

    public function bulckSubtractFromStockBySpoiledLinesLines($transaction, $TransactionSpoiledLines)
    {
        foreach ($TransactionSpoiledLines as $line) {
            if ($line->quantity) {
                $this->SubtractFromStock(
                    $line->product_id,
                    $transaction->branch_id,
                    $line->main_unit_quantity,
                    $line->warehouse_id
                );
            }
        }
    }

    public function bulckSubtractFromStockBySellLines($transaction, $TransactionSellLines)
    {
        foreach ($TransactionSellLines as $line) {
            if ($line->quantity) {
                $this->SubtractFromStock(
                    $line->product_id,
                    $transaction->branch_id,
                    $line->main_unit_quantity,
                    $line->warehouse_id
                );
            }
        }
    }

    public function bulckAddToStockByRetunLines($transaction, $TransactionRetunLines)
    {
        foreach ($TransactionRetunLines as $line) {
            if ($line->quantity) {
                $this->addToStock(
                    $line->product_id,
                    $transaction->branch_id,
                    $line->main_unit_quantity,
                    $line->warehouse_id
                );
            }
        }
    }

    public function bulckSubtractFromStockByRetunLines($transaction, $TransactionRetunLines)
    {
        foreach ($TransactionRetunLines as $line) {
            if ($line->quantity) {
                $this->SubtractFromStock(
                    $line->product_id,
                    $transaction->branch_id,
                    $line->main_unit_quantity,
                    $line->warehouse_id
                );
            }
        }
    }

    public function addToStock(
        $product_id,
        $branch_id,
        $quantity,
        $warehouse_id,
        $unit_id = null,
    ) {
        $ProductBranchDetails = $this->getProductBranchDetails($product_id, $branch_id, $warehouse_id);

        $mainQuantity = $this->getMainUnitQuantityFromSubUnit(
            $ProductBranchDetails->Product,
            $unit_id,
            $quantity
        );

        $ProductBranchDetails->qty_available += $mainQuantity;
        $ProductBranchDetails->save();
    }

    public function SubtractFromStock($product_id, $branch_id, $quantity, $warehouse_id, $unit_id = null)
    {
        $ProductBranchDetails = $this->getProductBranchDetails($product_id, $branch_id, $warehouse_id);
        $mainQuantity = $this->getMainUnitQuantityFromSubUnit($ProductBranchDetails->Product, $unit_id, $quantity);

        if ($mainQuantity <= $ProductBranchDetails->qty_available) {
            $ProductBranchDetails->qty_available -= $mainQuantity;
            $ProductBranchDetails->save();
            return;
        }

        $remainingQuantity = $mainQuantity - $ProductBranchDetails->qty_available;
        $ProductBranchDetails->qty_available = 0;
        $ProductBranchDetails->save();

        if ($remainingQuantity > 0) {
            throw new \Exception('Insufficient stock to fulfill the requested quantity.');
        }
    }

    public function history($product, $branch_id)
    {
        $settings = Setting::first();
        $warehouseCondition = $settings->display_warehouse ? null : function ($query) {
            $query->where('warehouse_id', null);
        };
        $data = [];
        $units = $product->sub_unit_ids;
        $mainUnit_id = $product->MainUnit->id;
        $mainUnit_id = array_search($mainUnit_id, $units);

        if ($mainUnit_id !== false) {
            unset($units[$mainUnit_id]);
            $units = array_values($units);
        }

        $sub_unit = $units[0] ?? null;

        $manufacturingLines = ManufacturingProductionLines::with([
            'ProductionLineIngredients.unit',
            'created_by'
        ])
            ->where('branch_id', $branch_id)
            ->where('is_ended', true)
            ->whereHas('ProductionLineIngredients', function ($query) use ($product) {
                $query->where('raw_material_id', $product->id);
            })
            ->get();

        foreach ($manufacturingLines as $line) {
            $ingredient = $line->ProductionLineIngredients->where('raw_material_id', $product->id)->first();
            if ($ingredient) {
                $quantity_by_sub_unit = $this->getQuantityByUnit($product, $sub_unit, $ingredient->quantity);
                $change_quantity_by_subunit = $sub_unit ? $quantity_by_sub_unit * (-1) : 'لا يوجد وحده فرعيه';

                $data[] = [
                    'change_quantity' => $ingredient->quantity * (-1),
                    'change_quantity_by_subunit' => $change_quantity_by_subunit,
                    'created_at' => date('Y-m-d h:i a', strtotime($line->created_at)),
                    'created_at_timestamp' => $line->created_at,
                    'unit_price' => $ingredient->raw_material_price,
                    'warehouse' => $line->warehouse_id ? $line->warehouse->name : 'الفرع',
                    'ref_no' => '<a href="#" style="color: blue;" class="fire-popup" data-url="'
                        . route('dashboard.production.show', $line->id)
                        . '" data-toggle="modal" data-target="#modal-default-big">'
                        . $line->production_line_code . '</a>',
                    'type' => 'manufacturing',
                    'is_settle' => false,
                    'created_by' => optional($line->created_by)->name ?? '',
                ];
            }
        }

        // Add manufacturing records - final product production
        $manufacturingProductLines = ManufacturingProductionLines::with(['recipe', 'created_by'])
            ->where('branch_id', $branch_id)
            ->where('is_ended', true)
            ->whereHas('recipe', function ($query) use ($product) {
                $query->where('final_product_id', $product->id);
            })
            ->get();

        foreach ($manufacturingProductLines as $line) {
            $finalQuantity = $line->production_quantity - ($line->production_quantity * $line->wastage_rate / 100);
            $quantity_by_sub_unit = $this->getQuantityByUnit($product, $sub_unit, $finalQuantity);
            $change_quantity_by_subunit = $sub_unit ? $quantity_by_sub_unit : 'لا يوجد وحده فرعيه';

            $data[] = [
                'change_quantity' => $finalQuantity,
                'change_quantity_by_subunit' => $change_quantity_by_subunit,
                'created_at' => date('Y-m-d h:i a', strtotime($line->created_at)),
                'created_at_timestamp' => $line->created_at,
                'unit_price' => $finalQuantity > 0 ? $line->production_total_cost / $finalQuantity : 0,
                'warehouse' => $line->warehouse_id ? $line->warehouse->name : 'الفرع',
                'ref_no' => '<a href="#" style="color: blue;" class="fire-popup" data-url="'
                    . route('dashboard.production.show', $line->id)
                    . '" data-toggle="modal" data-target="#modal-default-big">'
                    . $line->production_line_code . '</a>',
                'type' => 'manufacturing',
                'is_settle' => false,
                'created_by' => optional($line->created_by)->name ?? '',
            ];
        }
        // purchaseLines
        $transactionPurchaseLinesQuery = $product
            ->TransactionPurchaseLines()
            ->with('Transaction')
            ->with('Transaction.Contact')
            ->when($warehouseCondition, $warehouseCondition)
            ->whereHas('Transaction', function ($query) use ($branch_id, $settings) {
                $query
                    ->where('branch_id', $branch_id)
                    ->where(function ($query) use ($settings) {
                        $query
                            ->where('type', 'purchase')
                            ->where('delivery_status', 'delivered')
                            ->orWhere('type', '!=', 'purchase');
                    });
            });

        $transactionPurchaseLines = $transactionPurchaseLinesQuery->get();

        foreach ($transactionPurchaseLines as $line) {
            $return_quantity = 0;
            if ($line->return_quantity > 0) {
                $ProductBranchDetails = $this->getProductBranchDetails($line->product_id, $line->Transaction->branch_id, $line->warehouse_id);
                $return_quantity = $this->getMainUnitQuantityFromSubUnit(
                    $ProductBranchDetails->Product,
                    $line->unit_id,
                    $line->return_quantity
                );
            }

            $quantity_by_sub_unit = $this->getQuantityByUnit($product, $sub_unit, ($line->main_unit_quantity + $return_quantity));
            $change_quantity_by_subunit = 'لا يوجد وحده فرعيه';
            if ($sub_unit) {
                $change_quantity_by_subunit = $quantity_by_sub_unit * $this->getTypeOperation($line->transaction->type);
            }

            array_push($data, [
                'change_quantity' => ($line->main_unit_quantity + $return_quantity) * $this->getTypeOperation($line->transaction->type),
                'change_quantity_by_subunit' => $change_quantity_by_subunit,
                'created_at' => date('Y-m-d h:i a', strtotime($line->created_at)),
                'created_at_timestamp' => $line->created_at,
                'unit_price' => $line->unit_price,
                'warehouse' => $line->warehouse_id ? $line->warehouse->name : ' الفرع',
                'ref_no' => '<a href="#" style="color: blue;" class="fire-popup" data-url="' . route('dashboard.purchases.show', $line->transaction->id) . '" data-toggle="modal" data-target="#modal-default-big">' . $line->transaction->ref_no . '</a>',
                'type' => $line->transaction->type,
                'is_settle' => $line->transaction->is_settle ?? false,
                'created_by' => $line->transaction->CreatedBy?->name,
                'contact_name' => $line->transaction->contact->name ?? '',
            ]);
        }

        // sell line
        $TransactionSellLinesQuery = $product
            ->TransactionSellLines()
            ->with('Transaction')
            ->with('Transaction.Contact')
            ->when($warehouseCondition, $warehouseCondition)
            ->whereHas('Transaction', function ($query) use ($branch_id, $settings) {
                $query->where('branch_id', $branch_id)->where('payment_status', '!=', 'vault');
            });

        $TransactionSellLines = $TransactionSellLinesQuery->get();

        foreach ($TransactionSellLines as $line) {
            $return_quantity = 0;
            if ($line->return_quantity > 0) {
                $ProductBranchDetails = $this->getProductBranchDetails($line->product_id, $line->Transaction->branch_id, $line->warehouse_id);
                $return_quantity = $this->getMainUnitQuantityFromSubUnit(
                    $ProductBranchDetails->Product,
                    $line->unit_id,
                    $line->return_quantity
                );
            }

            $quantity_by_sub_unit = $this->getQuantityByUnit($product, $sub_unit, ($line->main_unit_quantity + $return_quantity));
            $change_quantity_by_subunit = 'لا يوجد وحده فرعيه';
            if ($sub_unit) {
                $change_quantity_by_subunit = $quantity_by_sub_unit * $this->getTypeOperation($line->transaction->type);
            }

            array_push($data, [
                'change_quantity' => ($line->main_unit_quantity + $return_quantity) * $this->getTypeOperation($line->transaction->type),
                'change_quantity_by_subunit' => $change_quantity_by_subunit,
                'created_at' => date('Y-m-d h:i a', strtotime($line->created_at)),
                'created_at_timestamp' => $line->created_at,
                'unit_price' => $line->unit_price,
                'ref_no' => '<a href="#" style="color: blue;" class="fire-popup" data-url="' . route('dashboard.sells.show', $line->transaction->id) . '" data-toggle="modal" data-target="#modal-default-big">' . $line->transaction->ref_no . '</a>',
                'type' => $line->transaction->type,
                'is_settle' => $line->transaction->is_settle ?? false,
                'warehouse' => $line->warehouse_id ? $line->warehouse->name : 'الفرع',
                'created_by' => $line->transaction->CreatedBy?->name,
                'contact_name' => $line->transaction->contact->name ?? '',
            ]);
        }

        // SpoiledLines
        $TransactionSpoiledLinesQuery = $product
            ->SpoiledLines()
            ->with('Transaction')
            ->when($warehouseCondition, $warehouseCondition)
            ->whereHas('Transaction', function ($query) use ($branch_id, $settings) {
                $query->where('branch_id', $branch_id)->where('status', 'final');
            });

        $TransactionSpoiledLines = $TransactionSpoiledLinesQuery->get();

        foreach ($TransactionSpoiledLines as $line) {
            $quantity_by_sub_unit = $this->getQuantityByUnit($product, $sub_unit, $line->main_unit_quantity);
            $change_quantity_by_subunit = 'لا يوجد وحده فرعيه';
            if ($sub_unit) {
                $change_quantity_by_subunit = $quantity_by_sub_unit * $this->getTypeOperation($line->transaction->type);
            }

            array_push($data, [
                'change_quantity' => $line->main_unit_quantity * $this->getTypeOperation($line->transaction->type),
                'change_quantity_by_subunit' => $change_quantity_by_subunit,
                'created_at' => date('Y-m-d h:i a', strtotime($line->created_at)),
                'created_at_timestamp' => $line->created_at,
                'unit_price' => $line->unit_price,
                'warehouse' => $line->warehouse_id ? $line->warehouse->name : 'الفرع',
                'ref_no' => '',
                'type' => $line->transaction->type,
                'is_settle' => $line->transaction->is_settle ?? false,
                'created_by' => $line->transaction->CreatedBy?->name,
            ]);
        }

        // transfer lines (from)
        $TransactionTransferLinesQuery = $product
            ->TransferLines()
            ->with('Transaction')
            ->whereHas('Transaction', function ($query) use ($branch_id, $settings) {
                $query
                    ->where(function ($q) use ($branch_id) {
                        $q
                            ->where('branch_id', $branch_id)
                            ->orWhere('branch_to_id', $branch_id);
                    })
                    ->where(function ($q) {
                        $q
                            ->where('warehouse_id', null)
                            ->where('warehouse_to_id', null);
                    })
                    ->where('status', 'final');
            });

        $TransactionTransferLines = $TransactionTransferLinesQuery->get();

        foreach ($TransactionTransferLines as $line) {
            $quantity = $line->main_unit_quantity;
            if ($line->Transaction->branch_to_id == $branch_id) {
                $quantity *= -1;
            }

            $quantity_by_sub_unit = $this->getQuantityByUnit($product, $sub_unit, $quantity);
            $change_quantity_by_subunit = 'لا يوجد وحده فرعيه';
            if ($sub_unit) {
                $change_quantity_by_subunit = $quantity_by_sub_unit * $this->getTypeOperation($line->transaction->type);
            }

            array_push($data, [
                'change_quantity' => $quantity * $this->getTypeOperation($line->transaction->type),
                'change_quantity_by_subunit' => $change_quantity_by_subunit,
                'created_at' => date('Y-m-d h:i a', strtotime($line->created_at)),
                'created_at_timestamp' => $line->created_at,
                'unit_price' => $line->unit_price,
                'warehouse' => $line->warehouse_id ? $line->warehouse->name : 'الفرع',
                'ref_no' => '',
                'type' => $line->transaction->type,
                'is_settle' => $line->transaction->is_settle ?? false,
                'created_by' => $line->transaction->CreatedBy?->name,
            ]);
        }

        usort($data, function ($a, $b) {
            return strtotime($a['created_at_timestamp']) - strtotime($b['created_at_timestamp']);
        });

        // process
        $quantity = 0;
        $quantity_by_sub_unit = 0;

        foreach ($data as $key => $item) {
            $quantity = $quantity + $item['change_quantity'];
            if ($sub_unit) {
                $quantity_by_sub_unit = $quantity_by_sub_unit + $item['change_quantity_by_subunit'];
            } else {
                $quantity_by_sub_unit = '';
            }

            $data[$key]['quantity'] = $quantity;
            $data[$key]['quantity_by_subunit'] = $quantity_by_sub_unit;

            $data[$key]['change_quantity_string'] = ($item['change_quantity'] > 0) ? '+' . $item['change_quantity'] : $item['change_quantity'];
            if ($sub_unit) {
                $data[$key]['change_quantity_string_by_subunit'] = ($item['change_quantity_by_subunit'] > 0) ? '+' . $item['change_quantity_by_subunit'] : $item['change_quantity_by_subunit'];
            } else {
                $data[$key]['change_quantity_string_by_subunit'] = '';
            }
        }

        usort($data, function ($a, $b) {
            return strtotime($b['created_at_timestamp']) - strtotime($a['created_at_timestamp']);
        });

        return [
            'data' => $data,
            'quantity' => $quantity,
        ];
    }

    public function getProductBranchDetails($product_id, $branch_id, $warehouse_id)
    {
        $settings = Setting::first();
        $warehouseCondition = $settings->display_warehouse ? function ($query) use ($warehouse_id) {
            $query->where('warehouse_id', $warehouse_id);
        } : null;

        $ProductBranchDetails = ProductBranchDetails::where('product_id', $product_id)
            ->where('branch_id', $branch_id)
            ->when($warehouseCondition, $warehouseCondition)
            ->first();

        if ($ProductBranchDetails)
            return $ProductBranchDetails;

        return ProductBranchDetails::create([
            'product_id' => $product_id,
            'branch_id' => $branch_id,
            'warehouse_id' => $warehouse_id,
        ]);
    }

    public function getProductWarehouseDetails($product_id, $warehouse_id)
    {
        $ProductWarehouseDetails = ProductWarehouseDetail::where('product_id', $product_id)
            ->where('warehouse_id', $warehouse_id)
            ->first();

        if ($ProductWarehouseDetails) {
            return $ProductWarehouseDetails;
        } else {
            return ProductWarehouseDetail::create([
                'product_id' => $product_id,
                'warehouse_id' => $warehouse_id,
            ]);
        }
    }
}
