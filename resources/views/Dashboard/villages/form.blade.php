<div class="card-body">
    <div class="row">
        <div class="col-lg-12">
            @include('components.form.input', [
                'class' => 'form-control',
                'name' => "name_ar",
                'label' => trans('admin.name_ar'),
                'value' => isset($village) ? $village->name_ar : old('name_ar') ,
                'attribute' => 'required',
            ])
            @include('components.form.input', [
                'class' => 'form-control',
                'name' => "name_en",
                'label' => trans('admin.name_en'),
                'value' => isset($village) ? $village->name_en : old('name_en') ,
                'attribute' => 'required',
            ])
            @include('components.form.select', [
                'collection' => $cities ,
                'index' => 'id',
                'id' => 'city_id',
                'select' => isset($village) ? $village->city_id : old('city_id'),
                'name' => 'city_id',
                'display' => app()->getLocale() == "ar" ? "city_name_ar" : "city_name_en",
                'label' => trans('admin.city'),
                'class' => 'form-control select2',
            ])
        </div>
    </div>
</div>

