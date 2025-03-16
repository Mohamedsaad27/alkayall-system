@extends('layouts.admin')

@section('title', trans('admin.purchase-return'))


@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">{{ trans('admin.purchase-return') }}</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ trans('admin.Home') }}</a> /
                            {{ trans('admin.purchase-return') }}</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
    @include('Dashboard.purchase-return.filter')
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">

            <div class="card">
                <div class="card-header">
                </div>
                <!-- /.card-header -->
                <div class="card-body" id="print-section" style="max-width: 100%; overflow-x: auto;">
                    <table class=" table table-bordered table-striped data-table responsive">
                        <thead>
                            <tr>
                                <th>{{ trans('admin.parent_purchase_ref_no') }}</th>
                                <th>{{ trans('admin.purchase_return_ref_no') }}</th>
                                <th>{{ trans('admin.branch') }}</th>
                                <th>{{ trans('admin.total') }}</th>
                                <th>{{ trans('admin.contact') }}</th>
                                <th>{{ trans('admin.phone') }}</th>
                                <th>{{ trans('admin.Created at') }}</th>
                                <th>{{ trans('admin.Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
                <!-- /.card-body -->
            </div>
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
@endsection

@section('script')
    <script type="text/javascript">
        var table = $('.data-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                "url": "{{ route('dashboard.purchases.purchase-return.index') }}",
                "data": function(d) {
                    d.branch_id = $('#branch_id').val();
                    d.contact_id = $('#contact_id').val();
                    d.created_by = $('#created_by').val();
                    d.from_date = $('#from_date').val();
                    d.to_date = $('#to_date').val();
                }
            },
            createdRow: function(row, data, dataIndex) {
                $(row).children('td:not(:last-child)').addClass('fire-popup')
                    .attr('data-target', '#modal-default-big')
                    .attr('data-toggle', 'modal')
                    .attr('data-url', data.route)
                    .css('cursor', 'pointer');
            },
            columns: [{
                    data: 'parent_purchase_ref_no',
                    name: 'parent_purchase_ref_no'
                },
                {
                    data: 'purchase_return_ref_no',
                    name: 'purchase_return_ref_no'
                },
                {
                    data: 'branch',
                    name: 'branch'
                },
                {
                    data: 'total',
                    name: 'total'
                },
                {
                    data: 'contact',
                    name: 'contact'
                },
                {
                    data: 'phone',
                    name: 'phone'
                },
                {
                    data: 'created_at',
                    name: 'created_at',
                    render: function(data) {
                        return moment(data).format('YYYY-MM-DD');
                    }
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                },
            ],
            dom: 'lBfrtip',
            buttons: [{
                    extend: 'copy',
                    exportOptions: {
                        search: 'none',
                        columns: ':visible'
                    }
                },
                {
                    extend: 'excel',
                    exportOptions: {
                        search: 'none',
                        columns: ':visible'
                    }
                },
                {
                    extend: 'csv',
                    exportOptions: {
                        search: 'none',
                        columns: ':visible'
                    }
                },
                {
                    extend: 'pdf',
                    exportOptions: {
                        search: 'none',
                        columns: ':visible'
                    }
                },
                {
                    extend: 'print',
                    exportOptions: {
                        search: 'none',
                        columns: ':visible'
                    }
                },
                {
                    extend: 'colvis',
                    exportOptions: {
                        search: 'none',
                        columns: ':visible'
                    }
                },
            ],
            lengthMenu: [
                [10, 25, 50, -1],
                [10, 25, 50, 'All']
            ]
        });

        $(document).on('change', '#branch_id, #contact_id, #created_by, #from_date, #to_date', function() {
            table.ajax.reload();
        });
    </script>
    <script>
        // Updated print invoice JavaScript handler
        $(document).ready(function() {
            $(document).on('click', '.print-invoice', function(e) {
                e.preventDefault();

                const route = $(this).attr('href');
                const printWindow = window.open(route, '_blank', 'width=1000,height=600');

                if (!printWindow) {
                    alert('Please allow popup windows for printing');
                    return;
                }

                printWindow.onload = function() {
                    try {
                        printWindow.focus();
                        printWindow.print();
                        setTimeout(function() {
                            printWindow.close();
                        }, 1000);
                    } catch (e) {
                        console.error('Print failed:', e);
                        alert('Printing failed. Please try again.');
                    }
                };

                // Fallback if onload doesn't trigger
                setTimeout(function() {
                    if (printWindow && !printWindow.closed) {
                        try {
                            printWindow.focus();
                            printWindow.print();
                        } catch (e) {
                            console.error('Print fallback failed:', e);
                        }
                    }
                }, 2000);
            });
        });
    </script>
@endsection
