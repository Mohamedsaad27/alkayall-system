<!DOCTYPE html>
<html lang="en">
    <head>
        @include('Frontend.includes.meta')
        @include('Frontend.includes.style')
        @yield('style')
    </head>
    <body class="ecommerce">
        @include('Frontend.includes.header')
        @yield('header')
        <div class="main">
            <div class="container">
                @yield('content')
                <div class="brand-section">
                    <div class="content-wrapper">
                        <div class="container">
                
                            <h2 >{{ trans('admin.brands') }}</h2>
                        </div>
                        <div class="owl-brand">
                
                                @foreach ($brands as $brand)
                                    <div class="brand-box" >
                                        <a href="{{ route('brand',$brand->id) }}">
                                            <img src="{{ $brand->getFirstMediaUrl('brands') ?: asset('assets/corporate/img/logos/logo-shop-red.png') }}" alt="Brand Image" style="height:100px;width: 165px;">
                                        </a>
                                    </div>
                                @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('Frontend.includes.footer')
        @yield('script')
        @include('Frontend.includes.script')
    </body>
</html>