<div class="card-body">
    <div class="row">
        <div class="col-lg-6">
            @include('components.form.input', [
                'class' => 'form-control',
                'attribute' => 'required',
                'name' => "username",
                'placeholder' => 'ادخل اسم المستخدم',
                'value' => isset($data) ? $data->username : old('username') ,
                'label' => trans('admin.Username'),
            ])
        </div>

        <div class="col-lg-6">
            @include('components.form.input', [
                'class' => 'form-control',
                'name' => "name",
                'placeholder' => 'ادخل اسم المستخدم',
                'label' => trans('admin.Name'),
                'value' => isset($data) ? $data->name : old('name') ,
                'attribute' => 'required',
            ])
        </div>

        <div class="col-lg-6">
            @include('components.form.input', [
                'type' => 'password',
                'class' => 'form-control',
                'name' => "password",
                'placeholder' => 'ادخل كلمة المرور',
                'label' => trans('admin.Password'),
                'value' => old('password'),
            ])
        </div>
        <div class="col-lg-6">
            @include('components.form.select', [
                'collection' => $branches,
                'index' => 'id',
                'select' => isset($data) ? $data->branch_id : old('branch_id'), // استخدم branch_id مباشرةً
                'name' => 'branch_id',
                'label' => trans('admin.main_branch'),
                'class' => 'form-control select2',
                'firstDisabled' => true,
                'id' => 'branch_id',
                'attribute' => 'required',
                'placeholder' => 'اختر الفرع',
            ])
            
        </div>

        <div class="col-lg-6">
            <div class="">
                @php
                $branch_ids = [];
                if(isset($data)){
                    $branch_ids = $data->Branches()->pluck('branch_id')->toArray();
                }
            @endphp
            <x-form.multiple-select class="form-control select2" id="branch_ids"
                :collection="$branches" :selectArr="$branch_ids" index="id"
                name="branch_ids[]" label="{{ trans('admin.branches') }}" display="name"  attribute="required"/>
            
            </div>
        </div>

        <div class="col-lg-6">
            @include('components.form.select', [
                'collection' => $roles,
                'index' => 'id',
                'select' => isset($data) ? $data->getRoleId() : old('role_id'),
                'name' => 'role_id',
                'label' => trans('admin.Roles'),
                'class' => 'form-control select2',
                'firstDisabled' => true,
                'attribute' => 'required',
                'id' => 'role_id',
                'placeholder' => 'اختر الدور',
            ])
        </div>
        @if($settings->hr_module)
        <div class="col-lg-6">
            @include('components.form.select', [
                'collection' => [
                    ['id' => 'monthly', 'name' => trans('admin.Monthly')],
                    ['id' => 'weekly', 'name' => trans('admin.Weekly')],
                    ['id' => 'daily', 'name' => trans('admin.Daily')]
                ],
                'id' => 'payment_method',
                'select' => isset($data) ? $data->payment_method : old('payment_method'),
                'name' => 'payment_method',
                'label' => trans('admin.Payment Method'),
                'class' => 'form-control select2',
                'placeholder' => 'اختر طريقة الحساب',
                'firstDisabled' => true,
                'attribute' => 'required',
                'onchange' => 'toggleSalaryFields(this.value)'
            ])
        </div>
        <div class="col-lg-6">
            @include('components.form.input', [
                'type' => 'number',
                'step' => '0.01',
                'class' => 'form-control',
                'name' => "working_hours_count",
                'value' => isset($data) ? $data->working_hours_count : old('working_hours_count'),
                'label' => trans('admin.Number Of Hours'),
                'placeholder' => 'ادخل عدد الساعات',
            ])
        </div>
        <div class="col-lg-6">
            @include('components.form.input', [
                'type' => 'number',
                'step' => '0.01',
                'class' => 'form-control',
                'name' => "salary",
                'value' => isset($data) ? $data->salary : old('salary'),
                'label' => '<span id="salaryLabel">' . trans('admin.Salary') . '</span>',
                'placeholder' => 'ادخل الراتب',
            ])
        </div>
        <div class="col-lg-6">
            @include('components.form.input', [
                'type' => 'number',
                'step' => '0.01',
                'class' => 'form-control',
                'placeholder' => 'ادخل عدد ايام الاجازة',
                'name' => "vacation_days_count",
                'value' => isset($data) ? $data->vacation_days_count : old('vacation_days_count'),
                'label' => trans('admin.Number Of Vacation Days'),
            ])
        </div>
        <div class="col-lg-6">

            @include('components.form.input', [
                'type' => 'number',
                'step' => '0.01',
                'class' => 'form-control',
                'placeholder' => 'ادخل سعر الساعة',
                'name' => "hour_price",
                'value' => isset($data) ? $data->hour_price : old('hour_price'),
                'label' => trans('admin.Hour Price') . ' <span class="text-danger">' . trans('admin.Hour Price Note') . '</span>',
            ])
        </div>
        <div class="col-lg-6">
            @include('components.form.input', [
                'type' => 'time',
                'class' => 'form-control',
                'name' => "presence_time",
                'label' => trans('admin.Presence Time') . ' <span class="text-danger">' . trans('admin.Presence Time Note') . '</span>',
            ])
        </div>
        <div class="col-lg-6">
            @include('components.form.input', [
                'type' => 'time',
                'class' => 'form-control',
                'name' => "leave_time",
                'label' => trans('admin.Leave Time') . ' <span class="text-danger">' . trans('admin.Leave Time Note') . '</span>',
            ])
        </div>
        <div class="col-lg-6">
            @include('components.form.input', [
                'type' => 'number',
                'step' => '0.01',
                'class' => 'form-control',
                'placeholder' => 'ادخل سعر الساعة الاضافية',
                'name' => "overtime_hour_price",
                'value' => isset($data) ? $data->overtime_hour_price : old('overtime_hour_price'),
                'label' => trans('admin.Overtime Hour Price') . ' <span class="text-danger">' . trans('admin.Overtime Hour Price Note') . '</span>',
            ])
        </div>
        @endif
    </div>
</div>

