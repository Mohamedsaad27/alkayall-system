@extends('layouts.admin')
@section('title', trans('admin.settle-products'))
@section('style')
    <style>
        .settle-product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .settle-product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .branch-quantity-input {
            max-width: 150px;
        }

        .product-image {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }

        .total-settle-card {
            background-color: #f8f9fa;
            border-left: 4px solid #28a745;
        }
    </style>
@endsection
@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                        <i class="fas fa-dolly-flatbed mr-2 text-success"></i>
                        {{ trans('admin.settle-products') }}
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard.home') }}">
                                <i class="fas fa-home mr-1"></i>{{ trans('admin.Home') }}
                            </a>
                        </li>
                        <li class="breadcrumb-item active">{{ trans('admin.settle-products') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <form id="settle-products-form" action="{{ route('dashboard.products.processSettle') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-12 col-md-8">
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-box-open mr-2"></i>
                                    {{ trans('admin.settle-products-note') }}
                                </h3>
                            </div>
                            <div class="card-body">
                                @foreach ($products as $product)
                                    <div class="card settle-product-card mb-3">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-md-2 text-center">
                                                    <img src="{{ $product->getImage() }}" alt="{{ $product->name }}"
                                                        class="product-image img-fluid">
                                                </div>
                                                <div class="col-md-4">
                                                    <h5 class="card-title">{{ $product->name }}</h5>
                                                    <p class="text-muted mb-0">{{ trans('admin.sku') }}:
                                                        {{ $product->sku }}</p>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="table-responsive">
                                                        <table class="table table-sm">
                                                            <thead>
                                                                <tr>
                                                                    <th>{{ trans('admin.branch') }}</th>
                                                                    <th>{{ trans('admin.current_quantity') }}</th>
                                                                    <th>{{ trans('admin.settle_quantity') }}</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($product->branches as $branch)
                                                                    <tr>
                                                                        <td>{{ $branch->name }}</td>
                                                                        <td>{{ $product->getStockByBranch($branch->id) }}</td>
                                                                        <td>
                                                                            <input type="number"
                                                                                name="settle[{{ $product->id }}][{{ $branch->id }}]"
                                                                                class="form-control form-control-sm branch-quantity-input settle-quantity"
                                                                                min="0"
                                                                                max="{{ $branch->pivot->quantity }}"
                                                                                placeholder="{{ trans('admin.enter_quantity') }}">
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card total-settle-card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-calculator mr-2"></i>
                                    {{ trans('admin.settle_summary') }}
                                </h3>
                            </div>
                            <div class="card-body">
                            
                                <div class="form-group">
                                    <label>{{ trans('admin.total_products') }}: <span id="total-products">0</span></label>
                                </div>
                                <div class="form-group">
                                    <label>{{ trans('admin.total_settled_quantity') }}: <span
                                            id="total-settled-quantity">0</span></label>
                                </div>
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    {{ trans('admin.confirm_settle') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
@endsection
@section('script')
    <script>
        $(document).ready(function() {
            function updateSettleSummary() {
                let totalProducts = 0;
                let totalSettledQuantity = 0;

                $('.settle-product-card').each(function() {
                    let productSettled = false;

                    $(this).find('.settle-quantity').each(function() {
                        let quantity = parseFloat($(this).val()) || 0;

                        if (quantity > 0) {
                            productSettled = true;
                            totalSettledQuantity += quantity;
                        }
                    });

                    if (productSettled) {
                        totalProducts++;
                    }
                });

                $('#total-products').text(totalProducts);
                $('#total-settled-quantity').text(totalSettledQuantity);
            }

            // Update summary on input change
            $('.settle-quantity').on('input', function() {
                updateSettleSummary();
            });

            // Form validation before submission
          
        });
    </script>
@endsection
