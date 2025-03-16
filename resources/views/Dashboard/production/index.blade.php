@extends('layouts.admin')

@section('title', trans('admin.production_lines'))


@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">{{ trans('admin.production_lines') }}</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ trans('admin.Home') }}</a> /
                            {{ trans('admin.production_lines') }}</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->



    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    @if (auth('user')->user()->has_permission('create-production'))
                        <a href="{{ route('dashboard.production.create') }}" type="button" class="btn btn-success">
                            <i class="fas fa-plus-circle"></i> {{ trans('admin.add_production_line') }}
                        </a>
                    @else
                        <a href="#" type="button" class="btn btn-secondary disabled">
                            <i class="fas fa-plus-circle"></i> {{ trans('admin.add_production_line') }}
                        </a>
                    @endif
                </div>

                <!-- /.card-header -->
                <div class="card-body">
                    <table class="table table-bordered table-striped data-table responsive">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ trans('admin.date') }}</th>
                                <th>{{ trans('admin.production_line_code') }}</th>
                                <th>{{ trans('admin.branch') }}</th>
                                <th>{{ trans('admin.product') }}</th>
                                <th>{{ trans('admin.production_quantity') }}</th>
                                <th>{{ trans('admin.production_cost_value') }}</th>
                                <th>{{ trans('admin.is_ended_status') }}</th>
                                <th class="text-center" width="100">{{ trans('admin.Actions') }}</th>
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
                "url": "{{ route('dashboard.production.index') }}",
                "data": function(d) {}
            },
            columns: [{
                    data: 'id',
                    name: 'id'
                },
                {
                    data: 'date',
                    name: 'date'
                },
                {
                    data: 'production_line_code',
                    name: 'production_line_code'
                },
                {
                    data: 'branch',
                    name: 'branch'
                },
                {
                    data: 'recipe',
                    name: 'recipe'
                },
                {
                    data: 'production_quantity',
                    name: 'production_quantity'
                },
                {
                    data: 'production_cost_value',
                    name: 'production_cost_value'
                },
                {
                    data: 'is_ended',
                    name: 'is_ended',
                    render: function(data) {
                        if (data == 1) {
                            return '<span class="badge badge-success">منتهية</span>';
                        } else {
                            return '<span class="badge badge-danger">غير منتهية</span>';
                        }
                    }
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                },
            ],
            order: [
                [0, 'desc']
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
        $(function () {
    $('[title]').tooltip();
});
        //   $(document).on('change', '#role', function() {
        //     table.ajax.reload();
        //   });
    </script>
@endsection
