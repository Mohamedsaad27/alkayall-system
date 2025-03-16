<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SiteSetting;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Darryldecode\Cart\Facades\CartFacade as Cart;

class CartItems extends Component
{
    public $items;
    public $contactId;

    public function mount()
    {
        $this->contactId = Auth::guard('contact')->check() ? auth('contact')->user()->id : null;
        $this->loadCartItems();
    }

    public function loadCartItems()
    {
        $this->items = $this->contactId 
            ? Cart::session($this->contactId)->getContent() 
            : Cart::getContent();
    }

    public function increment($itemId)
    {
        if ($this->contactId) {
            $item = Cart::session($this->contactId)->get($itemId);
            $product = Product::find($itemId);

            if (!$product) {
                $this->addError('quantity', 'Product not found.');
                return;
            }

            $availableStock = $product->getStockByBranch(auth('contact')->user()->getBranch()->id) - $item->quantity;

            if ($availableStock <= 0) {
                $this->addError('quantity', 'The requested quantity exceeds the available stock.');
                return;
            }

            Cart::session($this->contactId)->update($itemId, ['quantity' => 1]);
            $this->loadCartItems();
            $this->dispatch('cartUpdated');
        }
    }

    public function decrement($itemId)
    {
        if ($this->contactId) {
            $item = Cart::session($this->contactId)->get($itemId); 
            $product = Product::find($itemId); 
    
            if (!$product || !$item) {
                $this->addError('quantity', 'Product not found or not in cart.');
                return;
            }
    
            $branch = auth('contact')->user()->getBranch(); 
            $stock = $product->getStockByBranch($branch->id);
            $minSale = $product->min_sale;
            $maxSale = min($product->max_sale, $stock); 
    
            if ($item->quantity - 1 < $minSale) {
                $this->addError('quantity', "Cannot decrease below minimum sale quantity: $minSale.");
                return;
            }
    
            if ($stock < $minSale) {
                $this->addError('quantity', 'Stock is below the minimum sale limit.');
                return;
            }
    
            if ($item->quantity > 1) {
                Cart::session($this->contactId)->update($itemId, ['quantity' => -1]);
            } else {
                $this->remove($itemId);
            }
    
            $this->loadCartItems();
            $this->dispatch('cartUpdated');
        }
    }
    

    public function remove($itemId)
    {
        if ($this->contactId) {
            Cart::session($this->contactId)->remove($itemId);
            $this->loadCartItems();
            $this->dispatch('cartUpdated');
        }
    }

    public function render()
    {
        $setting = SiteSetting::first();
        return view('livewire.cart-items', compact('setting'));
    }
}
