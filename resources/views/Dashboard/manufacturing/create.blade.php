@extends('layouts.admin')
@section('style')
    <style>
        /* Simple styling for results dropdown */


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

        .list-group-item.disabled {
            pointer-events: none;
            /* Prevent any clicks */
            opacity: 0.5;
            /* Make it look disabled */
            background-color: #f8f9fa;
            /* Change background color if needed */
        }

        .result-list li:hover {
            background-color: #ddd;
        }
    </style>
@endsection

@section('title', trans('admin.manufacturing_recipes'))
@section('content')
    <style>
        .modal-dialog {
            max-width: 1000px;
        }

        .input-group> :not(:first-child):not(.dropdown-menu):not(.valid-tooltip):not(.valid-feedback):not(.invalid-tooltip):not(.invalid-feedback) {
            margin-right: -1px;
            margin-left: 0;
            border-right: 0;
            border-left: 1px solid #dee2e6;
        }

        .input-group .form-control {
            border-radius: 0 4px 4px 0 !important;
        }

        .input-group .form-select {
            border-radius: 4px 0 0 4px !important;
        }

        /* Additional styles for better RTL support */
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .input-group {
            direction: ltr;
            /* Forces LTR for proper input group layout */
        }

        .input-group input {
            text-align: right;
            /* Keeps text RTL inside input */
        }
    </style>
    <!-- form start -->
    <form method="post" action="">
        @csrf
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-sm-6">
                        <h1 class="m-0">{{ trans('admin.create_recipe') }}</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ trans('admin.Home') }}</a>
                                / <a
                                    href="{{ route('dashboard.manufacturing.index') }}">{{ trans('admin.manufacturing_recipes') }}</a>
                                / {{ trans('admin.Create') }}</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
                <div class="row my-2">
                    <div class="col-lg-12">
                        <div class="row">
                            <div class="col-lg-12">
                                @include('components.form.select', [
                                    'collection' => $productsCollection,
                                    'index' => 'id',
                                    'select' => isset($final_product_id)
                                        ? $final_product_id
                                        : old('final_product_id'),
                                    'name' => 'final_product_id',
                                    'label' => trans('admin.final_product'),
                                    'class' => 'form-control select2 final_product_id product-select',
                                    'attribute' => 'required',
                                    'id' => 'final_product_id',
                                ])
                            </div>
                        </div>

                    </div>
                    <div class="col-lg-3">
                    </div>
                    <div class="col-sm-3 d-flex align-items-center justify-content-end">

                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <section class="content ">
            <div id="print-section">
            </div>
            @include('Dashboard.stock-transfer.parts.AddBulckProductsPopUp')
            <div class="container-fluid ">
                <div class="row">
                    <!-- left column -->
                    <div class="col-md-12 ">
                        <!-- general form elements -->
                        <div class="card card-primary ">
                            <div class="card-header">

                            </div>
                            <!-- /.card-header -->

                            <div class="row px-5">

                                <div class="col-lg-12 py-2">
                                    <div>

                                        <input type="text" id="search" class="form-control"
                                            placeholder="ابحث عن المنتج ...." autocomplete="off" autofocus>
                                        <ul id="result-list" class="result-list list-group">
                                            <!-- Products will be appended here -->
                                        </ul>

                                    </div>
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

                                        </tbody>

                                    </table>
                                    <tfoot>
                                        <tr>
                                            <td colspan="7" class="text-end">
                                                <span class="text-danger" style="font-size: 1.2em;"> قيمة تكلفة المكونات :
                                                </span>
                                                <span class="text-danger" style="font-size: 1.2em;"
                                                    id="total_material_cost">0</span>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </div>



                            </div>
                            <!-- /.card-body -->
                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-lg-3">
                                        <label for="total_wastage_rate" class="form-label fw-medium mb-2">
                                            {{ trans('admin.total_wastage_rate') }}
                                        </label>
                                        <input type="number" class="form-control" name="total_wastage_rate" step="0.01"
                                            id="total_wastage_rate" value="0">
                                    </div>

                                    <div class="col-lg-3">
                                        <label for="final_quantity" class="form-label fw-medium mb-2">
                                            كمية المنتج النهائية
                                        </label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="final_quantity"
                                                id="final_quantity" value="0" step="0.01">
                                            <select class="form-select" name="final_quantity_unit_id">
                                                @foreach ($units as $unit)
                                                    <option value="{{ $unit->id }}">{{ $unit->actual_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-3">
                                        <label for="production_cost_value" class="form-label fw-medium mb-2">
                                            قيمة تكلفة الإنتاج
                                        </label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="production_cost_value"
                                                id="production_cost_value" value="0" step="0.01" 
                                                style="border-start-start-radius: 0 !important; border-end-start-radius: 0 !important;">
                                            <select class="form-select production_cost_type" name="production_cost_type"
                                                style="border-start-end-radius: 4px !important; border-end-end-radius: 4px !important; max-width: 100px;">
                                                <option value="fixed">ثابت</option>
                                                <option value="percentage">نسبة مئوية</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-3">
                                        <label for="total_cost" class="form-label fw-medium mb-2">
                                            {{ trans('admin.total_cost') }}
                                        </label>
                                        <input type="number" class="form-control" name="total_cost" id="total_cost"
                                            value="0" readonly>
                                        <small class="text-danger d-block mt-1">
                                            (قيمة تكلفة الانتاج + قيمة تكلفة المكونات)
                                        </small>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label for="description"
                                        class="form-label">{{ trans('admin.recipe_instructions') }}</label>
                                    <textarea class="form-control" name="description" id="description" rows="4"
                                        placeholder="{{ trans('admin.enter_instructions') }}"></textarea>
                                </div>
                                <button type="submit"
                                    class="btn btn-success mt-3">{{ trans('admin.create_recipe') }}</button>
                            </div>


                        </div>
                        <!-- /.card -->
                    </div>
                </div><!-- /.container-fluid -->
        </section>
    </form>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const settings = {
                allow_unit_price_update: {{ session('allow_unit_price_update') ? 'true' : 'false' }},
            };

            const priceInputs = document.querySelectorAll('.unit-price-input');
            priceInputs.forEach(input => {
                input.disabled = !settings.allow_unit_price_update;
            });
        });
    </script>
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
    <script>
        function calculateTotalCost() {
            let totalMaterialCost = 0;

            // Calculate total material cost from all rows
            $('.sell_table tr').each(function() {
                const quantity = parseFloat($(this).find('.quantity').val()) || 0;
                const unitPrice = parseFloat($(this).find('.unit-price').text()) || 0;
                const rowTotal = quantity * unitPrice;
                $(this).find('.total-price').text(rowTotal.toFixed(2));
                totalMaterialCost += rowTotal;
            });

            // Update total material cost display
            $('#total_material_cost').text(totalMaterialCost.toFixed(2));

            // Calculate total cost (material cost + production cost)
            const productionCost = parseFloat($('#production_cost_value').val()) || 0;
            const totalCost = totalMaterialCost + productionCost;
            $('#total_cost').val(totalCost.toFixed(2));
        }

        $(document).ready(function() {
            // Previous document.ready code remains unchanged

            // Add event listeners for quantity changes
            $(document).on('input', '.quantity', function() {
                calculateTotalCost();
            });

            // Add event listeners for unit price changes
            $(document).on('change', '.unit-select', function() {
                const row = $(this).closest('tr');
                const unitId = $(this).val();
                const productId = row.data('product-id');
                const multiplier = parseFloat($(this).find(':selected').data('multipler')) || 1;

                $.ajax({
                    url: '{{ route('dashboard.products.unit_price') }}',
                    method: 'GET',
                    data: {
                        product_id: productId,
                        unit_id: unitId
                    },
                    success: function(response) {
                        const unitPrice = response.unit_price || 0;
                        row.find('.unit-price').text(unitPrice.toFixed(2));
                        row.find('input[name*="[unit_price]"]').val(unitPrice);
                        calculateTotalCost();
                    },
                    error: function() {
                        alert('Failed to fetch unit price. Please try again.');
                    }
                });
            });

            // Add event listener for production cost changes
            $('#production_cost_value').on('input', function() {
                calculateTotalCost();
            });

            // Add event listener for removing products
            $(document).on('click', '.remove-product', function() {
                $(this).closest('tr').remove();
                updateRowNumbers();
                calculateTotalCost();
            });

            // Calculate initial total when adding new products
            function addNewRow(product) {
                // Previous addNewRow code remains unchanged
                calculateTotalCost();
            }
        });
    </script>
    <script type="text/javascript">
        //    Search 
        $(document).ready(function() {
            let rowCounter = 1;

            $('#search').on('input', function() {
                let query = $(this).val();
                let branchId = $('.from_branch_id').val();

                searchProducts(query, branchId);
            });
            $(document).on('click', '.product-item', function() {
                let productId = $(this).data('id');
                let branchId = $('.from_branch_id').val();
                addProductToTable(productId, branchId);
            });

            $(document).on('change', '.unit-select', function() {
                let row = $(this).closest('tr');
                let productRowId = row.data('product-id');
                let selectedUnitId = $(this).val();
                let branchId = $('.from_branch_id').val();

                updateProductUnit(productRowId, selectedUnitId, branchId, row);
            });

            $(document).on('input', '.quantity', function() {
                let row = $(this).closest('tr');
                let quantity = parseInt($(this).val()) || 0;
                updateAvailableQuantity(row, quantity);
                console.log('test from input quanitty');
            });

            $(document).on('click', '.remove-product', function() {
                $(this).closest('tr').remove();
                updateRowNumbers();
            });

            function displayMessage(message, className) {
                $('#result-list').empty().append(`<li class="list-group-item ${className}">${message}</li>`);
            }

            function searchProducts(query, branchId) {
                $.ajax({
                    url: '{{ route('dashboard.manufacturing.search.products') }}',
                    method: 'GET',
                    data: {
                        query: query,
                        branch_id: branchId
                    },
                    success: function(data) {
                        $('#result-list').empty();
                        if (data.length === 0) {
                            displayMessage('لا توجد منتجات مطابقة', '');
                        } else {
                            $.each(data, function(index, product) {
                                $('#result-list').append(`
                        <li tabindex="0" class="list-group-item product-item"
                            data-id="${product.id}"
                            data-name="${product.name}"
                            data-purchase-price="${product.purchase_price}"
                            data-sku="${product.sku}">
                            <div class="d-flex justify-content-between align-items-center text-start">
                                <div class="p-2">
                                    <strong>SKU:</strong> ${product.sku}
                                </div>
                                <div class="p-2">
                                    <strong>اسم المنتج:</strong> ${product.name}
                                </div>
                                <div class="p-2">
                                    <strong>سعر الشراء:</strong> ${product.purchase_price || 'غير متوفر'}
                                </div>
                            </div>
                        </li>
                    `);
                            });
                        }
                    },
                    error: function() {
                        displayMessage('حدث خطأ أثناء البحث', 'text-danger');
                    }
                });
            }

            function addProductToTable(productId, branchId) {
                $.ajax({
                    url: '{{ route('dashboard.sells.stock.products.row.add') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        product_id: productId,
                        branch_id: branchId,
                    },
                    success: function(response) {
                        let existingRow = $(`.sell_table tr[data-product-id="${response.id}"]`);
                        if (existingRow.length > 0) {
                            updateExistingRow(existingRow, response);
                        } else {
                            addNewRow(response);
                        }
                        clearSearchResults();
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        alert(`Error: ${xhr.status} ${xhr.statusText}`);
                        // لا تقم بمسح البيانات عند حدوث خطأ
                    }
                });
            }

            function updateExistingRow(row, product) {
                let quantityInput = row.find('.quantity');
                let currentQuantity = parseInt(quantityInput.val()) || 0;
                let newQuantity = currentQuantity + 1;
                quantityInput.val(newQuantity);
                row.find('.available-quantity').text(product.available_quantity - newQuantity);
            }

            function addNewRow(product) {
                $('.sell_table').append(`
                <tr data-product-id="${product.id}">
                    <td>${rowCounter++}</td>
                    <td>${product.name}</td>
                    <td>
                        <input type="number" class="form-control wastage-rate" 
                               name="manufacturing_recipes[${product.id}][wastage_rate]" 
                               value="0" min="0" max="100" step="0.01">
                    </td>
                    <td>
                        <input type="number" class="form-control quantity" 
                          step="0.01"     name="manufacturing_recipes[${product.id}][quantity]" >
                    </td>
                    <td>
                        <select class="form-control unit-select" 
                                name="manufacturing_recipes[${product.id}][unit_id]">
                            ${product.units.map(unit => 
                                `<option value="${unit.id}" 
                                                data-multipler="${unit.multipler}" 
                                                     ${product.unit == unit.id ? 'selected' : ''}>
                                                     ${unit.actual_name}
                                        </option>`
                            ).join('')}
                        </select>
                    </td>
                    <td class="unit-price">${product.main_unit_purchase_price || 0}</td>
                    <td class="total-price">${product.main_unit_purchase_price || 0}</td>
                    <td>
                        <button type="button" class="btn btn-danger remove-product">حذف</button>
                    </td>
                    <input type="hidden" name="manufacturing_recipes[${product.id}][unit_price]" value="${product.main_unit_purchase_price || 0}">
                    <input type="hidden" name="manufacturing_recipes[${product.id}][product_id]" value="${product.id}">
                    <input type="hidden" name="manufacturing_recipes[${product.id}][id]" value="${product.id}">
                </tr>
            `);

                $(`tr[data-product-id="${product.id}"]`).on('input', '.quantity', function() {
                    const row = $(this).closest('tr');
                    const quantity = parseFloat(row.find('.quantity').val()) || 0;
                    const unitPrice = parseFloat(row.find('.unit-price').text()) || 0;
                    const totalPrice = quantity * unitPrice;
                    row.find('.total-price').text(totalPrice.toFixed(2));
                    calculateTotalCost();
                });

                scrollToBottom();
            }
            $(document).on('change', '.unit-select', function() {
                const row = $(this).closest('tr');
                const unitId = $(this).val();
                const productId = row.data('product-id');
                const multiplier = parseFloat($(this).find(':selected').data('multipler')) || 1;

                $.ajax({
                    url: '{{ route('dashboard.products.unit_price') }}',
                    method: 'GET',
                    data: {
                        product_id: productId,
                        unit_id: unitId
                    },
                    success: function(response) {
                        const unitPrice = response.unit_price || 0;
                        row.find('.unit-price').text(unitPrice.toFixed(2));
                        row.find('input[name*="[unit_price]"]').val(unitPrice);
                        calculateTotalCost();
                    },
                    error: function() {
                        alert('Failed to fetch unit price. Please try again.');
                    }
                });
            });

            function updateProductUnit(productId, unitId, branchId, row) {
                $.ajax({
                    url: '{{ route('dashboard.units.stock.product.update') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        product_id: productId,
                        unit_id: unitId,
                        branch_id: branchId
                    },
                    success: function(response) {
                        var quantityInput = row.find('.quantity');
                        var quantity = parseInt(quantityInput.val()) || 0;

                        row.find('.unit_multipler_' + productId).val(response.unit_multipler);
                        // Removed min/max validation
                        row.find('.available-quantity').text(response.available_quantity);
                        row.find('#main_available_quantity').val(response.available_quantity);
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        alert(`Error: ${xhr.status} ${xhr.statusText}`);
                    }
                });
            }

            function clearSearchResults() {
                $('#search').val('');
                $('#result-list').empty();
            }

            function clearAddedProducts() {
                $('.sell_table').empty();
            }


            function scrollToBottom() {
                $("html, body").animate({
                    scrollTop: $(document).height()
                }, 1000);
            }

            function updateRowNumbers() {
                let counter = 1;
                $('.sell_table tr').each(function() {
                    $(this).find('td:first').text(counter++);
                });
            }
        });

        function updateRowNumbers() {
            $('.sell_table tr').each(function(index) {
                $(this).find('.row-number').text(index + 1); // تحديث رقم الصف
            });
        }

        $(document).on('click', '.remove-product', function() {
            $(this).closest('tr').remove();
            updateRowNumbers();
        });
    </script>


    <script>
        $(document).ready(function() {
            // Function to handle autofocus for Select2 dropdowns
            function setupSelect2Autofocus(selector, placeholder) {
                $(selector).select2({
                    placeholder: placeholder,
                });

                $(selector).on('select2:open', function() {
                    // Use a small timeout to ensure the search field is rendered
                    setTimeout(function() {
                        let searchField = document.querySelector(
                            '.select2-container .select2-search__field');
                        if (searchField) {
                            searchField.focus();
                        }
                    }, 0);
                });
            }
            // Setup autofocus for contact type dropdown
            setupSelect2Autofocus('#final_product_id', 'اختر المنتج');
        });
    </script>
@endsection
