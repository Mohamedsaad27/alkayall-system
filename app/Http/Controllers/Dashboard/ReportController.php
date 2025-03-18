<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\ProductBranchDetails;
use App\Models\ProductPriceHistory;
use App\Models\ProductUnitDetails;
use App\Models\SpoiledLine;
use App\Models\Transaction;
use App\Models\TransactionSellLine;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{
    public function stockReport(Request $request)
    {
        $branches = Branch::active()->get();
        $categories = Category::all();
        $users = User::all();
        $brands = Brand::all();
        $unitPrices = [];
        $unitPrices = ProductUnitDetails::pluck('sale_price')
            ->unique()
            ->toArray();
        if ($request->ajax()) {
            $stocks = ProductBranchDetails::with(['Product.ProductUnitDetails', 'Branch'])->select('product_branch_details.*');

            if ($request->brand_id) {
                $stocks->whereHas('Product', function ($query) use ($request) {
                    $query->where('brand_id', $request->brand_id);
                });
            }
            if ($request->branch_id) {
                $stocks->whereHas('Branch', function ($query) use ($request) {
                    $query->where('id', $request->branch_id);
                });
            }
            if ($request->category_id) {
                $stocks->whereHas('Product', function ($query) use ($request) {
                    $query->where('main_category_id', $request->category_id);
                });
            }
            if ($request->unit_price) {
                $stocks->whereHas('Product', function ($query) use ($request) {
                    $query->whereHas('ProductUnitDetails', function ($query) use ($request) {
                        $query->where('sale_price', $request->unit_price);
                    });
                });
            }
            if ($request->created_by) {
                $stocks->where('created_by', $request->created_by);
            }

            return DataTables::of($stocks)
                ->addColumn('sku', function ($stock) {
                    return $stock->product->sku ?? '';
                })
                ->addColumn('name', function ($stock) {
                    return $stock->product->name;
                })
                ->addColumn('branch', function ($stock) {
                    return $stock->branch->name;
                })
                ->addColumn('unit_price', function ($stock) {
                    return $stock->Product->ProductUnitDetails->first()->sale_price ?? null;
                })
                ->addColumn('qty_available', function ($stock) {
                    return $stock->qty_available;
                })
                ->make(true);
        }

        // return view
        return view('Dashboard.reports.stock', compact('users', 'branches', 'categories', 'brands', 'unitPrices'));
    }

    public function transactionSellReport(Request $request)
    {
        $branches = Branch::active()->get();
        $categories = Category::all();
        $users = User::all();
        $brands = Brand::all();
        $contacts = Contact::where('type', 'customer')->get();
        $unitPrices = [];
        $unitPrices = Product::pluck('unit_price')
            ->unique()
            ->values();
        if ($request->ajax()) {
            $transactions = Transaction::where('type', 'sell')->get();

            if ($request->branch_id) {
                $transactions->whereHas('transaction', function ($query) use ($request) {
                    $query->where('branch_id', $request->branch_id);
                });
            }
            if ($request->contact_id) {
                $transactions->whereHas('transaction', function ($query) use ($request) {
                    $query->where('contact_id', $request->contact_id);
                });
            }

            if ($request->date_from && $request->date_to) {
                $transactions->whereBetween('created_at', [$request->date_from, $request->date_to]);
            }
            if ($request->category_id) {
                $transactions->whereHas('Product', function ($query) use ($request) {
                    $query->where('main_category_id', $request->category_id);
                });
            }
            if ($request->unit_price) {
                $transactions->whereHas('Product', function ($query) use ($request) {
                    $query->where('unit_price', $request->unit_price);
                });
            }

            if ($request->created_by) {
                $transactions->where('created_by', $request->created_by);
            }
            return DataTables::of($transactions)
                ->addColumn('ref_no', function ($transactions) {
                    return $transactions?->ref_no;
                })
                ->addColumn('contact_name', function ($transactions) {
                    return $transactions->contact->name;
                })
                ->addColumn('date', function ($transactions) {
                    return $transactions->created_at->format('Y-m-d');
                })
                ->addColumn('total', function ($transactions) {
                    return $transactions->final_price;
                })
                ->make(true);
        }
        // return view
        return view('Dashboard.reports.transaction_sell', compact('users', 'branches', 'categories', 'brands', 'unitPrices', 'contacts'));
    }

    public function expensesReport(Request $request)
    {
        $branches = Branch::active()->get();
        $categories = ExpenseCategory::all();
        $users = User::all();
        if ($request->ajax()) {
            $expenses = Expense::with('expenseCategory', 'branch', 'createdBy')->select('expenses.*');
            if ($request->branch_id) {
                $expenses->where('branch_id', $request->branch_id);
            }

            if ($request->date_from && $request->date_to) {
                $expenses->whereBetween('created_at', [$request->date_from, $request->date_to]);
            }
            if ($request->created_by) {
                $expenses->where('created_by', $request->created_by);
            }
            if ($request->category_id) {
                $expenses->where('expense_category_id', $request->category_id);
            }
            return DataTables::of($expenses)
                ->addColumn('category', function ($expenses) {
                    return $expenses->expenseCategory?->name;
                })
                ->addColumn('branch', function ($expenses) {
                    return $expenses->branch?->name;
                })
                ->addColumn('amount', function ($expenses) {
                    return $expenses->amount;
                })
                ->addColumn('date', function ($expenses) {
                    return $expenses->created_at->format('Y-m-d');
                })
                ->addColumn('created_by', function ($expenses) {
                    return $expenses->createdBy?->name;
                })
                ->make(true);
        }
        // return view
        return view('Dashboard.reports.expenses', compact('users', 'branches', 'categories'));
    }

    public function popularProductsReport(Request $request)
    {
        $branches = Branch::active()->get();
        $categories = Category::all();

        if ($request->ajax()) {
            $transactions_sell_lines = TransactionSellLine::with('Product', 'Transaction')
                ->selectRaw('product_id, SUM(quantity) as total_quantity, SUM(total) as total_sales')
                ->groupBy('product_id')
                ->orderBy('total_quantity', 'desc');

            if ($request->branch_id) {
                $transactions_sell_lines->whereHas('Transaction', function ($query) use ($request) {
                    $query->where('branch_id', $request->branch_id);
                });
            }

            if ($request->category_id) {
                $transactions_sell_lines->whereHas('Product', function ($query) use ($request) {
                    $query->where('main_category_id', $request->category_id);
                });
            }

            return DataTables::of($transactions_sell_lines)
                ->addColumn('sku', function ($transactions_sell_lines) {
                    return $transactions_sell_lines->Product->sku ?? '';
                })
                ->addColumn('name', function ($transactions_sell_lines) {
                    return $transactions_sell_lines->Product->name ?? '';
                })
                ->addColumn('quantity', function ($transactions_sell_lines) {
                    return $transactions_sell_lines->total_quantity ?? '';
                })
                ->addColumn('total', function ($transactions_sell_lines) {
                    return $transactions_sell_lines->total_sales ?? '';
                })
                ->make(true);
        }
        // return view
        return view('Dashboard.reports.popular_products', compact('branches', 'categories'));
    }

    public function spoiledProductsReport(Request $request)
    {
        $branches = Branch::active()->get();
        $categories = Category::all();
        $users = User::all();

        if ($request->ajax()) {
            $spoiled_lines = SpoiledLine::with('product', 'transaction')->select('spoiled_lines.*');

            if ($request->branch_id) {
                $spoiled_lines->whereHas('Transaction', function ($query) use ($request) {
                    $query->where('branch_id', $request->branch_id);
                });
            }

            if ($request->category_id) {
                $spoiled_lines->whereHas('Product', function ($query) use ($request) {
                    $query->where('category_id', $request->category_id);
                });
            }
            if ($request->category_id) {
                $spoiled_lines->whereHas('Transaction', function ($query) use ($request) {
                    $query->where('created_by', $request->created_by);
                });
            }
            if ($request->date_from && $request->date_to) {
                $spoiled_lines->whereBetween('created_at', [$request->date_from, $request->date_to]);
            }

            return DataTables::of($spoiled_lines)
                ->addColumn('sku', function ($spoiled_lines) {
                    return $spoiled_lines->product->sku ?? '';
                })
                ->addColumn('name', function ($spoiled_lines) {
                    return $spoiled_lines->product->name ?? '';
                })
                ->addColumn('branch', function ($spoiled_lines) {
                    return $spoiled_lines->transaction->Branch?->name ?? '';
                })
                ->addColumn('created_by', function ($spoiled_lines) {
                    return $spoiled_lines->transaction->CreatedBy?->name ?? '';
                })
                ->addColumn('ref_no', function ($spoiled_lines) {
                    return $spoiled_lines->transaction->ref_no ?? '';
                })
                ->addColumn('total', function ($spoiled_lines) {
                    return $spoiled_lines->transaction->final_price ?? '';
                })
                ->addColumn('date', function ($spoiled_lines) {
                    return $spoiled_lines->transaction->created_at->format('Y-m-d');
                })
                ->addColumn('reason', function ($spoiled_lines) {
                    return $spoiled_lines->reason ?? '';
                })
                ->make(true);
        }
        // return view
        return view('Dashboard.reports.spoiled_products', compact('branches', 'categories', 'users'));
    }

    public function deptReport(Request $request)
    {
        if ($request->ajax()) {
            $data = Contact::query()->orderBy('id', 'desc');

            if ($request->type) {
                $data->where('type', $request->type)->where('balance', '>', 0);
            }

            return DataTables::of($data)
                ->addColumn('code', function ($row) {
                    return $row->id;
                })
                ->addColumn('name', function ($row) {
                    return $row->name;
                })
                ->addColumn('type', function ($row) {
                    return $row->type;
                })
                ->addColumn('phone', function ($row) {
                    return $row->phone;
                })
                ->addColumn('balance', function ($row) {
                    if ($row->type === 'supplier') {
                        return $row->balance * -1;
                    }
                    return $row->balance;
                })
                ->make(true);
        }
        return view('Dashboard.reports.dept_report');
    }

    public function changeInPriceReport(Request $request)
    {
        if ($request->ajax()) {
            $productPriceHistories = ProductPriceHistory::with('product', 'changedBy')->orderBy('created_at', 'desc');

            if ($request->product_id) {
                $productPriceHistories->where('product_id', $request->product_id);
            }
            if ($request->created_by) {
                $productPriceHistories->where('changed_by', $request->created_by);
            }
            if ($request->date_from && $request->date_to) {
                $productPriceHistories->whereBetween('created_at', [$request->date_from, $request->date_to]);
            }

            return DataTables::of($productPriceHistories)
                ->addColumn('product_name', function ($productPriceHistory) {
                    return $productPriceHistory->product?->name;
                })
                ->addColumn('old_unit_price', function ($productPriceHistory) {
                    return $productPriceHistory->old_unit_price;
                })
                ->addColumn('new_unit_price', function ($productPriceHistory) {
                    return $productPriceHistory->new_unit_price;
                })
                ->addColumn('unit', function ($productPriceHistory) {
                    return $productPriceHistory->unit?->actual_name;
                })
                ->addColumn('changed_by', function ($productPriceHistory) {
                    return $productPriceHistory->changedBy?->name;
                })
                ->addColumn('date', function ($productPriceHistory) {
                    return Carbon::parse($productPriceHistory->created_at)->format('Y-m-d');
                })
                ->make(true);
        }
        $products = Product::all();
        $users = User::all();
        return view('Dashboard.reports.change_in_price_report', compact('products', 'users'));
    }

    public function profitAndLossReport(Request $request)
    {
        $date_from = $request->date_from ?? Carbon::now()->format('Y-m-d');
        $date_to = $request->date_to ?? Carbon::now()->format('Y-m-d');
        $date_from = $date_from . ' 00:00:00';
        $date_to = $date_to . ' 23:59:59';
        $branches = Branch::active()->get();
        // Total Sales
        $total_sales_query = Transaction::query();
        if (isset($request->branch_id) && $request->branch_id) {
            $total_sales_query->where('branch_id', $request->branch_id);
        }
        if ($date_from && $date_to) {
            $total_sales_query->whereBetween('transaction_date', [$date_from, $date_to]);
        }
        $total_sales = $total_sales_query->where('type', 'sell')->sum('final_price');

        // Total Sales Returns
        $total_sales_returns_query = Transaction::query();
        if (isset($request->branch_id) && $request->branch_id) {
            $total_sales_returns_query->where('branch_id', $request->branch_id);
        }
        if ($date_from && $date_to) {
            $total_sales_returns_query->whereBetween('transaction_date', [$date_from, $date_to]);
        }
        $total_sales_returns = $total_sales_returns_query->where('type', 'sell_return')->sum('final_price');

        // total Purchase
        $totla_purchase_query = Transaction::query();

        if (isset($request->branch_id) && $request->branch_id) {
            $totla_purchase_query->where('branch_id', $request->branch_id);
        }
        if ($date_from && $date_to) {
            $totla_purchase_query->whereBetween('transaction_date', [$date_from, $date_to]);
        }
        $totla_purchase = $totla_purchase_query->where('type', 'purchase')->sum('final_price');

        // total Purchase Returns
        $totla_purchase_returns_query = Transaction::query();
        if (isset($request->branch_id) && $request->branch_id) {
            $totla_purchase_returns_query->where('branch_id', $request->branch_id);
        }
        if ($date_from && $date_to) {
            $totla_purchase_returns_query->whereBetween('transaction_date', [$date_from, $date_to]);
        }
        $totla_purchase_returns = $totla_purchase_returns_query->where('type', 'purchase_return')->sum('final_price');

        // total Spoiled Stock
        $total_spoiled_stock_query = SpoiledLine::query();

        if (isset($request->branch_id) && $request->branch_id) {
            $total_spoiled_stock_query->whereHas('transaction', function ($query) use ($request) {
                $query->where('branch_id', $request->branch_id);
            });
        }

        // Filter by date range if provided
        if ($date_from && $date_to) {
            $total_spoiled_stock_query->whereBetween('created_at', [$date_from, $date_to]);
        }

        // Initialize totals
        $total_spoiled_stock = 0;
        $total_price_of_spoiled_stock = 0;

        // Calculate total quantity and price of spoiled stock
        $total_spoiled_stock_query->each(function ($spoiledLine) use (&$total_spoiled_stock, &$total_price_of_spoiled_stock) {
            $total_spoiled_stock += $spoiledLine->quantity;

            // Fetch the sale price of the product and calculate the total price
            if ($spoiledLine->product) {
                $total_price_of_spoiled_stock += $spoiledLine->product->getSalePriceByUnit($spoiledLine->product->unit_id) * $spoiledLine->quantity;
            }
        });
        // Total Taxes in Sales
        $total_taxes_in_sales = Transaction::query();
        if (isset($request->branch_id) && $request->branch_id) {
            $total_taxes_in_sales->where('branch_id', $request->branch_id);
        }
        $total_taxes_in_sales = $total_taxes_in_sales->where('type', 'sell')->sum('tax_amount');
        // Total Expenses
        $total_expenses_query = Expense::query();
        if (isset($request->branch_id) && $request->branch_id) {
            $total_expenses_query->where('branch_id', $request->branch_id);
        }
        if ($date_from && $date_to) {
            $total_expenses_query->whereBetween('created_at', [$date_from, $date_to]);
        }
        $total_expenses = $total_expenses_query->sum('amount');
        // total Discounts in Sales
        $total_discount_in_sales = Transaction::query();
        if (isset($request->branch_id) && $request->branch_id) {
            $total_discount_in_sales->where('branch_id', $request->branch_id);
        }
        if ($date_from && $date_to) {
            $total_discount_in_sales->whereBetween('transaction_date', [$date_from, $date_to]);
        }
        $total_discount_in_sales = $total_discount_in_sales->where('type', 'sell')->sum('discount_value');

        // total Discounts in Purchases
        $total_discount_in_purchases = Transaction::query();
        if (isset($request->branch_id) && $request->branch_id) {
            $total_discount_in_purchases->where('branch_id', $request->branch_id);
        }
        $total_discount_in_purchases = $total_discount_in_purchases->where('type', 'purchase')->sum('discount_value');
        $net_profit_from_sales_and_purchases = Transaction::query()
            ->where('type', 'sell')
            ->with([
                'TransactionSellLines.Product.ProductUnitDetails'
            ])
            ->get()
            ->sum(function ($transaction) {
                return $transaction->TransactionSellLines->sum(function ($sellLine) {
                    $productUnitDetail = $sellLine
                        ->Product
                        ->ProductUnitDetails
                        ->where('unit_id', $sellLine->unit_id)
                        ->first();

                    if ($productUnitDetail) {
                        $salePrice = $sellLine->unit_price;
                        $purchasePrice = $productUnitDetail->purchase_price;
                        $quantity = $sellLine->quantity;

                        return ($salePrice - $purchasePrice) * $quantity;
                    }

                    return 0;
                });
            });

        $net_profit = ($net_profit_from_sales_and_purchases + $total_discount_in_purchases) - ($total_expenses + $total_discount_in_sales + $total_price_of_spoiled_stock + $total_taxes_in_sales);
        $total_profit = $total_sales - $totla_purchase;
        // total Opening Balance for Customers
        $total_opening_balance_for_customers = Contact::where('type', 'customer')->where('balance', '>', 0)->sum('balance');

        // total Opening Balance for Suppliers
        $total_opening_balance_for_suppliers = Contact::where('type', 'supplier')->where('balance', '>', 0)->sum('balance');

        $branches = Branch::active()->get();
        $customers = Contact::where('type', 'customer')->get();
        $categories = Category::all();
        $brands = Brand::all();
        $products = Product::all();
        $total_profit_by_branch = [];
        foreach ($branches as $branch) {
            $total_profit_by_branch[$branch->id] = $this->getTotalProfitByBranch($branch->id);
        }
        $total_profit_by_customer = [];
        foreach ($customers as $customer) {
            $total_profit_by_customer[$customer->id] = $this->getTotalProfitByCustomer($customer->id);
        }
        $total_profit_by_category = [];
        foreach ($categories as $category) {
            $total_profit_by_category[$category->id] = $this->getTotalProfitByCategory($category->id);
        }
        $total_profit_by_brand = [];
        foreach ($brands as $brand) {
            $total_profit_by_brand[$brand->id] = $this->getTotalProfitByBrand($brand->id);
        }
        $total_profit_by_product = [];
        foreach ($products as $product) {
            $total_profit_by_product[$product->id] = $this->getTotalProfitByProduct($product->id);
        }
        $refs_no = Transaction::select('ref_no')->distinct()->get();
        $total_profit_by_ref_no = [];
        foreach ($refs_no as $ref_no) {
            $total_profit_by_ref_no[$ref_no->ref_no] = $this->getTotalProfitByRefNo($ref_no->ref_no);
        }
        return view('Dashboard.reports.profit_and_loss_report', compact(
            'branches',
            'date_from',
            'date_to',
            'total_sales',
            'total_sales_returns',
            'totla_purchase',
            'totla_purchase_returns',
            'total_price_of_spoiled_stock',
            'total_expenses',
            'total_opening_balance_for_customers',
            'total_opening_balance_for_suppliers',
            'total_discount_in_sales',
            'total_discount_in_purchases',
            'total_profit',
            'branches',
            'customers',
            'categories',
            'brands',
            'total_profit_by_branch',
            'total_profit_by_customer',
            'total_profit_by_category',
            'total_profit_by_brand',
            'total_profit_by_product',
            'total_profit_by_ref_no',
            'refs_no',
            'products',
            'net_profit'
        ));
    }

    public function getTotalProfitByBranch($branch_id)
    {
        $total_sales_by_branch = Transaction::where('branch_id', $branch_id)
            ->where('type', 'sell')
            ->with([
                'TransactionSellLines.Product.ProductUnitDetails'
            ])
            ->get()
            ->sum(function ($transaction) {
                return $transaction->TransactionSellLines->sum(function ($sellLine) {
                    $productUnitDetail = $sellLine
                        ->Product
                        ->ProductUnitDetails
                        ->where('unit_id', $sellLine->unit_id)
                        ->first();
                    if ($productUnitDetail) {
                        $salePrice = $sellLine->unit_price;
                        $purchasePrice = $productUnitDetail->purchase_price;
                        $quantity = $sellLine->quantity;
                        return ($salePrice - $purchasePrice) * $quantity;
                    }
                    return 0;
                });
            });

        $total_profit_by_branch = $total_sales_by_branch;
        return $total_profit_by_branch;
    }

    public function getTotalProfitByCustomer($customer_id)
    {
        $total_sales_by_customer = Transaction::where('contact_id', $customer_id)
            ->where('type', 'sell')
            ->with(['TransactionSellLines.Product.ProductUnitDetails'])
            ->get()
            ->sum(function ($transaction) {
                return $transaction->TransactionSellLines->sum(function ($sellLine) {
                    $productUnitDetail = $sellLine
                        ->Product
                        ->ProductUnitDetails
                        ->where('unit_id', $sellLine->unit_id)
                        ->first();
                    if ($productUnitDetail) {
                        $salePrice = $sellLine->unit_price;
                        $purchasePrice = $productUnitDetail->purchase_price;
                        $quantity = $sellLine->quantity;
                        return ($salePrice - $purchasePrice) * $quantity;
                    }
                    return 0;
                });
            });

        $total_profit_by_customer = $total_sales_by_customer;
        return $total_profit_by_customer;
    }

    public function getTotalProfitByCategory($category_id)
    {
        $total_sales_by_category = Transaction::where('type', 'sell')
            ->with([
                'TransactionSellLines.Product.ProductUnitDetails'
            ])
            ->whereHas('TransactionSellLines.Product', function ($query) use ($category_id) {
                $query->where('main_category_id', $category_id);
            })
            ->get()
            ->sum(function ($transaction) {
                return $transaction->TransactionSellLines->sum(function ($sellLine) {
                    $productUnitDetail = $sellLine
                        ->Product
                        ->ProductUnitDetails
                        ->where('unit_id', $sellLine->unit_id)
                        ->first();
                    if ($productUnitDetail) {
                        $salePrice = $sellLine->unit_price;
                        $purchasePrice = $productUnitDetail->purchase_price;
                        $quantity = $sellLine->quantity;
                        return ($salePrice - $purchasePrice) * $quantity;
                    }
                    return 0;
                });
            });

        $total_profit_by_category = $total_sales_by_category;
        return $total_profit_by_category;
    }

    public function getTotalProfitByBrand($brand_id)
    {
        // Calculate total sales profit
        $total_sales_profit = Transaction::where('type', 'sell')
            ->with([
                'TransactionSellLines.Product.ProductUnitDetails'
            ])
            ->whereHas('TransactionSellLines.Product', function ($query) use ($brand_id) {
                $query->whereHas('Brand', function ($query) use ($brand_id) {
                    $query->where('id', $brand_id);
                });
            })
            ->get()
            ->sum(function ($transaction) {
                return $transaction->TransactionSellLines->sum(function ($sellLine) {
                    $productUnitDetail = $sellLine
                        ->Product
                        ->ProductUnitDetails
                        ->where('unit_id', $sellLine->unit_id)
                        ->first();
                    if ($productUnitDetail) {
                        $salePrice = $sellLine->unit_price;
                        $purchasePrice = $productUnitDetail->purchase_price;
                        $quantity = $sellLine->quantity;
                        return ($salePrice - $purchasePrice) * $quantity;
                    }
                    return 0;
                });
            });

        // Calculate total returns profit (as a reduction of profit)
        $total_returns_profit = Transaction::where('type', 'sell_return')
            ->with([
                'TransactionSellLines.Product.ProductUnitDetails'
            ])
            ->whereHas('TransactionSellLines.Product', function ($query) use ($brand_id) {
                $query->whereHas('Brand', function ($query) use ($brand_id) {
                    $query->where('id', $brand_id);
                });
            })
            ->get()
            ->sum(function ($transaction) {
                return $transaction->TransactionSellLines->sum(function ($returnLine) {
                    $productUnitDetail = $returnLine
                        ->Product
                        ->ProductUnitDetails
                        ->where('unit_id', $returnLine->unit_id)
                        ->first();
                    if ($productUnitDetail) {
                        $salePrice = $returnLine->unit_price;
                        $purchasePrice = $productUnitDetail->purchase_price;
                        $quantity = $returnLine->quantity;
                        // Subtract the profit (or add the loss) from returns
                        return -($salePrice - $purchasePrice) * $quantity;
                    }
                    return 0;
                });
            });

        $total_profit_by_brand = $total_sales_profit + $total_returns_profit;

        return $total_profit_by_brand;
    }

    public function getTotalProfitByProduct($product_id)
    {
        $total_sales_by_product = Transaction::where('type', 'sell')
            ->with([
                'TransactionSellLines.Product.ProductUnitDetails'
            ])
            ->whereHas('TransactionSellLines', function ($query) use ($product_id) {
                $query->where('product_id', $product_id);
            })
            ->get()
            ->sum(function ($transaction) {
                return $transaction->TransactionSellLines->sum(function ($sellLine) {
                    $productUnitDetail = $sellLine
                        ->Product
                        ->ProductUnitDetails
                        ->where('unit_id', $sellLine->unit_id)
                        ->first();
                    if ($productUnitDetail) {
                        $salePrice = $productUnitDetail->sale_price;
                        $purchasePrice = $productUnitDetail->purchase_price;
                        $quantity = $sellLine->quantity;
                        return ($salePrice - $purchasePrice) * $quantity;
                    }
                    return 0;
                });
            });

        $total_profit_by_product = $total_sales_by_product;
        return $total_profit_by_product;
    }

    public function getTotalProfitByRefNo($ref_no)
    {
        $total_sales_by_ref_no = Transaction::where('type', 'sell')
            ->where('ref_no', $ref_no)
            ->with([
                'TransactionSellLines.Product.ProductUnitDetails'
            ])
            ->get()
            ->sum(function ($transaction) {
                return $transaction->TransactionSellLines->sum(function ($sellLine) {
                    $productUnitDetail = $sellLine
                        ->Product
                        ->ProductUnitDetails
                        ->where('unit_id', $sellLine->unit_id)
                        ->first();
                    if ($productUnitDetail) {
                        $salePrice = $sellLine->unit_price;
                        $purchasePrice = $productUnitDetail->purchase_price;
                        $quantity = $sellLine->quantity;
                        return ($salePrice - $purchasePrice) * $quantity;
                    }
                    return 0;
                });
            });
        $total_profit_by_ref_no = $total_sales_by_ref_no;
        return $total_profit_by_ref_no;
    }
}
