<?php

namespace App\Services;

use App\Traits\Stock;
use App\Models\Contact;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Setting;
use App\Models\TaxRate;
use App\Models\Transaction;
use App\Models\Activity_log;
use App\Models\TransactionTax;
use App\Models\SaleUpdateHistory;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use App\Models\ProductBranchDetails;

class SellService
{
    use Stock;
    public $StockService;
    public $TransactionService;
    public $PaymentTransactionService;
    public $ActivityLogsService; // Added ActivityLogsService
    public function __construct(
        StockService $StockService,
        TransactionService $TransactionService,
        PaymentTransactionService $PaymentTransactionService,
        ActivityLogsService $ActivityLogsService
    ) {
        $this->StockService = $StockService;
        $this->TransactionService = $TransactionService;
        $this->PaymentTransactionService = $PaymentTransactionService;
        $this->ActivityLogsService = $ActivityLogsService;
    }
    public function product_row($product, $branch_id, $sell_line = null)
    {
        $available_quantity = 0;
        $product_branch_details = $product->ProductBranchDetails()->where('branch_id', $branch_id)->first();
        if ($product_branch_details)
            $available_quantity = $product_branch_details->qty_available;

        $data = [
            'id' => $product->id,
            'name' => $product->name,
            'units' => $product->GetAllUnits(),
            'unit_id' => ($sell_line->unit_id) ?? null,
            'quantity' => ($sell_line->quantity) ?? 0,
            'available_quantity' => $available_quantity,
            'unit_price' => ($sell_line->unit_price) ?? $product->unit_price,
            'total' => ($sell_line->final_price) ?? 0,
            'min_sale' => $product->min_sale,
            'max_sale' => $product->max_sale,
        ];

        return $data;
    }

