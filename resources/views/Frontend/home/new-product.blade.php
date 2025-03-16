<!-- BEGIN SALE PRODUCT & NEW ARRIVALS -->
<div class="row owl-theme margin-bottom-40" >
    <h2 class="margin-left-20">{{ trans('frontend.new_arrivals') }}</h2>
    
                <div class=" owl-theme owl-carousel" id="">
                    @foreach ($latestProducts as $product)
                        <div class="product-item " >
                            <div class="pi-img-wrapper">
                                <img src="{{$product->getImage() ?? asset('assets/pages/img/products/model2.jpg') }}" class="img-responsive"
                                    alt="Berry Lace Dress" style="height: 150px;width: 100% !important; object-fit: cover;">
                                <div>
                                    <a href="{{$product->getImage() ?? asset('assets/pages/img/products/model2.jpg') }}"
                                        class="btn btn-default fancybox-button">{{ trans('frontend.zoom') }}</a>
                                    <a href="{{ route('show.product',$product->id) }}" class="btn btn-default fancybox-fast-view view-product"
                                        data-id="{{ $product->id }}">{{ trans('frontend.view') }}</a>
                                </div>
                            </div>
                            @if ($branch && $product->getStockByBranch($branch->id) <= 0)
                            <span class="text-danger">{{ trans('frontend.out_of_stock') }}</span>
                            @endif
                            <h3><a href="{{ route('show.product',$product->id) }}">{{ $product->name }}</a> </h3>
                            <div class="pi-price"> <span>{{ trans('frontend.currency') }} </span>{{ $product->getSellPrice() }} / <span class="badge ">
                                    {{ $product->getMainUnitName($product->unit_id) }}</span></div>
                                    @if (Auth('contact')->check())
                                    <a href="#product-pop-up-{{ $product->id }}"
                                        class="btn btn-default add2cart add-to-cart fancybox-cart">
                                        <i class="fa fa-shopping-cart"></i>
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" class="btn btn-default add2cart add-to-cart fancybox-cart">
                                        <i class="fa fa-shopping-cart"></i>
                                    </a>
                                @endif                                      
                                    <div class="sticker sticker-new"></div>
                                </div>
                                
                    @endforeach
                
                </div>
                
                <!-- END SALE PRODUCT -->
</div>
<!-- END SALE PRODUCT & NEW ARRIVALS -->
