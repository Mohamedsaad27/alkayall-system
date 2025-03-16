<div class="row margin-bottom-40">
    <!-- BEGIN SIDEBAR -->
    <h4 class="margin-left-20">{{ trans('admin.categories') }}</h4>

    <div class="sidebar col-md-3 col-sm-4">
        <ul class="list-group margin-bottom-25 sidebar-menu">
            @foreach ($categories as $category)
                <li class="list-group-item clearfix">
                    <a href="{{ route('category', $category->id) }}">
                        <i class="fa fa-angle-right"></i> {{ $category->name }}
                    </a>
                    @if ($category->subcategories->count() > 0)
                        <ul class="dropdown-menu">
                            @foreach ($category->subcategories as $subcategory)
                                <li>
                                    <a href="{{ route('category', $subcategory->id) }}">
                                        <i class="fa fa-angle-right"></i> {{ $subcategory->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>



    <!-- END SIDEBAR -->

    <!-- BEGIN CONTENT -->
    <div class="col-md-9 col-sm-12">
        <h2 class="margin-left-10">{{ trans('frontend.products') }}</h2>
        <div class=" owl-theme owl-carousel">
            @foreach ($products as $product)
                <div class="product-item ">
                    <div class="pi-img-wrapper">
                        <img src="{{ $product->getImage() ?? asset('assets/pages/img/products/model2.jpg') }}"
                            class="img-responsive" alt="Berry Lace Dress"
                            style="height: 150px;width: 100% !important; object-fit: cover;">
                        <div>
                            <a href="{{ $product->getImage() ?? asset('assets/pages/img/products/model2.jpg') }}"
                                class="btn btn-default fancybox-button">{{ trans('frontend.zoom') }}</a>
                            <a href="{{ route('show.product', $product->id) }}"
                                class="btn btn-default fancybox-fast-view view-product"
                                data-id="{{ $product->id }}">{{ trans('frontend.view') }}</a>
                        </div>
                    </div>
                    @if ($branch && $product->getStockByBranch($branch->id) <= 0)
                        <span class="text-danger">{{ trans('frontend.out_of_stock') }}</span>
                    @endif
                    <h3><a href="{{ route('show.product', $product->id) }}">{{ $product->name }}</a> </h3>
                    <div class="pi-price"> <span>{{ trans('frontend.currency') }}
                        </span>{{ $product->getSellPrice() }} / <span class="badge ">
                            {{ $product->getMainUnitName($product->unit_id) }}</span></div>
                    <div>
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
                    </div>
                </div>
            @endforeach
        </div>
        {{-- @foreach ($products as $product)
            @include('Frontend.includes.product-pop-up')
        @endforeach --}}
    </div>
    <!-- END CONTENT -->
</div>
