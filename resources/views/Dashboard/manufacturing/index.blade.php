@extends('layouts.admin')

@section('title', trans('admin.manufacturing_recipes'))


@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">{{ trans('admin.manufacturing_recipes') }}</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ trans('admin.Home') }}</a> /
                            {{ trans('admin.manufacturing_recipes') }}</li>
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
                    @if (auth('user')->user()->has_permission('create-manufacturing-recipes'))
                        <a href="{{ route('dashboard.manufacturing.create') }}" type="button" class="btn btn-success">
                            <i class="fas fa-plus-circle"></i> {{ trans('admin.add_recipe') }}
                        </a>
                    @else
                        <a href="#" type="button" class="btn btn-secondary disabled">
                            <i class="fas fa-plus-circle"></i> {{ trans('admin.add_recipe') }}
                        </a>
                    @endif

                    @if (auth('user')->user()->has_permission('create-production'))
                        <a href="{{ route('dashboard.production.index') }}" type="button" class="btn btn-warning">
                            <i class="fas fa-cogs"></i> {{ trans('admin.add_production') }}
                        </a>
                    @else
                        <a href="#" type="button" class="btn btn-secondary disabled">
                            <i class="fas fa-cogs"></i> {{ trans('admin.add_production') }}
                        </a>
                    @endif

                    @if (auth('user')->user()->has_permission('read-manufacturing-reports'))
                        <a href="{{ route('dashboard.manufacturing.reports') }}" type="button" class="btn btn-primary">
                            <i class="fas fa-chart-bar"></i> {{ trans('admin.manufacturing_reports') }}
                        </a>
                    @else
                        <a href="#" type="button" class="btn btn-secondary disabled">
                            <i class="fas fa-chart-bar"></i> {{ trans('admin.manufacturing_reports') }}
                        </a>
                    @endif
                </div>

                <!-- /.card-header -->
                <div class="card-body">
                    <table class="table table-bordered table-striped data-table responsive">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ trans('admin.recipe_name') }}</th>
                                <th>{{ trans('admin.final_quantity') }}</th>
                                <th>{{ trans('admin.materials_cost') }}</th>
                                <th>{{ trans('admin.production_cost_value') }}</th>
                                <th>{{ trans('admin.total_cost') }}</th>
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
                "url": "{{ route('dashboard.manufacturing.index') }}",
                "data": function(d) {}
            },
            columns: [{
                    data: 'id',
                    name: 'id'
                },
                {
                    data: 'recipe',
                    name: 'recipe'
                },
                {
                    data: 'final_quantity',
                    name: 'final_quantity'
                },
                {
                    data: 'materials_cost',
                    name: 'materials_cost'
                },
                {
                    data: 'production_cost_value',
                    name: 'production_cost_value'
                },
                {
                    data: 'total_cost',
                    name: 'total_cost'
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

        $('[data-toggle="tooltip"]').tooltip();

    </script>
@endsection
