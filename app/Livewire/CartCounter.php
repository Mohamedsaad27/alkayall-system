<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Darryldecode\Cart\Facades\CartFacade as Cart;

class CartCounter extends Component
{
    public $totalQuantity = 0;
    public $total = 0.0;
    public $items = [];

    protected $listeners = ['cartUpdated' => 'updateCart'];

    public function mount()
    {
        $this->updateCart();
    }

    /**
     * Update cart details (items, quantity, and total).
     */
    public function updateCart()
    {
        $contactId = Auth::guard('contact')->check() ? auth('contact')->user()->id : null;

        if ($contactId) {
            $cart = Cart::session($contactId);
            $this->items = $cart->getContent()->toArray();
            $this->totalQuantity = $cart->getTotalQuantity();
            $this->total = $cart->getTotal();
        } else {
            $cart = Cart::getContent();
        }


    }

    /**
     * Remove an item from the cart.
     */
    public function remove($itemId)
    {
        $contactId = Auth::guard('contact')->check() ? auth('contact')->user()->id : null;

        if ($contactId) {
            Cart::session($contactId)->remove($itemId);
        } else {
            Cart::remove($itemId);
        }

        $this->updateCart();
        $this->dispatch('cartUpdated');
    }

    public function render()
    {
        return view('livewire.cart-counter', [
            'totalQuantity' => $this->totalQuantity,
            'total' => $this->total,
            'items' => $this->items,
        ]);
    }
}
