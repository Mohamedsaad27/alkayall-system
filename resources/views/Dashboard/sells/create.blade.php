@extends('layouts.blank')
@section('style')
    <style>
        /* Simple styling for results dropdown */
        #print-section {
            display: none;
        }

        @media print {
            body * {
                visibility: hidden;

            }

            #print-section * {
                visibility: visible;

            }

            #print-section {
                display: block;

            }
        }

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

        .result-list li:focus {
            background-color: #837a7a;
            outline: none;
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

@section('title', trans('admin.sells'))
@section('printsection')
    <div id="print-section">

    </div>
@endsection
@section('content')
    @php
        $is_edit = false;
        if (isset($sell)) {
            $is_edit = true;
        }

        $disabled = '';
        if ($is_edit) {
            $disabled = 'disabled';
        }
        $product_segments = [];
        // Log::info($sell);
        if (isset($sell) && $sell->contact->salesSegment) {
            $product_segments = $sell->contact->salesSegment->products()->pluck('products.id');
        }
    @endphp

    <style>
        .modal-dialog {
            max-width: 1000px;
        }
    </style>
    <!-- form start -->
    <form method="post" action="">
        @csrf
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-1">
                    <div class="col-sm-12 d-flex align-items-center justify-content-end">

                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ trans('admin.Home') }}</a>
                                / <a href="{{ route('dashboard.sells.index') }}">{{ trans('admin.sells') }}</a> /
                                {{ trans('admin.Create') }}</li>
                        </ol>
                    </div><!-- /.col -->
                </div>
                <div class="row mb-2">
                    <div class="col-sm-4">
                        <div class="col-lg-12">
                            @include('components.form.select', [
                                'collection' => $branches,
                                'id' => 'branch_id',
                                'index' => 'id',
                                'select' =>
                                    $branches->first()->id ??
                                    (isset($sell) ? $sell->branch_id : auth()->user()->branch_id),
                                'name' => 'branch_id',
                                'label' => trans('admin.branch') . ':',
                                'class' => 'form-control select2 branch_id w-100',
                                'attribute' => 'required ' . $disabled,
                                'value' => $branches->first()->id,
                            ])

                        </div>
                    </div>
                    <div class="col-sm-4">
                        <label for="contact_id">{{ trans('admin.contact') }}</label>
                        <div class="d-flex">
                            <select name="contact_id" id="contact_id" class="form-control select2 contact_id" required>
                                <option value="" selected>{{ trans('admin.Select') }}</option>
                                @foreach ($contacts as $contact)
                                    <option value="{{ $contact->id }}" @if ((isset($sell) && $sell->contact_id == $contact->id) || $contact->name === 'نقدي') selected @endif>
                                        {{ $contact->name }} ({{ $contact->balance }})
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-primary ml-2" data-toggle="modal"
                                data-target="#addContactModal">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <!-- Add Contact Modal -->
                    <div class="modal fade" id="addContactModal" tabindex="-1" role="dialog"
                        aria-labelledby="addContactModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addContactModalLabel">{{ trans('admin.Add New Customer') }}
                                    </h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <form id="addContactForm">
                                    @csrf
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="name">{{ trans('admin.Name') }}</label>
                                                    <input type="text" class="form-control" id="name" name="name"
                                                        required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="phone">{{ trans('admin.Phone') }}</label>
                                                    <input type="text" class="form-control" id="phone"
                                                        name="phone">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="address">{{ trans('admin.Address') }}</label>
                                                    <textarea class="form-control" id="address" name="address"></textarea>
                                                    <span class='text-danger'>{{ trans('admin.address-note') }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="contactType">{{ trans('admin.Type') }}</label>
                                                    <select class="form-control select2" id="contactType" name="type" disabled>
                                                        <option value="customer" selected >{{ trans('admin.customer') }}
                                                        </option>
                                                        <option value="supplier">{{ trans('admin.supplier') }}</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label
                                                        for="opening_balance">{{ trans('admin.Opening Balance') }}</label>
                                                    <input type="number" step="0.01" class="form-control"
                                                        id="opening_balance" name="opening_balance">
                                                    <span
                                                        class='text-danger'>{{ trans('admin.opening-balance-note') }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="governorate_id">{{ trans('admin.Governorate') }}</label>
                                                    <select class="form-control select2" id="governorate_id"
                                                        name="governorate_id">
                                                        @foreach ($governorates ?? [] as $governorate)
                                                            <option value="{{ $governorate->id }}">
                                                                {{ $governorate->governorate_name_ar }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="city_id">{{ trans('admin.City') }}</label>
                                                    <select class="form-control select2" id="cities_dropdown"
                                                        name="city_id">
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="customerFields">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label
                                                            for="credit_limit">{{ trans('admin.Credit Limit') }}</label>
                                                        <input type="number" step="0.01" class="form-control"
                                                            id="credit_limit" name="credit_limit">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label
                                                            for="sales_segment_id">{{ trans('admin.Sales Segment') }}</label>
                                                        <select class="form-control select2" id="sales_segment_id"
                                                            name="sales_segment_id">
                                                            @foreach ($salesSegments ?? [] as $segment)
                                                                <option value="{{ $segment->id }}">{{ $segment->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label
                                                            for="activity_type_id">{{ trans('admin.Activity Type') }}</label>
                                                        <select class="form-control select2" id="activity_type_id"
                                                            name="activity_type_id">
                                                            <option value="">
                                                                {{ trans('admin.Select Activity Type') }}</option>
                                                            @foreach ($activityTypes ?? [] as $type)
                                                                <option value="{{ $type->id }}">{{ $type->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-dismiss="modal">{{ trans('admin.Close') }}</button>
                                        <button type="submit" class="btn btn-primary">{{ trans('admin.Save') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4 d-flex align-items-center justify-content-end">

                        <button type="button" class="btn btn-success fire-popup ml-2" data-toggle="modal"
                            data-target="#getByBrand">{{ trans('admin.Add Bulck products') }}</button>

                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->

        <section class="content ">

            @include('Dashboard.sells.parts.AddBulckProductsPopUp')
            <div class="container-fluid ">
                <div class="row">
                    <!-- left column -->
                    <div class="col-md-12 ">
                        <!-- general form elements -->
                        <div class="card card-primary ">
                            <div class="card-header">

                            </div>
                            <!-- /.card-header -->

                            <div class="row px-5">

                                <div class="col-lg-12 py-2">
                                    <div>

                                        <input type="text" id="search" class="form-control"
                                            placeholder="ابحث عن المنتج ...." autocomplete="off" autofocus>
                                        <ul id="result-list" class="result-list list-group">
                                            <!-- Products will be appended here -->
                                        </ul>

                                    </div>
                                </div>


                                <div class="col-lg-12 my-3">
                                    @if ($errors->any())
                                        <div class="alert alert-danger m-2" role="alert">
                                            <ul>
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                    <table class="table table-bordered table-striped ">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>{{ trans('admin.name') }}</th>
                                                <th style="min-width: 120px;">{{ trans('admin.unit') }}</th>
                                                <th>{{ trans('admin.quantity') }}</th>
                                                <th>{{ trans('admin.available quantity') }}</th>
                                                <th>{{ trans('admin.unit_price') }}</th>
                                                <th>{{ trans('admin.total') }}</th>
                                                @if ($settings->display_warehouse)
                                                    <th>{{ trans('admin.warehouse') }}</th>
                                                @endif
                                                <th>{{ trans('admin.action') }}</th>
                                            </tr>
                                        </thead>

                                        <tbody class="sell_table">

                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="discount_type">{{ trans('admin.discount type') }}</label>
                                        <select class="form-control" id="discount_type" name="discount_type" required>
                                            <option value="percentage"
                                                {{ isset($sell) && $sell->discount_type == 'percentage' ? 'selected' : '' }}>
                                                {{ trans('admin.percentage') }}</option>
                                            <option value="fixed_price"
                                                {{ isset($sell) && $sell->discount_type == 'fixed_price' ? 'selected' : '' }}>
                                                {{ trans('admin.fixed amount') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="discount_value">{{ trans('admin.amount') }}</label>
                                        <input type="number" class="form-control" id="discount_value"
                                            name="discount_value" value="0" min="0" step="1" required>
                                    </div>
                                </div>
                                <div class="col-lg-6 mb-3">
                                    <label>{{ trans('admin.tax-rates') }}</label>
                                    <select multiple name="taxes[]" id="taxes" class="taxes form-control select2"
                                        style="width: 100%;">
                                        @foreach ($activeTaxes as $tax)
                                            <option value="{{ $tax['id'] }}" data-tax-rate="{{ $tax['rate'] }}">
                                                {{ $tax['name'] }} ({{ $tax['rate'] }}%)
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('taxes[]')
                                        <span style="color: red; margin: 20px;">
                                            {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                <div class="col-lg-12">
                                    <h5>الإجمالي النهائي: <span class="final_total">0.00</span></h5>
                                    <input type="hidden" name="final_total" id="final_total">
                                </div>
                            </div>
                            <!-- /.card-body -->
                            <div class="card-footer">
                                <button type="submit" class="btn btn-success button-send" name="sell_type"
                                    value="cash">{{ trans('admin.cash') }}</button>
                                <button type="submit" class="btn btn-info button-send credit_button"
                                    style="display: none;" name="sell_type"
                                    value="credit">{{ trans('admin.credit') }}</button>
                                <button type="button" class="btn btn-primary fire-popup" data-toggle="modal"
                                    data-target="#modal-default"
                                    data-url="{{ route('dashboard.sells.multiPay') }}">{{ trans('admin.multi_pay') }}</button>
                                <button type="submit" class="btn btn-dark draft" name="sell_type"
                                    value="draft">{{ trans('admin.draft') }}</button>
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
        // Initialize Select2 and focus on search when dropdown opens
        $(document).ready(function() {

            $('.select2').select2({
                placeholder: "{{ trans('admin.Select') }}", // Placeholder for the dropdown
                width: 'resolve' // Make sure it takes full width as specified
            }).on('select2:open', function(e) {
                // Focus on search box within Select2 when dropdown is opened
                let searchField = document.querySelector('.select2-container--open .select2-search__field');
                if (searchField) {
                    searchField.focus();
                }
            });
        });
    </script>
    {{-- X-CSRF-TOKEN --}}

    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
    @include('Dashboard.includes.product_row_ajax')
    {{-- old for printing invoice debend on session --}}
    {{-- <script>
        @if (Session::has('transaction'))
            let printUrl =
                @if (session('classic_printing'))
                    "{{ route('dashboard.sells.printInvoicePage', Session::get('transaction')->id) }}"
                @else
                    "{{ route('dashboard.sells.printThermalInvoice', Session::get('transaction')->id) }}"
                @endif ;

            $.ajax({
                url: printUrl,
                type: 'get',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    $('#print-section').html(response);
                    setTimeout(() => {
                        window.print();
                    }, 1000);

                    console.log("AJAX request successful:", response);
                },
                error: function(xhr, status, error) {
                    console.log("AJAX request failed:", error);
                }
            });
        @endif
    </script> --}}

    {{-- create transaction sell and print it  debend on ajax request  --}}
    <script>
        $(document).ready(function() {

            $(document).on('click', '.button-send', function(e) {
                e.preventDefault();

                data = {};

                data._token = "{{ csrf_token() }}";
                data.branch_id = $('#branch_id').val();
                var amount = $('#amount').val();
                data.amount = amount;


                if (!data.branch_id) {
                    toastr.error("اختر الفرع")
                    return;
                }
                data.contact_id = $('#contact_id').val();
                if (!data.contact_id) {
                    toastr.error("اختر العميل")
                    return;
                }
                data.discount_type = $('#discount_type').val();
                data.discount_value = $('#discount_value').val();
                data.final_total = $('#final_total').val();
                data.sell_type = $(this).val();
                data.taxes = $('select[name="taxes[]"]').val(); // Add this line


                var products = [];
                tableRr = $('.sell_table tr');
                // console.log(products);
                tableRr.each(function(tr) {
                    let quantityInput = $(this).find('td .quantity');
                    let unit_priceInput = $(this).find('td #unit_price');
                    let productId = $(this).find('#product_id');
                    let unitId = $(this).find('#unit_id');
                    let warehouse_id = $(this).find('#warehouse');
                    let main_unit_price = $(this).find('#main_unit_price');
                    let main_available_quantity = $(this).find('#main_available_quantity');
                    let unit_multipler = $(this).find('#unit_multipler');


                    let product = {};

                    product.quantity = quantityInput.val();
                    product.product_id = productId.val();
                    product.id = productId.val();
                    product.unit_price = unit_priceInput.val();
                    product.unit_id = unitId.val();
                    product.main_unit_price = main_available_quantity.val();
                    product.main_available_quantity = main_available_quantity.val();
                    product.unit_multipler = unit_multipler.val();
                    product.warehouse_id = warehouse_id.val();
                    products.push(product)
                    console.log(product);
                    console.log(quantityInput.val());

                });

                if (products.length < 1) {
                    toastr.error("حدد المنتجات")
                    return;
                }

                data.products = products;

                $(this).prop('disabled', true);
                $.ajax({
                    url: "{{ route('dashboard.sells.store') }}",
                    type: 'POST',
                    data: data,

                    success: function(response) {
                        console.log('================================');
                        console.log(response);
                        $('.sell_table').empty();
                        var transactionId = response.transaction.id
                        var printUrl = "{{ route('dashboard.sells.printInvoicePage', ':id') }}"
                            .replace(':id', transactionId);
                        console.log(printUrl);
                        $.ajax({
                            url: printUrl,
                            type: 'get',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                $('#print-section').html(response);
                                setTimeout(() => {
                                    window.print();
                                }, 1000);

                                console.log("AJAX request successful:", response);
                            },
                            error: function(xhr, status, error) {
                                console.log("AJAX request failed:", error);
                            }
                        });
                        toastr.success("success")

                    },
                    error: function(xhr, status, error) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;


                            $.each(errors, function(key, value) {
                                toastr.error(value[
                                    0]);
                            });
                        } else {
                            toastr.error("An error occurred, please try again.");
                        }

                    }
                });

                setTimeout(() => {
                    $(this).prop('disabled', false);
                }, 2000);
                // $('#contact_id').val('');
                $('.final_total').text('0.00');
            })
            $('.my-popup').on('hidden.bs.modal', function() {
                $('.my-popup .modal-title').empty();
                $('.my-popup .modal-body').empty(); // $(this).empty();
            });

            $(document).on("keydown", function(event) {

                if (event.key === "Enter") {
                    event.preventDefault();

                    if ($('#result-list li:focus').is(':focus')) {
                        $("#result-list li:focus").click();
                    }

                    // If #search is not focused, focus it
                    if (!$('#search').is(':focus')) {
                        $('#search').focus();
                    }
                }

                if (event.key === "ArrowDown") {
                    event.preventDefault();

                    let current = $("#result-list li:focus");

                    if (current.length === 0) {
                        $("#result-list li").first().focus();
                    } else {
                        let next = current.next("li");
                        if (next.length) {
                            next.focus();
                        }
                    }
                }

                if (event.key === "ArrowUp") {
                    event.preventDefault();

                    let current = $("#result-list li:focus");

                    if (current.length === 0) {
                        $("#result-list li").last().focus();
                    } else {
                        let prev = current.prev("li");
                        if (prev.length) {
                            prev.focus();
                        }
                    }
                }
            });


        })
    </script>
    <script>
        $(document).ready(function() {
            setupSelect2Autofocus('#contact_id', 'اختر العميل');
            setupSelect2Autofocus('#branch_id', 'اختر الفرع');
        });
        $(document).ready(function() {
            


            $('#addContactModal').on('shown.bs.modal', function () {
        // Initialize all select2 elements within the modal
        $('.select2').select2({
            dropdownParent: $('#addContactModal'),
            width: '100%'
        });
        
        // Prevent modal from closing when clicking on select2 dropdown
        $(document).on('click', '.select2-container--open', function (e) {
            e.stopPropagation();
        });

            // Handle contact type change
            $('#contactType').on('change', function() {
                let selectedType = $(this).val();
                if (selectedType === 'customer') {
                    $('#customerFields').show();
                } else {
                    $('#customerFields').hide();
                }
            });

            // Handle governorate change
            $('#addContactModal').on('hidden.bs.modal', function() {
        $('#addContactForm')[0].reset();
        $('#addContactModal select').val('').trigger('change');
    });

    // Handle contact form submission
    $('#addContactModal').on('hidden.bs.modal', function() {
        $('#addContactForm')[0].reset();
        $('#addContactModal select').val('').trigger('change');
    });

    // Handle contact form submission
    $('#addContactForm').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true);
        
        $.ajax({
            url: route('dashboard.contacts.store'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    // Format balance for display
                    const balance = parseFloat(response.contact.balance || 0).toFixed(2);
                    
                    // Add new contact to select dropdown
                    const newOption = new Option(
                        `${response.contact.name} (${balance})`,
                        response.contact.id,
                        true,
                        true
                    );
                    $('#contact_id').append(newOption).trigger('change');

                    // Close modal and show success message
                    $('#addContactModal').modal('hide');
                    toastr.success(response.message);

                    // Update any related fields based on sales segment if needed
                    if (response.contact.sales_segment_id) {
                        // Add any additional logic for sales segment handling
                    }
                }
            },
            error: function(xhr) {
                submitBtn.prop('disabled', false);
                
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    Object.values(errors).forEach(function(error) {
                        toastr.error(error[0]);
                    });
                } else {
                    toastr.error('An error occurred while saving the contact. Please try again.');
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false);
            }
        });
    });
    </script>

    <script>
        $(document).ready(function() {
            $('#governorate_id').change(function() {
                var governorate_id = $(this).val(); // Get the selected governorate ID
                var citiesDropdown = $('#cities_dropdown'); // Get the cities dropdown element
                let appLocale = "{{ app()->getLocale() }}";
                // Clear the cities dropdown
                citiesDropdown.empty();
                citiesDropdown.append(
                '<option value="">{{ trans('admin.Select') }}</option>'); // Optional placeholder

                if (governorate_id) {
                    // Make AJAX request to fetch cities
                    $.ajax({
                        url: '{{ route('dashboard.branchs.getCitiesByGovernorate') }}',
                        method: 'GET',
                        data: {
                            governorate_id: governorate_id
                        },
                        success: function(data) {
                            console.log(data);
                            $.each(data, function(index, city) {
                                citiesDropdown.append('<option value="' + city.id +
                                    '">' + (appLocale === "ar" ? city.city_name_ar :
                                        city.city_name_en) + '</option>');
                            });

                            citiesDropdown.select2(); // Reinitialize Select2
                        },
                        error: function(xhr, status, error) {
                            console.error(xhr);
                            alert('An error occurred while fetching cities. Please try again.');
                        }
                    });
                }
            });
        });
    </script>
@endsection
