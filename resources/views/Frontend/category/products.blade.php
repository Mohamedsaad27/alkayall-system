@if ($products->count() > 0)

    <div class="row list-view-sorting clearfix">
        <div class="col-md-2 col-sm-2 list-view">
            <a href="javascript:;"><i class="fa fa-th-large"></i></a>
            <a href="javascript:;"><i class="fa fa-th-list"></i></a>
        </div>

    </div>

        <!-- BEGIN PRODUCT LIST -->
        <div class="row product-list">
           
            <div class="sidebar col-md-3 col-sm-4">
                <h4 class="margin-left-20">{{ trans('admin.categories') }}</h4>

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
      
            <div class="col-md-9">
                @foreach ($products as $product)
                    <div class="col-md-4 col-sm-6 col-xs-12">
                        <div>
                            @if ($branch &&  $product->getStockByBranch($branch->id) <= $product->min_sele)
                                <span class="text-danger">{{ trans('frontend.out_of_stock') }}</span>
                            @endif
                            <div class="product-item ">
                                <div class="pi-img-wrapper">
                                    <img src="{{ $product->getImage() ?? asset('assets/pages/img/products/model2.jpg') }}"
                                        class="img-responsive" alt="Berry Lace Dress"
                                        style="height: 150px;width: 100%; object-fit: cover;">
                                    <div>
                                        <a href="{{ $product->getImage() ?? asset('assets/pages/img/products/model2.jpg') }}"
                                            class="btn btn-default fancybox-button">{{ trans('frontend.zoom') }}</a>
                                        <a href="{{ route('show.product', $product->id) }}"
                                            class="btn btn-default fancybox-fast-view view-product"
                                            data-id="{{ $product->id }}">{{ trans('frontend.view') }}</a>
                                    </div>
                                </div>
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
                        </div>
                    </div>
                @endforeach
             
            </div>
        </div>
        
     





@else
     <!-- BEGIN PRODUCT LIST -->
     <div class="row product-list">
        <div class="sidebar col-md-3 col-sm-4 my-5">
            <h4 class="margin-left-20">{{ trans('admin.categories') }}</h4>

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
        <div class="col-md-9 alert-warning p-8 text-center h3">

            لا يوجد منتجات بعد
        </div>
    </div>
    
  
@endif
