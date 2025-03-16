@extends('layouts.admin')

@section('title', trans('admin.sell-return'))


@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
            <h1 class="m-0">{{ trans('admin.sell-return') }}</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{route('dashboard.home')}}">{{ trans('admin.Home') }}</a> / {{ trans('admin.sell-return') }}</li>
            </ol>
            </div><!-- /.col -->
        </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
    @include('Dashboard.sell-return.filter')
    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="card">
          <div class="card-header">
          </div>
          <!-- /.card-header -->
          <div class="card-body">
            <table class="table table-bordered table-striped data-table responsive">
              <thead>
              <tr>
                <th>{{ trans('admin.parent_sell_ref_no') }}</th>
                <th>{{ trans('admin.sell_return_ref_no') }}</th>
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
          "url": "{{ route('dashboard.sells.sell-return.index') }}",
          "data": function ( d ) {
            d.branch_id = $('#branch_id').val();
            d.contact_id = $('#contact_id').val();
            d.created_by = $('#created_by').val();
            d.from_date = $('#from_date').val();
            d.to_date = $('#to_date').val();
          }
        },
        columnDefs: [{
                    targets: 1,
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('fire-popup')
                            .attr('data-target', '#modal-default-big')
                            .attr('data-toggle', 'modal')
                            .attr('data-url', rowData.route)
                            .attr('style', 'cursor: pointer');
                    }
                },
                {
                    targets: 2,
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('fire-popup')
                            .attr('data-target', '#modal-default-big')
                            .attr('data-toggle', 'modal')
                            .attr('data-url', rowData.route)
                            .attr('style', 'cursor: pointer');
                    }
                },
                {
                    targets: 3,
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('fire-popup')
                            .attr('data-target', '#modal-default-big')
                            .attr('data-toggle', 'modal')
                            .attr('data-url', rowData.route)
                            .attr('style', 'cursor: pointer');
                    }
                },
                {
                    targets: 4,
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('fire-popup')
                            .attr('data-target', '#modal-default-big')
                            .attr('data-toggle', 'modal')
                            .attr('data-url', rowData.route)
                            .attr('style', 'cursor: pointer');
                    }
                },
                {
                    targets: 5,
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('fire-popup')
                            .attr('data-target', '#modal-default-big')
                            .attr('data-toggle', 'modal')
                            .attr('data-url', rowData.route)
                            .attr('style', 'cursor: pointer');
                    }
                },
                {
                    targets: 6,
                    createdCell: function(td, cellData, rowData, row, col) {
                        $(td).addClass('fire-popup')
                            .attr('data-target', '#modal-default-big')
                            .attr('data-toggle', 'modal')
                            .attr('data-url', rowData.route)
                            .attr('style', 'cursor: pointer');
                    }
                },
            ],
        columns: [
            {data: 'parent_sell_ref_no', name: 'parent_sell_ref_no'},
            {data: 'sell_return_ref_no', name: 'sell_return_ref_no'},
            {data: 'branch', name: 'branch'},
            {data: 'total', name: 'total'},
            {data: 'contact', name: 'contact'},
            {data: 'phone', name: 'phone'},
            {data: 'created_at', name: 'created_at',render: function(data, type, row, meta){
                return moment(data).format('YYYY-MM-DD');
            }},
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

  $(document).on('change', '#branch_id, #contact_id, #created_by, #from_date, #to_date', function() {
    table.ajax.reload();
  });
</script>
@endsection
