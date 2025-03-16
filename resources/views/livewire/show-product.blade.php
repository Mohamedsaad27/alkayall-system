<div>
    @if ($isOutOfStock)
        <span class="text-danger"  style="color: crimson">
            @if ($stock < $min)
                The product is unavailable as the stock is below the minimum required quantity.
            @else
                This product is out of stock.
            @endif
        </span>
    @else
        <div class="quantity">
            <input 
                type="number" 
                class="form-control" 
                wire:model="quantity" 
                min="{{ $min }}" 
                max="{{ $max }}" 
            >
        </div>
        <a href="javascript:;" class="btn btn-default add2cart" wire:click.prevent="add">
            {{ trans('frontend.add_to_cart') }}
        </a>
        @error('quantity')
            <span class="text-red-500" style="color: crimson">{{ $message }}</span>
        @enderror
    @endif
</div>
