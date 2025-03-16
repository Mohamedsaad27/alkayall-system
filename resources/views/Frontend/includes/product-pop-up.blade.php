<!-- Fancybox Modal -->
<div id="product-pop-up-{{ $product->id }}" style="display: none; width: 700px;">
    <div class="product-page product-pop-up">
        <div class="row">
            <div class="col-md-6 col-sm-6 col-xs-3">
              <div class="product-main-image" style="position: relative; overflow: hidden;">
                <img src="{{ $product->getImage() ?? asset('assets/pages/img/products/model2.jpg') }}"
                    alt="{{ $product->name }}" class="img-responsive"
                    data-bigimgsrc="{{ $product->getImage() ?? asset('assets/pages/img/products/model2.jpg') }}">
                <img src="{{ $product->getImage() ?? asset('assets/pages/img/products/model2.jpg') }}"
                    class="zoomImg"
                    style="position: absolute; top: 0px; left: 0px; opacity: 0; width: 600px; height: 800px; border: none; max-width: none;">
            </div>

            </div>
            <div class="col-md-6 col-sm-6 col-xs-9">
                <h2 id="product-name">{{ $product->name }}</h2>
                <div class="price-availability-block clearfix">
                    <div class="price">
                      <strong><span>{{ trans('frontend.currency') }}</span>{{ $product->getSellPrice() }}</strong>
                    </div>
                    <div class="availability">
                        
                    </div>
                </div>

            </div>
            <div class="description">
                <p id="product-description"></p>
            </div>
            <div class="product-page-cart">
                <div class="product-page-cart mt-4">
                    @livewire('show-product', ['productId' => $product->id], key($product->id))
                </div>
                <a href="{{ route('show.product', $product->id) }}" class="btn btn-default">More details</a>
            </div>
        </div>
    </div>
</div>
</div>
