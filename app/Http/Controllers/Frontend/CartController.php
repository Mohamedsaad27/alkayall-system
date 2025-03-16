<?php

namespace App\Http\Controllers\Frontend;

use App\Models\User;
use App\Models\Brand;
use App\Models\Order;
use App\Models\Branch;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use App\Services\SellService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Darryldecode\Cart\Facades\CartFacade as Cart;

class CartController extends Controller
{
    public $SellService;
    protected $PaymentTransactionService;
    public $TransactionService;
    protected $ActivityLogsService;
    public function __construct(SellService $SellService)
    {
        $this->SellService = $SellService;
    }


    public function index()
    {
        $setting = SiteSetting::get()->first();
        $brands  = Brand::all();

        $categories = Category::with('subcategories')->whereNull('parent_id')->get();
        return view('Frontend.cart.all', compact('categories', 'setting','brands'));
    }


    public function checkOut(Request $request)
    {
        $contact = auth('contact')->user();
        if (!$contact) {
            return redirect()->route('login')->with('error', 'You need to log in first.');
        }
    
        $branch = DB::table('branchs')->where('governorate_id', $contact->governorate_id)->first();
        $items = Cart::session($contact->id)->getContent();
        $setting = SiteSetting::first();
    
        $subtotal = $items->sum(fn($item) => $item->quantity * $item->price);
        $tax = (($setting->tax ?? 14) / 100) * $subtotal;
        $total = $subtotal + $tax;
    
        $products = [];
    
        foreach ($items as $item) {
            $product = Product::find($item->id);
            if (!$product) {
                return redirect()->back()->with('error', 'Product not found.');
            }
    
            $stock = $product->getStockByBranch($branch->id);
            if ($item->quantity < $product->min_sale || $item->quantity > $product->max_sale || $stock < $item->quantity) {
                return redirect()->back()->with('error', 'One or more products do not meet the stock or quantity requirements.');
            }
    
            $products[] = [
                'product_id' => $item->id,
                'quantity' => $item->quantity,
                'unit_price' => $item->price,
                'total' => $item->quantity * $item->price,
            ];
        }
    
        DB::beginTransaction();
        try {
            $data = [
                'branch_id' => $branch->id,
                'contact_id' => $contact->id,
                'payment_type' => "credit",
                'payment_status' => "due",
                'status' => 'final',
                'transaction_from' => 'site',
                'account_id' => $branch->credit_account_id,
                'discount_value' => null,
                'discount_type' => null,
                'amount' => $total,
                'total' => $total,
            ];
    
            $transaction = $this->SellService->CreateSell($data, $products, $request);
            Cart::session($contact->id)->clear();
    
            DB::commit();
            return redirect()->route('profile')->with('success', 'Order placed successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }
    
}
