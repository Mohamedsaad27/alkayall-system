<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Component;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Support\Facades\Auth;

class ShowProduct extends Component
{
    public $productId;
    public $quantity = 1;
    public $max;
    public $min;
    public $stock = 0;
    public $isOutOfStock = false;
    public $contactId;

    public function mount($productId)
    {
        $this->productId = $productId;

        // إذا كان المستخدم غير مسجل الدخول، لا نقوم بإعادة التوجيه
        if (Auth::guard('contact')->check()) {
            $this->contactId = auth('contact')->user()->id;
            $branch = auth('contact')->user()->getBranch();

            if (!$branch) {
                $this->isOutOfStock = true;
                return;
            }

            $product = Product::find($this->productId);

            if ($product) {
                $this->min = $product->min_sale;
                $this->max = $product->max_sale;

                // حساب المخزون
                $this->stock = $this->calculateStock($product, $branch->id);

                // التحقق إذا كان المخزون أقل من الحد الأدنى
                if ($this->stock < $this->min) {
                    $this->isOutOfStock = true;
                } else {
                    $this->quantity = $this->min;
                    $this->max = min($this->max, $this->stock); // الحد الأقصى بناءً على المخزون
                    $this->isOutOfStock = false;
                }
            } else {
                $this->isOutOfStock = true;
            }
        } else {
            // المستخدم غير مسجل ولكن لا نعيد التوجيه
            $this->isOutOfStock = false; // نعرض المنتج بشكل عادي
        }
    }


    private function calculateStock($product, $branchId)
    {
        $cartQuantity = Cart::session($this->contactId)->get($this->productId)?->quantity ?? 0;
        $availableStock = $product->getStockByBranch($branchId) - $cartQuantity;

        return max(0, $availableStock);
    }

    public function add()
    {
        // إذا كان المستخدم غير مسجل الدخول
        if (!Auth::guard('contact')->check()) {
            // تخزين الخصائص الحالية في الـ session
            session()->put('redirect_after_login', [
                'productId' => $this->productId,
                'quantity' => $this->quantity,
            ]);
    
            // إعادة التوجيه إلى صفحة تسجيل الدخول
            return redirect()->route('login');
        }
    
        // التحقق من حالة المنتج
        if ($this->isOutOfStock) {
            $this->addError('quantity', 'This product is out of stock or does not meet the minimum stock requirement.');
            return;
        }
    
        if ($this->quantity < $this->min || $this->quantity > $this->max) {
            $this->addError('quantity', 'The quantity must be between ' . $this->min . ' and ' . $this->max . '.');
            return;
        }
    
        $product = Product::findOrFail($this->productId);
    
        Cart::session($this->contactId)->add([
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->getSellPrice(),
            'quantity' => $this->quantity,
            'attributes' => ['image' => $product->getImage()],
        ]);
    
        $branch = auth('contact')->user()->getBranch();
        $this->stock = $this->calculateStock($product, $branch->id);
        $this->max = min($this->max, $this->stock);
        $this->quantity = $this->min;
        $this->resetErrorBag('quantity');
        $this->dispatch('cartUpdated');
    }
    
    public function render()
    {
        return view('livewire.show-product', [
            'isOutOfStock' => $this->isOutOfStock,
            'quantity' => $this->quantity,
            'min' => $this->min,
            'max' => $this->max,
            'stock' => $this->stock,
        ]);
    }
}
