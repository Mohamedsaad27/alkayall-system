<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBranchDetails;
use App\Models\ProductUnitDetails;
use App\Models\ReferenceCount;
use App\Models\Setting;
use App\Models\Transaction;
use App\Traits\Stock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class TransactionService
{
    use Stock;

    public function CreateTransaction($data)
    {
        $userId = Auth::guard('user')->check() ? Auth::guard('user')->user()->id : null;
        $input = [
            'branch_id' => $data['branch_id'] ?? null,
            'branch_to_id' => $data['branch_to_id'] ?? null,
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'warehouse_to_id' => $data['warehouse_to_id'] ?? null,
            'type' => $data['type'] ?? null,
            'status' => $data['status'] ?? null,
            'transaction_from' => $data['transaction_from'] ?? 'dashboard',
            'transaction_date' => $data['transaction_date'] ?? now(),
            'contact_id' => $data['contact_id'] ?? null,
            'return_transaction_id' => $data['return_transaction_id'] ?? null,
            'ref_no' => generate_ref_no($data['type']),
            'payment_type' => $data['payment_type'] ?? 'cash',
            'created_by' => $userId,
            'discount_value' => $data['discount'] ?? null,
            'discount_type' => $data['discount_type'] ?? null,
            'total_due_before' => $data['total_due_before'] ?? null,
            'delivery_status' => $data['delivery_status'] ?? 'ordered',
            'payment_status' => $data['payment_status'] ?? 'final',
            'is_settle' => $data['is_settle'] ?? false
        ];

        return Transaction::create($input);
    }

    public function CreatePurchaseLines($transaction, $data, $purchase_lines_array)
    {
        foreach ($purchase_lines_array as $purchase_line) {
       
            $product = Product::findOrFail($purchase_line['product_id']);
      
            $purchase_line['unit_id'] = $purchase_line['unit_id'] ?? $product->unit_id;

            $mainQuantity = $this->getMainUnitQuantityFromSubUnit($product, $purchase_line['unit_id'], $purchase_line['quantity']);
            $getPricePurchaseProduct = $product->getPurchasePriceByUnit($purchase_line['unit_id']);

            if ($purchase_line['unit_price'] != $getPricePurchaseProduct) {
                $updatePricePurchase = ProductUnitDetails::where('product_id', $purchase_line['product_id'])
                    ->where('unit_id', $purchase_line['unit_id'])
                    ->first();

                $updatePricePurchase->update(['purchase_price' => $purchase_line['unit_price']]);
            }

            if ($purchase_line['quantity']) {
                $transaction
                    ->TransactionPurchaseLines()
                    ->create([
                        'product_id' => $purchase_line['product_id'],
                        'quantity' => $purchase_line['quantity'],
                        'unit_price' => $purchase_line['unit_price'],
                        'unit_id' => $purchase_line['unit_id'],
                        'warehouse_id' => $purchase_line['warehouse_id'] ?? null,
                        'main_unit_quantity' => $mainQuantity,
                    ]);
            }
        }

        return $transaction;
    }

    public function CreateSellLines($transaction, $data, $sell_lines_array)
    {
        $settings = Setting::first();
        $warehouseCondition = $settings->display_warehouse ? null : function ($query) {
            $query->where('warehouse_id', null);
        };

        foreach ($sell_lines_array as $purchase_line) {
            $product = Product::findOrFail($purchase_line['product_id']);
            $purchase_line['unit_id'] = $purchase_line['unit_id'] ?? $product->unit_id;
            $mainQuantity = $this->getMainUnitQuantityFromSubUnit($product, $purchase_line['unit_id'], $purchase_line['quantity']);
            $ProductBranchDetails = ProductBranchDetails::where('product_id', $purchase_line['product_id'])
                ->where('branch_id', $transaction->branch_id)
                ->where('warehouse_id', $purchase_line['warehouse_id'] ?? null)
                ->first();

            $branches = ProductBranchDetails::where('product_id', $purchase_line['product_id'])
                ->where('branch_id', $transaction->branch_id)
                ->when($warehouseCondition, $warehouseCondition)
                ->orderBy('updated_at', 'desc')
                ->get();

            $productPurchasePrice = $product->ProductUnitDetails->where('unit_id' , $purchase_line['unit_id'])->value('purchase_price');
                
            if (!$ProductBranchDetails) {
                throw new \Exception("المنتج {$product->name} غير متوفر في الفرع المحدد.");
            }

            $remainingQuantity = $mainQuantity;

            if ($remainingQuantity <= $ProductBranchDetails->qty_available) {
                $transaction->TransactionSellLines()->create([
                    'product_id' => $purchase_line['product_id'],
                    'quantity' => $purchase_line['quantity'],
                    'unit_price' => $purchase_line['unit_price'],
                    'unit_purchase_price' => $productPurchasePrice,
                    'total' => $purchase_line['quantity'] * $purchase_line['unit_price'],
                    'unit_id' => $purchase_line['unit_id'],
                    'warehouse_id' => $purchase_line['warehouse_id'] ?? null,
                    'main_unit_quantity' => $mainQuantity,
                ]);
                continue;
            }

            $transaction->TransactionSellLines()->create([
                'product_id' => $purchase_line['product_id'],
                'quantity' => $ProductBranchDetails->qty_available,
                'unit_price' => $purchase_line['unit_price'],
                'unit_purchase_price' => $productPurchasePrice,
                'total' => $ProductBranchDetails->qty_available * $purchase_line['unit_price'],
                'unit_id' => $purchase_line['unit_id'],
                'warehouse_id' => $purchase_line['warehouse_id'] ?? null,
                'main_unit_quantity' => $ProductBranchDetails->qty_available,
            ]);

            $remainingQuantity -= $ProductBranchDetails->qty_available;

            foreach ($branches as $branch) {
                if ($remainingQuantity <= 0) {
                    break;
                }

                $quantityFromWarehouse = min($branch->qty_available, $remainingQuantity);

                $transaction->TransactionSellLines()->create([
                    'product_id' => $purchase_line['product_id'],
                    'quantity' => $quantityFromWarehouse,
                    'unit_price' => $purchase_line['unit_price'],
                    'unit_purchase_price' => $productPurchasePrice,
                    'total' => $quantityFromWarehouse * $purchase_line['unit_price'],
                    'unit_id' => $purchase_line['unit_id'],
                    'warehouse_id' => $branch->warehouse_id ?? null,
                    'main_unit_quantity' => $quantityFromWarehouse,
                ]);

                $remainingQuantity -= $quantityFromWarehouse;
            }

            if ($remainingQuantity > 0) {
                throw new \Exception("الكمية المطلوبة أكبر من الكمية المتوفرة للمنتج {$product->name}.");
            }
        }

        return $transaction;
    }

    public function CreateRetunLines($transaction, $main_sell, $data, $return_lines_array)
    {
        foreach ($return_lines_array as $return_line) {
            $product = Product::findOrFail($return_line['product_id']);
            $return_line['unit_id'] = $return_line['unit_id'] ?? $product->unit_id;
            $mainQuantity = $this->getMainUnitQuantityFromSubUnit($product, $return_line['unit_id'], $return_line['quantity']);
            if ($return_line['quantity']) {
                $transaction
                    ->TransactionPurchaseLines()
                    ->create([
                        'product_id' => $return_line['product_id'],
                        'quantity' => $return_line['quantity'],
                        'unit_price' => $return_line['unit_price'],
                        'unit_id' => $return_line['unit_id'],
                        'warehouse_id' => $return_line['warehouse_id'] ?? null,
                        'transactions_sell_line_id' => $return_line['transactions_sell_line_id'],
                        'main_unit_quantity' => $mainQuantity,
                    ]);

                $sell_line = $main_sell->TransactionSellLines()->where('id', $return_line['transactions_sell_line_id'])->first();
                if ($sell_line) {
                    $sell_line->update([
                        'quantity' => $sell_line['quantity'] - $return_line['quantity'],
                        'total' => ($sell_line['quantity'] - $return_line['quantity']) * $return_line['unit_price'],
                        'main_unit_quantity' => $sell_line['main_unit_quantity'] - $mainQuantity,
                        'return_quantity' => $sell_line['return_quantity'] + $return_line['quantity'],
                    ]);
                }
            }
        }
        return $transaction;
    }

    public function CreatePurchaseRetunLines($transaction, $main_purchase, $return_lines_array)
    {
        foreach ($return_lines_array as $return_line) {
            $product = Product::findOrFail($return_line['product_id']);
            $return_line['unit_id'] = $return_line['unit_id'] ?? $product->unit_id;
            $mainQuantity = $this->getMainUnitQuantityFromSubUnit($product, $return_line['unit_id'], $return_line['quantity']);

            if ($return_line['quantity']) {
                $transaction
                    ->TransactionSellLines()
                    ->create([
                        'product_id' => $return_line['product_id'],
                        'quantity' => $return_line['quantity'],
                        'unit_price' => $return_line['unit_price'],
                        'unit_id' => $return_line['unit_id'],
                        'warehouse_id' => $return_line['warehouse_id'] ?? null,
                        'transactions_purchase_line_id' => $return_line['transactions_purchase_line_id'],
                        'total' => $return_line['quantity'] * $return_line['unit_price'],
                        'main_unit_quantity' => $mainQuantity,
                    ]);
            }
            $sell_line = $main_purchase->TransactionPurchaseLines()->where('id', $return_line['transactions_purchase_line_id'])->first();
            if ($sell_line) {
                $sell_line->update([
                    'quantity' => $sell_line['quantity'] - $return_line['quantity'],
                    'main_unit_quantity' => $sell_line['main_unit_quantity'] - $mainQuantity,
                    'return_quantity' => $sell_line['return_quantity'] + $return_line['quantity'],
                ]);
            }
        }
        return $transaction;
    }

    public function UpdateSellLinesAfterRetun() {}

    public function DeleteTransaction($transaction)
    {
        $transaction->delete();
    }

    public function UpdateTransaction($transaction, $data)
    {
        $transaction->update($data);
    }

    public function UpdateTransactionPurchaseLines($transaction, $data)
    {
        foreach ($data as $purchase_line) {
            $transaction->TransactionPurchaseLines()->update($purchase_line);
        }
    }

    public function UpdateOrCreateTransactionSellLines($transaction, $sell_lines_array)
    {
        // dd($sell_lines_array);
        $products_ids = [];
        foreach ($sell_lines_array as $item) {
            $product = Product::findOrFail($item['product_id']);
            $item['unit_id'] = $item['unit_id'] ?? $product->unit_id;
            $mainQuantity = $this->getMainUnitQuantityFromSubUnit($product, $item['unit_id'], $item['quantity']);
            $data = [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $item['quantity'] * $item['unit_price'],
                'unit_id' => $item['unit_id'],
                'main_unit_quantity' => $mainQuantity,
            ];
            $sell_line = $transaction
                ->TransactionSellLines()
                ->where('product_id', $item['product_id'])
                ->first();

            if ($sell_line) {
                $data['old_quantity'] = $sell_line->quantity;
                $products_ids[] = $sell_line->product_id;
                $sell_line->update($data);
            } else {
                $products_ids[] = $item['product_id'];
                $transaction
                    ->TransactionSellLines()
                    ->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'old_quantity' => 0,
                        'unit_price' => $item['unit_price'],
                        'total' => $item['quantity'] * $item['unit_price'],
                        'unit_id' => $item['unit_id'],
                        'main_unit_quantity' => $mainQuantity,
                    ]);
            }
        }

        $productsRemove = $transaction->TransactionSellLines()->whereNotIn('product_id', $products_ids)->delete();
    }

    public function UpdateOrCreateTransactionPurchaseLines($transaction, $purchase_lines_array)
    {
        $products_ids = [];
        foreach ($purchase_lines_array as $item) {
            $product = Product::findOrFail($item['product_id']);
            $item['unit_id'] = $item['unit_id'] ?? $product->unit_id;
            $mainQuantity = $this->getMainUnitQuantityFromSubUnit($product, $item['unit_id'], $item['quantity']);

            $data = [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'unit_id' => $item['unit_id'],
                'main_unit_quantity' => $mainQuantity,
            ];
            $purchase_line = $transaction
                ->TransactionPurchaseLines()
                ->where('product_id', $item['product_id'])
                ->first();
            if ($purchase_line) {
                $products_ids[] = $purchase_line->product_id;
                $purchase_line->update($data);
            } else {
                $products_ids[] = $item['product_id'];
                $transaction
                    ->TransactionPurchaseLines()
                    ->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'unit_id' => $item['unit_id'],
                        'main_unit_quantity' => $mainQuantity,
                    ]);
            }
        }

        $productsRemove = $transaction->TransactionPurchaseLines()->whereNotIn('product_id', $products_ids)->delete();
    }

    public function CreateTransferLines($transaction, $transfer_lines_array)
    {
        foreach ($transfer_lines_array as $transfer_line) {
            $product = Product::findOrFail($transfer_line['product_id']);
            $transfer_line['unit_id'] = $transfer_line['unit_id'] ?? $product->unit_id;
            $transaction
                ->TransferLines()
                ->create([
                    'product_id' => $transfer_line['product_id'],
                    'quantity' => $transfer_line['quantity'],
                    'unit_id' => $transfer_line['unit_id'],
                    'main_unit_quantity' => $transfer_line['main_unit_quantity']
                ]);
        }
        return $transaction;
    }

    public function CreateSpoiledLines($transaction, $spoiled_lines_array)
    {
        foreach ($spoiled_lines_array as $spoiled_line) {
            $product = Product::findOrFail($spoiled_line['product_id']);
            $spoiled_line['unit_id'] = $spoiled_line['unit_id'] ?? $product->unit_id;
            $transaction
                ->SpoiledLines()
                ->create([
                    'product_id' => $spoiled_line['product_id'],
                    'quantity' => $spoiled_line['quantity'],
                    'unit_id' => $spoiled_line['unit_id'],
                    'main_unit_quantity' => $spoiled_line['main_unit_quantity'],
                    'warehouse_id' => $spoiled_line['warehouse_id'] ?? null,
                ]);
        }
        return $transaction;
    }

    public function UpdateProductionTransaction($data, $transactionId)
    {
        // Find the transaction model first
        $transaction = Transaction::findOrFail($transactionId);

        // Then update it
        $transaction->update($data);
    }
}
