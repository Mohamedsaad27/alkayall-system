@extends('layouts.admin')

@section('title', trans('admin.contacts'))

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
            <h1 class="m-0">{{ trans('admin.contacts') }}</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{route('dashboard.home')}}">{{ trans('admin.Home') }}</a> / <a href="{{route('dashboard.contacts.index')}}">{{ trans('admin.contacts') }}</a> / {{ trans('admin.Create') }}</li>
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
                    @include('Dashboard.contacts.form')
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
    // Call toggleFields function initially to set correct visibility
    toggleFields($('#contactType').val());

    // Event listener for type change
    $('#contactType').on('change', function() {
        let selectedType = $(this).val();
        toggleFields(selectedType);
    });

    // Function to toggle the visibility of fields
    function toggleFields(selectedType) {
        if (selectedType === 'customer') {
            $('#creditLimitField').show();
            $('#salesSegmentsField').show();
            $('#activity_type_id').show();
        } else if (selectedType === 'supplier') {
            $('#creditLimitField').hide();
            $('#salesSegmentsField').hide();
        } else {
            // Hide fields if neither customer nor supplier is selected
            $('#creditLimitField').hide();
            $('#salesSegmentsField').hide();
        }
    }
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
                let searchField = document.querySelector('.select2-container .select2-search__field');
                if (searchField) {
                    searchField.focus();
                }
            }, 0);
        });
    }

    // Setup autofocus for contact type dropdown
    setupSelect2Autofocus('#contactType', 'اختر نوع العميل');

    // Setup autofocus for activity type dropdown
    setupSelect2Autofocus('#activity_type_id', 'اختر نوع النشاط');

    // You can add more dropdowns following the same pattern
    setupSelect2Autofocus('#governorate_id', 'اختر المحافظة');
    setupSelect2Autofocus('#cities_dropdown', 'اختر المدينة');
        setupSelect2Autofocus('select[name="sales_segment_id"]', 'اختر القطاع');
    });
</script>
@endsection
