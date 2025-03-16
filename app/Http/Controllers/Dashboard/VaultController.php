<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\User;
use App\Models\Branch;
use App\Models\Contact;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;

class VaultController extends Controller
{
    public function index(Request $request)
    {
        $setting  = Setting::first();

        if(!$setting->display_vault) 
        {
            abort(404);
        }
      

        if ($request->ajax()) {
           
            $data = Transaction::with(['PaymentsTransaction', 'ReturnTransactions.PaymentsTransaction',
            'TransactionSellLines' => function ($query) {
                $query->where('return_quantity', '>', 0);
            }])
            ->where('type', 'sell')
            ->where('status', '!=', 'draft')
            ->where('payment_status','vault')
            ->orderBy('id', 'desc');
            if ($request->branch_id) {
                $data->where('branch_id', $request->branch_id);
            }
            if ($request->contact_id) {
                $data->where('contact_id', $request->contact_id);
            }
            if ($request->date_from && $request->date_to) {
                $data->whereBetween('created_at', [$request->date_from, $request->date_to]);
            }
            if ($request->created_by) {
                $data->where('created_by', $request->created_by);
            }
            if ($request->payment_status) {
                $data->where('payment_status', $request->payment_status);
            }
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn =  '<div class="btn-group"><button type="button" class="btn btn-success">' . trans('admin.Actions') . '</button><button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown"></button><div class="dropdown-menu" role="menu">';
                    if (auth('user')->user()->has_permission('read-vault'))
                        $btn .= '<a class="dropdown-item fire-popup" data-url="' . route('dashboard.sells.show', $row->id) . '"
                                href="#" data-toggle="modal" data-target="#modal-default-big">' . trans("admin.Show") . '</a>';
                    if (auth('user')->user()->has_permission('update-vault'))
                        $btn .= '<a class="dropdown-item"  href="' . route('dashboard.sells.edit', $row->id) . '">' . trans('admin.Edit') . '</a>';
                    if (auth('user')->user()->has_permission('pay-vault') && $row->payment_status != "final")
                        $btn .= '<a class="dropdown-item fire-popup"  data-toggle="modal" data-target="#modal-default" href="#" data-url="' . route('dashboard.sells.payTransaction', $row->id) . '">' . trans('admin.Pay') . '</a>';

                   
                    if (auth('user')->user()->has_permission('delete-vault') && $row->ReturnTransactions()->count() == 0)
                        $btn .= '<a class="dropdown-item delete-popup" href="#" data-toggle="modal" data-target="#modal-default" data-url="' . route("dashboard.sells.delete", $row->id) . '">' . trans('admin.Delete') . '</a>';


                    $btn .= '</div></div>';
                    return $btn;
                })
                ->addColumn('contact', function ($row) {
                    return $row->Contact?->name;
                })
                ->addColumn('phone', function ($row) {
                    return $row->Contact?->phone;
                })
                ->addColumn('government', function ($row) {
                    return $row->Contact?->government;
                })
                ->addColumn('city', function ($row) {
                    return $row->Contact?->city;
                })
                ->addColumn('total', function ($row) {
                    return number_format($row->final_price, 1);
                })
                ->addColumn('delivery_status', function ($row) {
                    return $row->delivery_status;
                })
                ->addColumn('payment_status', function ($row) {
                    return $row->payment_status;
                })
                ->addColumn('paid_from_transaction', function ($row) {
                    return $row->PaymentsTransaction->sum('amount');
                })
                ->addColumn('paid_from_transaction', function ($row) {
                    $paidFromTRansaction    = $row->PaymentsTransaction->sum('amount');
                    $paymentsForReturnTransactions = $row->ReturnTransactions->flatMap(function ($returnTransaction) {
                        return $returnTransaction->PaymentsTransaction;
                    });
                    $SumPaymentsForReturnTransactions = $paymentsForReturnTransactions->sum('amount');

                    return  $paidFromTRansaction -   $SumPaymentsForReturnTransactions;
                })
                ->addColumn('remaining_amount', function ($row) {
                    $paid_from_transaction = $row->PaymentsTransaction->sum('amount');
                    $paymentsForReturnTransactions = $row->ReturnTransactions->flatMap(function ($returnTransaction) {
                        return $returnTransaction->PaymentsTransaction;
                    });
                    $SumPaymentsForReturnTransactions = $paymentsForReturnTransactions->sum('amount');

                    return $row->final_price - ($paid_from_transaction - $SumPaymentsForReturnTransactions);
                })
                ->addColumn('created_by', function ($row) {
                    return $row->CreatedBy?->name;
                })
                ->addColumn('branch_id', function ($row) {
                    return $row->Branch?->name;
                })->addColumn('route', function ($row) {
                    return route('dashboard.sells.show', $row->id);
                })
                ->addColumn('ref_no', function ($row) {
                    $hasReturnQuantity = $row->TransactionSellLines->contains('return_quantity', '>', 0);
                    
                    if ($hasReturnQuantity) {
                        return $row->ref_no . ' <i class="fas fa-exchange-alt text-danger" title="Has Return"></i>';
                    }
                    
                    return $row->ref_no;
                })
                ->rawColumns(['ref_no', 'action']) 
                ->make(true);
        }
        $branches = Branch::active()->get();
        $customers = Contact::where('type', 'customer')->get();
        $users = User::all();
        return view('Dashboard.vault.index', compact('branches', 'customers', 'users'));
    }
}
