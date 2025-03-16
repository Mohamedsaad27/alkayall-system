@extends('layouts.admin')

@section('title', trans('admin.Incentives'))


@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">{{ trans('admin.Incentives') }}</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ trans('admin.Home') }}</a> /
                            {{ trans('admin.Incentives') }}</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!--start filter-->
    @include('Dashboard.hr-module.incentives.filter')
    <!--end filter-->

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    @if (auth('user')->user()->has_permission('create-incentives'))
                        <a href="{{ route('dashboard.hr.incentive.create') }}" type="button"
                            class="btn btn-info">{{ trans('admin.Add') }}</a>
                    @else
                        <a href="#" type="button" class="btn btn-info disabled">{{ trans('admin.Add') }}</a>
                    @endif
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <table class="table table-bordered table-striped data-table responsive">
                        <thead>
                            <tr>
                                <th>{{ trans('admin.User') }}</th>
                                <th>{{ trans('admin.amount') }}</th>
                                <th>{{ trans('admin.notes') }}</th>
                                <th>{{ trans('admin.Created_At') }}</th>
                                <th>{{ trans('admin.Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="1">{{ trans('admin.total') }}</th>
                                <th id="total-amount"></th>
                                <th colspan="3"></th>
                            </tr>
                        </tfoot>
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
                url: "{{ route('dashboard.hr.incentive.index') }}",
                data: function(d) {
                    d.user_id = $('.user_id').val();
                    d.date_from = $('.date_from').val();
                    d.date_to = $('.date_to').val();
                }
            },
            columns: [{
                    data: 'user',
                    name: 'user'
                },
                {
                    data: 'amount',
                    name: 'amount'
                },
                {
                    data: 'notes',
                    name: 'notes'
                },
                {
                    data: 'created_at',
                    name: 'created_at'
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
            ],
            footerCallback: function(row, data, start, end, display) {
                // Fetch total from server-side response
                var api = this.api();
                $.ajax({
                    url: "{{ route('dashboard.hr.incentive.index') }}",
                    data: {
                        user_id: $('.user_id').val(),
                        date_from: $('.date_from').val(),
                        date_to: $('.date_to').val()
                    },
                    success: function(response) {
                        // Update total in footer
                        $('#total-amount').html(response.total);
                    }
                });
            }
        });

        $(document).on('change', '.user_id, .date_from, .date_to', function() {
            table.ajax.reload();
        });
    </script>
@endsection
