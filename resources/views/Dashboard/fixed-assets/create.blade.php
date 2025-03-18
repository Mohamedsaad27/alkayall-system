@extends('layouts.admin')

@section('title', trans('admin.Fixed Assets'))

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
            <h1 class="m-0">{{ trans('admin.Fixed Assets') }}</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{route('dashboard.home')}}">{{ trans('admin.Home') }}</a> / <a href="{{route('dashboard.fixed-assets.index')}}">{{ trans('admin.fixed_assets') }}</a> / {{ trans('admin.Create') }}</li>
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
                <h3 class="card-title">{{ trans('admin.add_fixed_asset') }}</h3>
                </div>
                <!-- /.card-header -->
                <!-- form start -->
                <form method="post" action="{{ route('dashboard.fixed-assets.store') }}">
                    @csrf
                    @include('Dashboard.fixed-assets.form')
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
                setTimeout(function() {
                    let searchField = document.querySelector('.select2-container .select2-search__field');
                    if (searchField) {
                        searchField.focus();
                    }
                }, 0);
            });
        }

        // Setup autofocus for fixed asset user and branch dropdowns
        setupSelect2Autofocus('#created_by', 'اختر المستخدم');
        setupSelect2Autofocus('#branch_id', 'اختر الفرع');
    });
</script>
@endsection
