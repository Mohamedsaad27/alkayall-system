@extends('layouts.admin')

@section('title', trans('admin.Expenses'))

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
            <h1 class="m-0">{{ trans('admin.Expenses') }}</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{route('dashboard.home')}}">{{ trans('admin.Home') }}</a> / <a href="{{route('dashboard.expense-categories.index')}}">{{ trans('admin.expense_categories') }}</a> / {{ trans('admin.Create') }}</li>
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
                <h3 class="card-title">{{ trans('admin.CreateExpense') }}</h3>
                </div>
                <!-- /.card-header -->
                <!-- form start -->
                <form method="post" action="">
                    @csrf
                    @include('Dashboard.expenses.form')
                    <!-- /.card-body -->

                    
                </form>
            </div>
            <!-- /.card -->
            </div>
        </div><!-- /.container-fluid -->
    </section>
@endsection
@section('script')
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
        setupSelect2Autofocus('#expense_category_id', 'اختر فئة المصروف');
        setupSelect2Autofocus('#account_id', 'اختر الحساب');
        setupSelect2Autofocus('#created_by', 'اختر المستخدم');
        setupSelect2Autofocus('#branch_id', 'اختر الفرع');
    });
</script>
@endsection
