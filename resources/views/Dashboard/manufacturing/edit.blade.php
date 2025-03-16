@extends('layouts.admin')
@section('style')
    <style>
        .result-list {
            list-style-type: none;
            padding: 0;
            margin: 0;
            border: 1px solid #ddd;
            max-height: 200px;
            overflow-y: auto;
            width: 100%;
        }

        .result-list li {
            padding: 8px;
            cursor: pointer;
        }

        .result-list li:hover {
            background-color: #ddd;
        }

        .list-group-item.disabled {
            pointer-events: none;
            opacity: 0.5;
            background-color: #f8f9fa;
        }

        .modal-dialog {
            max-width: 1000px;
        }

        .input-group .form-control {
            border-radius: 0 4px 4px 0 !important;
        }

        .input-group .form-select {
            border-radius: 4px 0 0 4px !important;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .input-group {
            direction: ltr;
        }

        .input-group input {
            text-align: right;
        }
    </style>
@endsection

@section('title', trans('admin.manufacturing_recipes'))
@section('content')
    <form method="post" action="{{ route('dashboard.manufacturing.update', $recipe->id) }}">
        @csrf
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-sm-6">
                        <h1 class="m-0">{{ trans('admin.update_recipe') }}</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item">
                                <a href="{{ route('dashboard.home') }}">{{ trans('admin.Home') }}</a> /
                                <a
                                    href="{{ route('dashboard.manufacturing.index') }}">{{ trans('admin.manufacturing_recipes') }}</a>
                                /
                                {{ trans('admin.Edit') }}
                            </li>
                        </ol>
                    </div>
                </div>

                <div class="row my-2">
                    <div class="col-lg-12">
                        <div class="row">
                            <div class="col-lg-12">
                                @include('components.form.select', [
                                    'collection' => $productsCollection,
                                    'index' => 'id',
                                    'select' => $recipe->final_product_id,
                                    'name' => 'final_product_id',
                                    'label' => trans('admin.final_product'),
                                    'class' => 'form-control select2 final_product_id product-select',
                                    'attribute' => 'required',
                                    'id' => 'final_product_id',
                                ])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <div class="card card-primary">
                    <div class="card-header"></div>
                    <div class="row px-5">
                        <div class="col-lg-12 py-2">
                            <input type="text" id="search" class="form-control" placeholder="ابحث عن المنتج ...."
                                autocomplete="off" autofocus>
                            <ul id="result-list" class="result-list list-group"></ul>
                        </div>

                        <div class="col-lg-12 my-3">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ trans('admin.name') }}</th>
                                        <th>{{ trans('admin.wastage_rate') }}</th>
                                        <th>{{ trans('admin.quantity') }}</th>
                                        <th style="min-width: 120px;">{{ trans('admin.unit') }}</th>
                                        <th>{{ trans('admin.unit_price') }}</th>
                                        <th>{{ trans('admin.total_price') }}</th>
                                        <th>{{ trans('admin.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="sell_table">
                                    @foreach ($recipe->ingredients as $index => $ingredient)
                                        <tr data-product-id="{{ $ingredient->raw_material_id }}">
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $ingredient->rawMaterial->name }}</td>
                                            <td>
                                                <input type="number" class="form-control wastage-rate"
                                                    name="manufacturing_recipes[{{ $ingredient->raw_material_id }}][wastage_rate]"
                                                    value="{{ $ingredient->wastage_rate }}" min="0" max="100"
                                                    step="0.01">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control quantity"
                                                    name="manufacturing_recipes[{{ $ingredient->raw_material_id }}][quantity]"
                                                    value="{{ $ingredient->quantity }}">
                                            </td>
                                            <td>
                                                <select class="form-control unit-select"
                                                    name="manufacturing_recipes[{{ $ingredient->raw_material_id }}][unit_id]"
                                                    data-current-multiplier="{{ $ingredient->unit->multipler }}">
                                                    @foreach ($units as $unit)
                                                        <option value="{{ $unit->id }}"
                                                            data-multipler="{{ $unit->multipler }}"
                                                            {{ $ingredient->unit_id == $unit->id ? 'selected' : '' }}>
                                                            {{ $unit->actual_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="unit-price">{{ $ingredient->raw_material_price }}</td>
                                            <td class="total-price">
                                                {{ $ingredient->raw_material_price * $ingredient->quantity }}</td>
                                            <td>
                                                <button type="button" class="btn btn-danger remove-product">حذف</button>
                                                <input type="hidden"
                                                    name="manufacturing_recipes[{{ $ingredient->product_id }}][unit_price]"
                                                    value="{{ $ingredient->unit_price }}">
                                                <input type="hidden"
                                                    name="manufacturing_recipes[{{ $ingredient->product_id }}][product_id]"
                                                    value="{{ $ingredient->product_id }}">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="7" class="text-end">
                                            <span class="text-danger" style="font-size: 1.2em;">قيمة تكلفة المكونات: </span>
                                            <span class="text-danger" style="font-size: 1.2em;" id="total_material_cost">
                                                {{ $recipe->materials_cost }}
                                            </span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-lg-3">
                                <label for="total_wastage_rate"
                                    class="form-label">{{ trans('admin.total_wastage_rate') }}</label>
                                <input type="number" class="form-control" name="total_wastage_rate" id="total_wastage_rate"
                                    value="{{ $recipe->total_wastage_rate }}">
                            </div>

                            <div class="col-lg-3">
                                <label for="final_quantity" class="form-label">كمية المنتج النهائية</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="final_quantity" id="final_quantity"
                                        value="{{ $recipe->final_quantity }}">
                                    <select class="form-select" name="final_quantity_unit_id">
                                        @foreach ($units as $unit)
                                            <option value="{{ $unit->id }}"
                                                {{ $recipe->final_quantity_unit_id == $unit->id ? 'selected' : '' }}>
                                                {{ $unit->actual_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-lg-3">
                                <label for="production_cost_value" class="form-label">قيمة تكلفة الإنتاج</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="production_cost_value"
                                        id="production_cost_value" value="{{ $recipe->production_cost_value }}">
                                    <select class="form-select production_cost_type" name="production_cost_type">
                                        <option value="fixed"
                                            {{ $recipe->production_cost_type == 'fixed' ? 'selected' : '' }}>ثابت</option>
                                        <option value="percentage"
                                            {{ $recipe->production_cost_type == 'percentage' ? 'selected' : '' }}>نسبة
                                            مئوية</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-lg-3">
                                <label for="total_cost" class="form-label">{{ trans('admin.total_cost') }}</label>
                                <input type="number" class="form-control" name="total_cost" id="total_cost"
                                    value="{{ $recipe->total_cost }}" readonly>
                                <small class="text-danger d-block mt-1">(قيمة تكلفة الانتاج + قيمة تكلفة المكونات)</small>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label for="description" class="form-label">{{ trans('admin.recipe_instructions') }}</label>
                            <textarea class="form-control" name="description" id="description" rows="4">{{ $recipe->description }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-success mt-3">{{ trans('admin.update_recipe') }}</button>
                    </div>
                </div>
            </div>
        </section>
    </form>
@endsection

@section('script')
  <script>
    $(document).ready(function() {
    let products = @json($products);
    let currentSearchTimeout;
    
    // Search functionality
    $('#search').on('input', function() {
        clearTimeout(currentSearchTimeout);
        let searchTerm = $(this).val().trim();
        
        currentSearchTimeout = setTimeout(() => {
            if (searchTerm.length < 2) {
                $('#result-list').empty();
                return;
            }
            
            let results = products.filter(product => 
                product.name.toLowerCase().includes(searchTerm.toLowerCase())
            );
            
            displaySearchResults(results);
        }, 300);
    });

    // Display search results
    function displaySearchResults(results) {
        let $resultList = $('#result-list').empty();
        
        results.forEach(product => {
            if (!isProductInTable(product.id)) {
                $('<li>')
                    .addClass('list-group-item')
                    .text(product.name)
                    .data('product', product)
                    .appendTo($resultList)
                    .on('click', function() {
                        addProductToTable($(this).data('product'));
                        $('#result-list').empty();
                        $('#search').val('');
                    });
            }
        });
    }

    // Check if product already exists in table
    function isProductInTable(productId) {
        return $(`.sell_table tr[data-product-id="${productId}"]`).length > 0;
    }

    // Add product to table
    function addProductToTable(product) {
        let rowCount = $('.sell_table tr').length + 1;
        let row = `
            <tr data-product-id="${product.id}">
                <td>${rowCount}</td>
                <td>${product.name}</td>
                <td>
                    <input type="number" class="form-control wastage-rate" 
                           name="manufacturing_recipes[${product.id}][wastage_rate]" 
                           value="0" min="0" max="100" step="0.01">
                </td>
                <td>
                    <input type="number" class="form-control quantity" 
                           name="manufacturing_recipes[${product.id}][quantity]" 
                           value="1">
                </td>
                <td>
                    <select class="form-control unit-select" 
                            name="manufacturing_recipes[${product.id}][unit_id]">
                        ${generateUnitOptions()}
                    </select>
                </td>
                <td class="unit-price">${product.price}</td>
                <td class="total-price">${product.price}</td>
                <td>
                    <button type="button" class="btn btn-danger remove-product">حذف</button>
                    <input type="hidden" name="manufacturing_recipes[${product.id}][unit_price]" 
                           value="${product.price}">
                    <input type="hidden" name="manufacturing_recipes[${product.id}][product_id]" 
                           value="${product.id}">
                </td>
            </tr>
        `;
        $('.sell_table').append(row);
        updateTotalCost();
    }

    // Generate unit options
    function generateUnitOptions() {
        return @json($units).map(unit => 
            `<option value="${unit.id}" data-multipler="${unit.multipler}">
                ${unit.actual_name}
            </option>`
        ).join('');
    }

    // Remove product
    $(document).on('click', '.remove-product', function() {
        $(this).closest('tr').remove();
        updateRowNumbers();
        updateTotalCost();
    });

    // Update quantities and calculations
    $(document).on('input', '.quantity, .wastage-rate', function() {
        updateRowTotal($(this).closest('tr'));
        updateTotalCost();
    });

    // Handle unit changes
    $(document).on('change', '.unit-select', function() {
        let $row = $(this).closest('tr');
        let currentMultiplier = parseFloat($(this).find(':selected').data('multipler'));
        let previousMultiplier = parseFloat($(this).data('current-multiplier') || 1);
        
        let $quantityInput = $row.find('.quantity');
        let currentQuantity = parseFloat($quantityInput.val());
        let newQuantity = (currentQuantity * previousMultiplier) / currentMultiplier;
        
        $quantityInput.val(newQuantity.toFixed(2));
        $(this).data('current-multiplier', currentMultiplier);
        
        updateRowTotal($row);
        updateTotalCost();
    });

    // Calculate production costs
    function calculateProductionCost() {
        let materialsCost = parseFloat($('#total_material_cost').text()) || 0;
        let productionCostValue = parseFloat($('#production_cost_value').val()) || 0;
        let productionCostType = $('.production_cost_type').val();
        
        if (productionCostType === 'percentage') {
            return (materialsCost * productionCostValue) / 100;
        }
        return productionCostValue;
    }

    // Update row totals
    function updateRowTotal($row) {
        let quantity = parseFloat($row.find('.quantity').val()) || 0;
        let unitPrice = parseFloat($row.find('.unit-price').text()) || 0;
        let total = quantity * unitPrice;
        $row.find('.total-price').text(total.toFixed(2));
    }

    // Update total cost
    function updateTotalCost() {
        let materialsCost = 0;
        $('.total-price').each(function() {
            materialsCost += parseFloat($(this).text()) || 0;
        });
        
        $('#total_material_cost').text(materialsCost.toFixed(2));
        let productionCost = calculateProductionCost();
        $('#total_cost').val((materialsCost + productionCost).toFixed(2));
    }

    // Update row numbers
    function updateRowNumbers() {
        $('.sell_table tr').each(function(index) {
            $(this).find('td:first').text(index + 1);
        });
    }

    // Initial calculations
    updateTotalCost();
    
    // Production cost type change handler
    $('.production_cost_type, #production_cost_value').on('change input', updateTotalCost);
});
    </script>
@endsection
