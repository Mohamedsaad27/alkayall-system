<?php

namespace App\Livewire;

use App\Models\Product;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Darryldecode\Cart\Facades\CartFacade as Cart;

class AddToCart extends Component
{
    public $productId;
    public $isOutOfStock = false;

    public function mount($productId)
    {
        $this->productId = $productId;

        // Check stock availability
        if (Auth::guard('contact')->check()) {
            $branch = auth('contact')->user()->getBranch();
            if ($branch) {
                $product = Product::find($this->productId);
                $this->isOutOfStock = $product && $product->getStockByBranch($branch->id) <= 0;
            }
        } else {
            $this->isOutOfStock = false; // Consider as out of stock if user is not authenticated
        }
    }

    public function add()
    {
        if ($this->isOutOfStock) {
            return; // Do nothing if out of stock
        }
    
        if (Auth::guard('contact')->check()) {
            $product = Product::findOrFail($this->productId);
            $contactID = auth('contact')->user()->id;
    
            // التحقق من الكمية
            if ($this->quantity > $this->max || $this->quantity < $this->min) {
                $this->addError('quantity', 'The quantity must be between ' . $this->min . ' and ' . $this->max . '.');
                return;
            } else {
                // إزالة رسالة الخطأ إذا كانت الكمية صحيحة
                $this->resetErrorBag('quantity');
    
                Cart::session($contactID)->add([
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->getSellPrice(),
                    'quantity' => $this->quantity, // استخدام الكمية المدخلة
                    'attributes' => [
                        'image' => $product->getImage(),
                    ],
                ]);
    
                // إعادة تعيين الكمية إلى الحد الأدنى
                $this->quantity = $this->min;
    
                // إرسال حدث لتحديث السلة
                $this->dispatch('cartUpdated');
            }
        } else {
            return redirect()->route('login');
        }
    }
    

    public function render()
    {
        return view('livewire.add-to-cart');
    }
}
