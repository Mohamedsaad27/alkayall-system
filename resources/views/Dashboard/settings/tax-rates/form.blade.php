<div class="card-body">
    <div class="row">
        <div class="col-lg-12">
            @include('components.form.input', [
                'class' => 'form-control',
                'name' => "name",
                'label' => trans('admin.name'),
                'value' => isset($taxRate) ? $taxRate->name : old('name') ,
                'attribute' => '',
            ])
          
            @include('components.form.input', [
                'class' => 'form-control',
                'name' => "rate",
                'label' => trans('admin.Rate'),
                'value' => isset($taxRate) ? $taxRate->rate : old('rate') ,
                'attribute' => '',
            ])
             <div class="col-lg-4">
                <x-form.checkbox class="form-control" label="{{trans('admin.is_active')}}" tag="AddAsSubUnitAjax"
                value="1"  name="is_active"
                attribute="{{ isset($data) ? ($data->is_active != null) ? 'checked' : '' : '' }}"/>
                @error('is_active')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>
</div>
