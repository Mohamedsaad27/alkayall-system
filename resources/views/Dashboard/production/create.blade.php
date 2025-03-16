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

@section('title', trans('admin.create_production_line'))
@section('content')
    <style>
        .traffic-light {
            position: relative;
            margin: 0;
            padding: 0;
        }

        .traffic-light input[type="radio"] {
            display: none;
        }

        .traffic-light label {
            cursor: pointer;
            font-weight: 500;
            border: 2px solid #ddd;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .traffic-light input[value="1"]+label {
            color: #198754;
        }

        .traffic-light input[value="0"]+label {
            color: #dc3545;
        }

        .traffic-light input[value="1"]:checked+label {
            background: #198754;
            border-color: #198754;
            color: white;
        }

        .traffic-light input[value="0"]:checked+label {
            background: #dc3545;
            border-color: #dc3545;
            color: white;
        }

        .traffic-light label:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

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
            Width: 120px;
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
    <form method="post" action="{{ route('dashboard.production.store') }}">
        @csrf
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-sm-6">
                        <h1 class="m-0">{{ trans('admin.production_line') }}</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a
                                    href="{{ route('dashboard.home') }}">{{ trans('admin.Home') }}</a>
                                / <a
                                    href="{{ route('dashboard.production.index') }}">{{ trans('admin.production_line') }}</a>
                                / {{ trans('admin.Create') }}</li>
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
                <div class="row my-2">
                    <div class="col-lg-12">
                        <div class="row">
                            <div class="col-lg-3">
                                @include('components.form.input', [
                                    'class' => 'form-control',
                                    'name' => 'production_line_code',
                                    'label' => trans('admin.production_line_code'),
                                    'value' => isset($production_line)
                                        ? $production_line->production_line_code
                                        : old('production_line_code'),
                                    'attribute' => 'readonly',
                                ])
                            </div>
                            <div class="col-lg-3">
                                @include('components.form.select', [
                                    'collection' => $branches,
                                    'index' => 'id',
                                    'select' => isset($branch_id) ? $branch_id : old('branch_id'),
                                    'name' => 'branch_id',
                                    'label' => trans('admin.branch'),
                                    'class' => 'form-control select2',
                                    'attribute' => 'required',
                                    'id' => 'branch_id',
                                ])
                            </div>
                            <div class="col-lg-3">
                                @include('components.form.select', [
                                    'collection' => $productRecipe->map(function ($recipe) {
                                        return ['id' => $recipe->id, 'name' => $recipe->finalProduct->name];
                                    }),
                                    'index' => 'id',
                                    'select' => isset($recipe_id) ? $recipe_id : old('recipe_id'),
                                    'name' => 'recipe_id',
                                    'label' => trans('admin.recipe'),
                                    'class' => 'form-control select2 recipe_id product-select',
                                    'attribute' => 'required',
                                    'id' => 'recipe_id',
                                ])

                            </div>
                            <div class="col-lg-3">
                                <label for="final_quantity" class="form-label fw-medium mb-2">
                                    {{ trans('admin.quantity') }}
                                </label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control" name="quantity" id="final_quantity"
                                        value="{{ old('final_quantity') }}"
                                        placeholder="{{ trans('admin.enter_quantity') }}" required>
                                    <select class="form-select" name="quantity_unit_id">
                                        @foreach ($units as $unit)
                                            <option value="{{ $unit->id }}">{{ $unit->actual_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
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
            <div class="container-fluid ">
                <div class="row">
                    <!-- left column -->
                    <div class="col-md-12 ">
                        <!-- general form elements -->
                        <div class="card card-primary ">
                            <div class="card-header">
                                <h4 class="text-center fw-bold ">
                                    {{ trans('admin.materials') }}
                                </h4>
                            </div>
                            <!-- /.card-header -->

                            <div class="row px-5">
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
                                            </tr>
                                        </thead>

                                        <tbody class="production_table">

                                        </tbody>

                                    </table>
                                    <tfoot>
                                        <tr>
                                            <td colspan="7" class="text-end">
                                                <span class="text-danger" style="font-size: 1.2em;"> قيمة تكلفة المكونات :
                                                </span>
                                                <span class="text-danger" style="font-size: 1.2em;"
                                                    id="total_material_cost">0</span>
                                                    <input type="hidden" name="total_material_cost" id="total_material_cost_input">
                                            </td>
                                        </tr>
                                    </tfoot>
                                </div>



                            </div>
                            <!-- /.card-body -->
                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-lg-3">
                                        <div class="mb-3">
                                            <label for="total_wastage_rate" class="form-label fw-medium">
                                                {{ trans('admin.total_wastage_rate') }}
                                            </label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="total_wastage_rate"
                                                    id="total_wastage_rate" value="0" step="0.01"
                                                    placeholder="{{ trans('admin.enter_rate') }}">
                                                <select class="form-select" name="wastage_rate_unit_id">
                                                    @foreach ($units as $unit)
                                                        <option value="{{ $unit->id }}">{{ $unit->actual_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
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

                                    <div class="col-lg-3">
                                        <label class="form-label fw-medium mb-2">{{ trans('admin.is_ended') }}</label>
                                        <div class="d-flex gap-3">
                                            <div class="form-check traffic-light">
                                                <input class="form-check-input" type="radio" name="is_ended"
                                                    value="1" id="is_ended_yes">
                                                <label class="form-check-label px-3 py-2 rounded" for="is_ended_yes">
                                                    {{ trans('admin.yes') }}
                                                </label>
                                            </div>
                                            <div class="form-check traffic-light">
                                                <input class="form-check-input" type="radio" name="is_ended"
                                                    value="0" id="is_ended_no">
                                                <label class="form-check-label px-3 py-2 rounded" for="is_ended_no">
                                                    {{ trans('admin.no') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
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
      $(document).ready(function() {
    let recipeDetails = null;

    $('#recipe_id').on('change', function() {
        const recipeId = $(this).val();
        if (!recipeId) return;
        loadRecipeDetails(recipeId);
    });

    function loadRecipeDetails(recipeId) {
        $.ajax({
            url: `/dashboard/recipe/${recipeId}/details`,
            method: 'GET',
            success: function(response) {
                recipeDetails = response;
                updateProductionTable(response.ingredients);
                
                // Set initial production quantity if empty
                if (!$('#final_quantity').val()) {
                    $('#final_quantity').val(response.recipe.final_quantity);
                    $('select[name="quantity_unit_id"]').val(response.recipe.unit_id);
                }
                
                updateIngredientQuantities();
                calculateTotalMaterialCost();
            },
            error: function(xhr) {
                console.error('Error fetching recipe details:', xhr);
            }
        });
    }

    // Handle both quantity and unit changes
    $('#final_quantity, select[name="quantity_unit_id"]').on('change input', updateIngredientQuantities);

    function getUnitMultiplier(unitId) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: '/dashboard/units/' + unitId + '/multiplier',
                method: 'GET',
                success: function(response) {
                    resolve(response.multiplier);
                },
                error: function(xhr) {
                    console.error('Error fetching unit multiplier:', xhr);
                    reject(xhr);
                }
            });
        });
    }
    async function updateIngredientQuantities() {
    if (!recipeDetails) return;

    const recipeQuantity = parseFloat(recipeDetails.recipe.final_quantity);
    const productionQuantity = parseFloat($('#final_quantity').val()) || 0;
    
    const recipeUnitId = recipeDetails.recipe.unit_id;
    const selectedUnitId = $('select[name="quantity_unit_id"]').val();
    
    try {
        const [recipeMultiplier, selectedMultiplier] = await Promise.all([
            getUnitMultiplier(recipeUnitId),
            getUnitMultiplier(selectedUnitId)
        ]);

        const recipeBaseQty = recipeQuantity * recipeMultiplier;
        const productionBaseQty = productionQuantity * selectedMultiplier;
        const ratio = productionBaseQty / recipeBaseQty;

        // Update both visible and hidden quantity inputs
        $('.production_table tr').each(function(index) {
            const originalQuantity = recipeDetails.ingredients[index].quantity;
            const newQuantity = (originalQuantity * ratio).toFixed(2);
            
            // Update both visible disabled input and hidden input
            $(this).find('.quantity').val(newQuantity);
            $(this).find('.quantity-hidden').val(newQuantity);
        });

        calculateTotalMaterialCost();
    } catch (error) {
        console.error('Error calculating quantities:', error);
    }
}

function updateProductionTable(ingredients) {
    console.log(ingredients);
    const tbody = $('.production_table');
    tbody.empty();

    ingredients.forEach((ingredient, index) => {
        const row = `
            <tr>
                <td>${index + 1}</td>
                <td>${ingredient.raw_material.name}</td>
                <td>
                    <input type="number" name="ingredients[${ingredient.raw_material.id}][wastage_rate]" 
                        class="form-control wastage-rate" value="${ingredient.wastage_rate || 0}"
                        min="0" step="0.01" disabled>
                </td>
                <td>
                    <input type="number" class="form-control quantity" 
                        value="${ingredient.quantity}"
                        min="0" step="0.01" disabled>
                    <input type="hidden" name="ingredients[${ingredient.raw_material.id}][quantity]" 
                        class="quantity-hidden" value="${ingredient.quantity}">
                </td>
                <td>
                    ${ingredient.unit.actual_name}
                    <input type="hidden" name="ingredients[${ingredient.raw_material.id}][unit_id]" 
                        value="${ingredient.unit.id}">
                </td>
                <td>
                    <input type="number" class="form-control unit-price" 
                        value="${ingredient.raw_material_price || 0}"
                        min="0" step="0.01" disabled>
                    <input type="hidden" name="ingredients[${ingredient.raw_material.id}][unit_price]" 
                        value="${ingredient.raw_material_price || 0}">
                </td>
                <td>
                    <span  class="total-price">0</span>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function calculateTotalMaterialCost() {
    let totalMaterialCost = 0;
    $('.production_table tr').each(function() {
        const quantity = parseFloat($(this).find('.quantity').val()) || 0;
        const unitPrice = parseFloat($(this).find('.unit-price').val()) || 0;
        const totalPrice = quantity * unitPrice;
        $(this).find('.total-price').text(totalPrice.toFixed(2));
        totalMaterialCost += totalPrice;
    });

    $('#total_material_cost').text(totalMaterialCost.toFixed(2));
    $('#total_material_cost_input').val(totalMaterialCost.toFixed(2));
    
    updateTotalCost();
}
function updateTotalCost() {
    const materialCost = parseFloat($('#total_material_cost').text()) || 0;
    const productionCostValue = parseFloat($('#production_cost_value').val()) || 0;
    const productionCostType = $('.production_cost_type').val();

        let productionCost = productionCostType === 'percentage' ?
            (materialCost * productionCostValue / 100) :
            productionCostValue;

        const totalCost = materialCost + productionCost;
        $('#total_cost').val(totalCost.toFixed(2));
    }

    $('#production_cost_value, .production_cost_type').on('change', updateTotalCost);
});
    </script>
    <script>
        $(document).ready(function() {
            $('#recipe_id').on('change', function() {
                const recipeId = $(this).val();
                if (recipeId) {
                    $.ajax({
                        url: '{{ route('generate.production.line.code') }}', // Update with your route name
                        method: 'GET',
                        data: {
                            recipe_id: recipeId
                        },
                        success: function(response) {
                            $('input[name="production_line_code"]').val(response).prop(
                                'readonly', true);
                        },
                        error: function() {
                            alert("Failed to fetch production line code.");
                        }
                    });
                }
            });
        });
    </script>
@endsection
