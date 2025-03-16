@extends('layouts.admin')

@section('title', trans('admin.tax-rates'))


@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
            <h1 class="m-0">{{ trans('admin.tax-rates') }}</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{route('dashboard.home')}}">{{ trans('admin.Home') }}</a> / {{ trans('admin.tax-rates') }}</li>
            </ol>
            </div><!-- /.col -->
        </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!--start filter-->
    <!--end filter-->

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="card">
          <div class="card-header">
            @if (auth('user')->user()->has_permission('create-tax-rates'))
              <a href="{{route('dashboard.settings.tax-rates.create')}}" type="button" class="btn btn-info">{{ trans('admin.Add') }}</a>
            @else
              <a href="#" type="button" class="btn btn-info disabled">{{ trans('admin.Add') }}</a>
            @endif
          </div>
          <!-- /.card-header -->
          <div class="card-body">
            <table class="table table-bordered table-striped data-table responsive">
              <thead>
              <tr>
                <th>#</th>
                <th>{{ trans('admin.Name') }}</th>
                <th>{{ trans('admin.Rate') }}</th>
                <th>{{ trans('admin.Status') }}</th>
                <th>{{ trans('admin.created_By') }}</th>
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
          "url": "{{ route('dashboard.settings.tax-rates.index') }}",
          "data": function ( d ) {
            d.role = $('#role').val();
          }
        },
        createdRow: function(row, data, dataIndex) {
            $(row).children('td:not(:last-child)').addClass('fire-popup')
                .attr('data-target', '#modal-default-big')
                .attr('data-toggle', 'modal')
                .attr('data-url', data.route)
                .css('cursor', 'pointer');
        },
        columns: [
            {data: 'id', name: 'id'},
            {data: 'name', name: 'name'},
            {data: 'rate', name: 'rate'},
            {data: 'is_active', name: 'is_active', render: function(data) {
                return data === 1 ? '<span class="badge bg-success">مفعل</span>' : '<span class="badge bg-danger">معطل</span>';
            }},
            {data: 'created_by', name: 'created_by'},
            {data: 'created_at', name: 'created_at'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        dom: 'lBfrtip',
        buttons: [
                    { extend: 'copy',  exportOptions: {search: 'none',columns: ':visible'}},
                    { extend: 'excel', exportOptions: {search: 'none',columns: ':visible'}},
                    { extend: 'csv',   exportOptions: {search: 'none',columns: ':visible'}},
                    { extend: 'pdf',   exportOptions: {search: 'none',columns: ':visible'}},
                    { extend: 'print', exportOptions: {search: 'none',columns: ':visible'}},
                    { extend: 'colvis', exportOptions: {search: 'none',columns: ':visible'}},
                ],
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']]
    });

  $(document).on('change', '#role', function() {
    table.ajax.reload();
  });
</script>
@endsection