    public function CreateSell($data, $sell_lines_array, $request)
    {


        // Perform validations first
        $settings = Setting::first();
        $balance_contact = Contact::find($data['contact_id'])->balance;
        $account_id = $data["account_id"];
        unset($data["account_id"]);

        $data["type"] = 'sell';
        $data["total_due_before"] = $balance_contact;
        $data["transaction_date"] = now();
        isset($data['payment_type']) ? $input["status"] = $data['status'] : null;

        $data['payment_status'] = $data['payment_type'] == 'draft'
            ? 'draft'
            : ($settings->display_vault && $data['sell_type'] == 'cash'
                ? 'vault'
                : ($data['payment_type'] == 'cash'
                    ? 'final'
                    : 'due'));

        $transaction = $this->TransactionService->CreateTransaction($data);
        $transaction = $this->TransactionService->CreateSellLines($transaction, $data, $sell_lines_array);
        $taxes = $data['taxes'] ?? [];
        $taxTotal = 0;
        $sell_total = $this->getSellTotal($transaction);
        $discount_value = $data['discount_value'] ?? 0;
        $discount_type = $data['discount_type'] ?? null;

        if ($discount_type == 'percentage') {
            $discount_amount = ($sell_total * $discount_value) / 100;
            $discount_value = $discount_amount;
        } elseif ($discount_type == 'fixed_price') {
            $discount_amount = $discount_value;
        } else {
            $discount_amount = 0;
        }

        $final_total = $sell_total - $discount_amount;
        $taxEntries = [];
        if (!empty($taxes)) {
            foreach ($taxes as $tax_rate_id) {
                $taxRate = TaxRate::find($tax_rate_id);
                if ($taxRate) {
                    // Calculate tax amount based on final price after discount
                    $taxAmount = $final_total * ($taxRate->rate / 100);
                    $taxTotal += $taxAmount;

                    $taxEntries[] = [
                        'transaction_id' => $transaction->id,
                        'tax_rate_id' => $tax_rate_id,
                        'tax_amount' => $taxAmount,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
            }
            if (!empty($taxEntries)) {
                TransactionTax::insert($taxEntries);
            }
        }

        $final_total_with_taxes = $final_total + $taxTotal;

        $transaction->update([
            'total' => $sell_total,
            'discount_value' => $discount_value,
            'discount_type' => $discount_type,
            'tax_amount' => $taxTotal,
            'final_price' => $final_total_with_taxes
        ]);

        if ($input["status"] == "final" && !$settings->display_vault) {
            $this->StockService->bulckSubtractFromStockBySellLines($transaction, $transaction->TransactionSellLines);
        }
        if ($input["status"] == "final" && $settings->display_vault && $request->sell_type != 'cash') {
            $this->StockService->bulckSubtractFromStockBySellLines($transaction, $transaction->TransactionSellLines);
        }

        if ($input["status"] == "final" && !$settings->display_vault && $request->sell_type == 'cash') {

            $payment_data = [
                'transaction_id' => $transaction->id,
                'contact_id'     => $data['contact_id'] ?? null,
                'account_id'     => $account_id,
                'amount'         => $final_total_with_taxes,
                'method'         => 'cash',
                'operation'      => 'subtract',
                'contact_balace_no_effect' => true,
                'type'           => 'sell',
                'created_by'     => auth()->id(),
            ];

            $transaction->update(['payment_status' => 'final']);
            $this->PaymentTransactionService->Create($payment_data);
        } else if ($request->sell_type == 'credit' && $balance_contact < 0) {

            $payments = Payment::with('PaymentTransaction')
                ->withSum('PaymentTransaction', 'amount')
                ->where('contact_id', $data["contact_id"])
                ->get();
            $totalPaymentRemainder = 0;
            foreach ($payments as $payment) {

                $payment_transaction_sum_amount = $payment->payment_transaction_sum_amount;
                $remainder = $payment->amount - $payment_transaction_sum_amount;

                $totalPaymentRemainder += $remainder;
                if ($remainder == 0) {
                    continue;
                }

                $total = $transaction->final_price;
                if ($transaction->payment_status != "due") {
                    $total = $transaction->final_price - $transaction->load('PaymentsTransaction')->PaymentsTransaction->sum('amount');
                }


                if ($remainder >= $total) {
                    $PaymentTransaction = PaymentTransaction::create([
                        'payment_id' => $payment->id,
                        'transaction_id' => $transaction->id,
                        'contact_id' => $data['contact_id'],
                        'account_id' => $account_id,
                        'amount' => $total,
                        'method' => 'credit',
                    ]);

                    $transaction->update(['payment_status' => 'final']);

                    $this->PaymentTransactionService->ContactAdd($transaction->Contact, $total);
                    break;
                } else {

                    $PaymentTransaction = PaymentTransaction::create([
                        'payment_id' => $payment->id,
                        'transaction_id' => $transaction->id,
                        'contact_id' => $data['contact_id'],
                        'account_id' => $account_id,
                        'amount' => $remainder,
                        'method' => 'credit',
                    ]);

                    $transaction->update(['payment_status' => 'partial']);

                    $this->PaymentTransactionService->ContactAdd($transaction->Contact, $remainder);
                }
            }

            if ($transaction->payment_status == "partial") {
                $remainderPayment = $transaction->total - $transaction->load('PaymentsTransaction')->PaymentsTransaction->sum('amount');
                $this->PaymentTransactionService->ContactAdd($transaction->Contact, $remainderPayment);
            }

            // if no has payment and still $balance is less than 0 
            // $transactionOpeningBalance = Transaction::where('contact_id', $data['contact_id'])
            //     ->where('type', 'opening_balance')
            //     ->first();

            if ($totalPaymentRemainder == 0 && $balance_contact < 0) {

                $amount = '';

                if ($balance_contact * -1 >= $transaction->final_price) {
                    $amount = $transaction->final_price;

                    $transaction->update(['payment_status' => 'final']);
                } else {
                    $amount = $balance_contact * -1;
                    $transaction->update(['payment_status' => 'partial']);
                }

                $payment_data = [
                    'transaction_id' => $transaction->id,
                    'contact_id' => $data['contact_id'] ?? null,
                    'account_id' => $account_id,
                    'amount' => $amount,
                    'method' => 'credit',
                    'operation' => 'subtract',
                    'contact_balace_no_effect' => true,
                    'type' => 'sell',
                    'for' => 'decrement_opening_balance',
                ];

                $this->PaymentTransactionService->Create($payment_data);

                // $transactionOpeningBalance->increment('final_price',$amount);
                $this->PaymentTransactionService->ContactAdd($transaction->Contact, $transaction->final_price);
            }
        } else if ($request->sell_type == 'multi_pay') {

            $amount = $data['amount'];

            $payment_status = 'final';
            $amountDiffrence = 0;
            if ($final_total > $data['amount']) {
                $payment_status = 'partial';
                $amountDiffrence = $final_total - $data['amount'];
            }

            $payment_data = [
                'transaction_id' => $transaction->id,
                'contact_id' => $data['contact_id'] ?? null,
                'account_id' => $account_id,
                'amount' => $amount,
                'method' => 'cash',
                'operation' => 'subtract',
                'contact_balace_no_effect' => true,
                'type' => 'sell',
                'created_by' => auth()->id() ?? null,
            ];

            $transaction->update(['payment_status' => $payment_status]);

            $this->PaymentTransactionService->Create($payment_data);

            $this->PaymentTransactionService->ContactAdd($transaction->Contact, $amountDiffrence);
        } else {
            if (!$transaction->Contact->is_default) {
                $this->PaymentTransactionService->ContactAdd($transaction->Contact, $transaction->final_price);
            }
        }

        return $transaction;
    }

    public function UpdateSell($sellTransaction, $data, $sell_lines_array, $request)
    {
        $taxes = $request->taxes ?? [];
        $taxTotal = 0;
        $old_total = $this->getSellTotal($sellTransaction);
        $old_final_total = $sellTransaction->final_price;
        $changes = [];
        $old_lines = $sellTransaction->TransactionSellLines->keyBy('product_id')->toArray();
        $products_remotve_ids = [];
        $setting = Setting::first();
        //update stock
        if ($data["status"] == "final" && $sellTransaction->payment_status != "vault") {
            foreach ($sell_lines_array as $item) {
                $product = Product::findOrFail($item['product_id']);
                $item["unit_id"] = $item['unit_id'] ?? $product->unit_id;
                $mainQuantity = $this->getMainUnitQuantityFromSubUnit($product, $item['unit_id'], $item['quantity']);
                $sell_line = $sellTransaction->TransactionSellLines()
                    ->where('product_id', $item['product_id'])
                    ->first();

                if ($sell_line) {
                    $products_remotve_ids[] = $sell_line->product_id;
                } else {
                    $products_remotve_ids[] = $item['product_id'];
                }

                if (!isset($sell_line) || $mainQuantity != $sell_line->main_unit_quantity) {
                    if (!isset($sell_line) || $mainQuantity > $sell_line->main_unit_quantity) {
                        $new_quantity = (!isset($sell_line)) ? $mainQuantity : $mainQuantity - $sell_line->main_unit_quantity;
                        $this->StockService->SubtractFromStock(
                            $item['product_id'],
                            $sellTransaction->branch_id,
                            $new_quantity,
                            $sell_line->warehouse_id ?? null
                        );
                    } else {
                        $new_quantity = $sell_line->main_unit_quantity - $mainQuantity;
                        $this->StockService->addToStock(
                            $item['product_id'],
                            $sellTransaction->branch_id,
                            $new_quantity,
                            $sell_line->warehouse_id ?? null
                        );
                    }
                }
            }
        }

        // get product removed and update stock 
        $productsRemove = $sellTransaction->TransactionSellLines()->whereNotIn('product_id', $products_remotve_ids)->get();
       
        if ($data["status"] == "final" && $sellTransaction->payment_status != "vault") {
            foreach ($productsRemove as $productRemove) {
                $this->StockService->addToStock(
                    $productRemove->product_id,
                    $sellTransaction->branch_id,
                    $productRemove->main_unit_quantity,
                    $sell_line->warehouse_id ?? null
                );
            }
        }
    

        //update sell transaction
        $this->TransactionService->UpdateTransaction($sellTransaction, $data);
        $this->TransactionService->UpdateOrCreateTransactionSellLines($sellTransaction, $sell_lines_array);
        $sell_total = $this->getSellTotal($sellTransaction);

        // Handle discount
        $discount_value = $data['discount_value'] ?? 0;
        $discount_type = $data['discount_type'] ?? null;

        if ($discount_type == 'percentage') {
            $discount_amount = ($sell_total * $discount_value) / 100;
            $discount_value = $discount_amount;
        } elseif ($discount_type == 'fixed_price') {
            $discount_amount = $discount_value;
        } else {
            $discount_amount = 0;
        }

        $final_total = $sell_total - $discount_amount;

        // Handle taxes
        $taxEntries = [];
        if (!empty($taxes)) {
            foreach ($taxes as $tax_rate_id) {
                $taxRate = TaxRate::find($tax_rate_id);
                if ($taxRate) {
                    // Calculate tax amount based on final price after discount
                    $taxAmount = $final_total * ($taxRate->rate / 100);
                    $taxTotal += $taxAmount;

                    $taxEntries[] = [
                        'transaction_id' => $sellTransaction->id,
                        'tax_rate_id' => $tax_rate_id,
                        'tax_amount' => $taxAmount,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
            }

            // Delete existing tax entries and insert new ones
            TransactionTax::where('transaction_id', $sellTransaction->id)->delete();
            if (!empty($taxEntries)) {
                TransactionTax::insert($taxEntries);
            }
        }

        $final_total_with_taxes = $final_total + $taxTotal;

        // Prepare changes for history
        $new_total = $sell_total;
        foreach ($sell_lines_array as $line) {
            $product = Product::find($line['product_id']);
            $old_line = $old_lines[$line['product_id']] ?? null;
            if (
                !$old_line ||
                $old_line['quantity'] != $line['quantity'] ||
                $old_line['unit_price'] != $line['unit_price']
            ) {
                $changes[] = sprintf(
                    "Product: %s - Old Qty: %s, New Qty: %s, Old Price: %s, New Price: %s",
                    $product->name,
                    $old_line['quantity'] ?? 0,
                    $line['quantity'],
                    $old_line['unit_price'] ?? 0,
                    $line['unit_price']
                );
            }
        }

        // Create update history
        SaleUpdateHistory::create([
            'transaction_id' => $sellTransaction->id,
            'old_total' => $old_total,
            'new_total' => $new_total,
            'old_final_price' => $old_line['quantity'] ?? $line['quantity'],
            'new_final_price' => $line['quantity'],
            'changes_summary' => implode("\n", $changes),
            'updated_by' => auth()->id()
        ]);

        // Update transaction with new totals
        $sellTransaction->update([
            'total' => $sell_total,
            'discount_value' => $discount_value,
            'discount_type' => $discount_type,
            'tax_amount' => $taxTotal,
            'final_price' => $final_total_with_taxes
        ]);

        // Handle payment transaction
        if ($sellTransaction->PaymentTransaction && $sellTransaction->payment_type == 'cash') {
            $this->PaymentTransactionService->Update($sellTransaction->PaymentTransaction, $final_total_with_taxes, true);
        } else {

            if ($final_total > $old_final_total) {
                $this->PaymentTransactionService
                    ->ContactAdd($sellTransaction->Contact, abs($old_final_total - $final_total));
            } else {
                $this->PaymentTransactionService
                    ->ContactSubtract($sellTransaction->Contact, abs($old_final_total - $final_total));
            }
        }

        return $sellTransaction;
    }

    public function getSellTotal($transaction)
    {
        return $transaction->TransactionSellLines()
            ->select(DB::raw('SUM(quantity * unit_price) as total'))
            ->value('total');
    }

    public function delete($sell)
    {
        $settings = Setting::first();

        //remove payment trandaction
        if ($sell->PaymentTransaction) {
            $this->PaymentTransactionService->delete($sell->PaymentTransaction, true);
        } else {
            
            if (!$sell->Contact->is_default) {
                $this->PaymentTransactionService->ContactSubtract($sell->Contact, $sell->final_price);
            } 
        }
        if ($sell->payment_status != "vault") {
            //return stock
            foreach ($sell->TransactionSellLines as $line) {
                $this->StockService->addToStock(
                    $line->product_id,
                    $sell->branch_id,
                    $line->main_unit_quantity,
                    $line->warehouse_id
                );
            }
        }
        
        //delete
        $sell->TransactionSellLines()->delete();
        $sell->delete();
    }

    public function FinishSell($sellTransaction, $data, $sell_lines_array)
    {
        $settings = Setting::first();

        $balance_contact = Contact::find($sellTransaction->contact_id)->balance;
        $account_id = $data["account_id"];
        unset($data["account_id"]);

        $data["type"] = 'sell';
        $data["total_due_before"] = $balance_contact;
        $data["transaction_date"] = now();
        isset($data['payment_type']) ? $input["status"] = $data['status'] : null;

        $data['payment_status'] = $data['payment_type'] == 'draft'
            ? 'draft'
            : ($data['payment_type'] == 'cash' ? 'final' : 'due');
        $data['payment_status'] = ($settings->display_vault && $data['sell_type'] == 'cash')
            ? 'vault'
            : $data['payment_status'];


        $old_total = $this->getSellTotal($sellTransaction);

        //update stock
        if ($data["status"] == "final") {
            foreach ($sell_lines_array as $item) {
                $product = Product::findOrFail($item['product_id']);
                $item["unit_id"] = $item['unit_id'] ?? $product->unit_id;
                $mainQuantity = $this->getMainUnitQuantityFromSubUnit($product, $item['unit_id'], $item['quantity']);
                $sell_line = $sellTransaction->TransactionSellLines()
                    ->where('product_id', $item['product_id'])
                    ->first();
                if (!isset($sell_line) || $mainQuantity != $sell_line->main_unit_quantity) {
                    if (!isset($sell_line) || $mainQuantity > $sell_line->main_unit_quantity) {
                        $new_quantity = (!isset($sell_line)) ? $mainQuantity : $mainQuantity - $sell_line->main_unit_quantity;
                        $this->StockService->SubtractFromStock(
                            $item['product_id'],
                            $sellTransaction->branch_id,
                            $new_quantity,
                            $sell_line->warehouse_id
                        );
                    } else {
                        $new_quantity = $sell_line->main_unit_quantity - $mainQuantity;
                        $this->StockService->addToStock(
                            $item['product_id'],
                            $sellTransaction->branch_id,
                            $new_quantity,
                            $sell_line->warehouse_id
                        );
                    }
                }
            }
        }
        //update sell ransaction
        $this->TransactionService->UpdateTransaction($sellTransaction, $data);
        $this->TransactionService->UpdateOrCreateTransactionSellLines($sellTransaction, $sell_lines_array);
        $sell_total = $this->getSellTotal($sellTransaction);

        $sellTransaction->update(['total' => $sell_total, 'final_price' => $sell_total]);

        if ($sellTransaction->PaymentTransaction && $sellTransaction->payment_type == 'cash') {

            $this->PaymentTransactionService->Update($sellTransaction->PaymentTransaction, $sell_total, true);
        } else {
            $this->PaymentTransactionService->ContactSubtract($sellTransaction->Contact, ($old_total - $sell_total));
        }
    }
    public function getLastQtyInUpdate($sell, $product_id)
    {
        return $sell->TransactionSellLines()->where('product_id', $product_id)->first()->main_unit_quantity ?? null;
    }
}
