<div class="row">
    <div class="col-lg-12">
        <div class="row">
            <div class="col-lg-3">
                @include('components.form.input', [
                    'class' => 'form-control',
                    'name' => "recipe_name",
                    'label' => trans('admin.recipe_name'),
                    'value' => $recipe->finalProduct?->name,
                    'attribute' => 'required disabled',
                ])
            </div>
            <div class="col-lg-3">
                @include('components.form.input', [
                    'class' => 'form-control',
                    'name' => "total_cost",
                    'label' => trans('admin.total_cost'),
                    'value' => $recipe->total_cost,
                    'attribute' => 'required disabled',
                ])
            </div>
            <div class="col-lg-3">
                @include('components.form.input', [
                    'class' => 'form-control',
                    'name' => "created_at",
                    'label' => trans('admin.Created_At'),
                    'value' => \Carbon\Carbon::parse($recipe->created_at)->format('d-m-Y h:i'),
                    'attribute' => 'required disabled',
                ])
            </div>
        </div>
    </div>

    <div class="col-lg-12">
        <table id="example1" class="table table-bordered table-striped responsive">
            <thead>
            <tr>
                <th>{{ trans('admin.product') }}</th>
                <th>{{ trans('admin.wastage_rate') }}</th>
                <th>{{ trans('admin.quantity') }}</th>
                <th>{{ trans('admin.unit') }}</th>
                <th>{{ trans('admin.price') }}</th>
            </tr>
            </thead>
            <tbody>
                @foreach ($recipe->ingredients as $ingredient)
                    <tr>
                        <td>{{$ingredient->rawMaterial?->name}}</td>
                        <td>{{$ingredient->wastage_rate}}</td>
                        <td>{{$ingredient->quantity}}</td>
                        <td>{{$ingredient->unit?->actual_name}}</td>
                        <td>{{$ingredient->raw_material_price}}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Footer Section -->
    <div class="col-lg-12">
        <div class="row mt-4">
            <div class="col-lg-3">
                @include('components.form.input', [
                    'class' => 'form-control',
                    'name' => "final_quantity",
                    'label' => trans('admin.final_quantity') ,
                    'value' => $recipe->final_quantity . ' (' . $recipe->unit->actual_name . ')',
                    'attribute' => 'required disabled',
                ])
            </div>
            <div class="col-lg-3">
                @include('components.form.input', [
                    'class' => 'form-control',
                    'name' => "production_cost_value",
                    'label' => trans('admin.production_cost_value'),
                    'value' => $recipe->production_cost_value,
                    'attribute' => 'required disabled',
                ])
            </div>
            <div class="col-lg-3">
                @include('components.form.input', [
                    'class' => 'form-control',
                    'name' => "materials_cost",
                    'label' => trans('admin.materials_cost'),
                    'value' => $recipe->materials_cost,
                    'attribute' => 'required disabled',
                ])
            </div>
        </div>
    </div>
</div>
