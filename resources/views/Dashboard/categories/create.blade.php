@extends('layouts.admin')

@section('title', trans('admin.Categories'))

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">{{ trans('admin.Categories') }}</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ trans('admin.Home') }}</a> / <a
                                href="{{ route('dashboard.categories.index') }}">{{ trans('admin.Categories') }}</a> /
                            {{ trans('admin.Create') }}</li>
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
                        <form method="post" action="" enctype="multipart/form-data">
                            @csrf
                            @include('Dashboard.categories.form')
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
            setupSelect2Autofocus('#parent_id', 'اختر القسم الرئيسي');
        });
    </script>
@endsection
