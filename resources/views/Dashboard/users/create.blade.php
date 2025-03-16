@extends('layouts.admin')

@section('title', trans('admin.Users'))

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
            <h1 class="m-0">{{ trans('admin.Users') }}</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{route('dashboard.home')}}">{{ trans('admin.Home') }}</a> / <a href="{{route('dashboard.users.index')}}">{{ trans('admin.Users') }}</a> / {{ trans('admin.Create') }}</li>
            </ol>
            </div><!-- /.col -->
        </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <section class="content">
        <div class="container-fluid">
        <div class="row">
            <!-- left column -->
            <div class="col-md-12">
            <!-- general form elements -->
            <div class="card card-primary">
                <div class="card-header">
                <h3 class="card-title">{{ trans('admin.Create') }}</h3>
                </div>
                <!-- /.card-header -->
                <!-- form start -->
                <form method="post" action="">
                    @csrf
                    @include('Dashboard.users.form')
                    <!-- /.card-body -->

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">{{ trans('admin.Add') }}</button>
                    </div>
                </form>
            </div>
            <!-- /.card -->
            </div>
        </div><!-- /.container-fluid -->
    </section>
@endsection
@section('script')
<script>
    function toggleSalaryFields(paymentMethod) {
        const salaryLabel = document.getElementById('salaryLabel');
        
        if (paymentMethod.value === 'weekly') {
            console.log('weekly');
            salaryLabel.textContent = '{{ trans("admin.Weekly Salary") }}';
        } else if (paymentMethod.value === 'daily') {
            console.log('daily');
            salaryLabel.textContent = '{{ trans("admin.Daily Salary") }}';
        } else if (paymentMethod.value === 'monthly') {
            console.log('monthly');
            salaryLabel.textContent = '{{ trans("admin.Salary") }}';
        }
    }
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
                let searchField = document.querySelector('.select2-container .select2-search__field');
                if (searchField) {
                    searchField.focus();
                }
            }, 0);
        });
    }

    // Setup autofocus for dropdowns in the form
    setupSelect2Autofocus('#branch_id', 'اختر الفرع');
    setupSelect2Autofocus('#branch_ids', 'اختر الفروع');
    setupSelect2Autofocus('#role_id', 'اختر الدور');
    
    // If HR module is enabled, add payment method dropdown
    @if($settings->hr_module)
    setupSelect2Autofocus('#payment_method', 'اختر طريقة الحساب');
    @endif
});
</script>
@endsection

