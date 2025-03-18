@extends('layouts.admin')

@section('title', trans('admin.Fixed Assets'))

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
            <h1 class="m-0">{{ trans('admin.Fixed Assets') }}</h1>
            </div>
            <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{route('dashboard.home')}}">{{ trans('admin.Home') }}</a> / {{ trans('admin.fixed_assets') }}</li>
            </ol>
            </div>
        </div>
        </div>
    </div>

    <!-- Start Filter -->
    @include('Dashboard.fixed-assets.filter')
    <!-- End Filter -->

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="card">
          <div class="card-header">
            @if (auth('user')->user()->has_permission('create-fixed-assets'))
              <a href="{{route('dashboard.fixed-assets.create')}}" class="btn btn-info">{{ trans('admin.Add') }}</a>
            @else
              <a href="#" class="btn btn-info disabled">{{ trans('admin.Add') }}</a>
            @endif
          </div>
          <div class="card-body">
            <!-- Add this right before the table -->
            <div class="row mb-3">
                <div class="col-md-3">
                <div class="card bg-info">
                    <div class="card-body">
                    <h5 class="card-title">{{ trans('admin.Total Fixed Assets Value') }}</h5>
                    <h3 class="card-text">{{ number_format($totalPrice, 2) }} {{ trans('admin.currency') }}</h3>
                    </div>
                </div>
                </div>
            </div>
            <table class="table table-bordered table-striped data-table responsive">
              <thead>
              <tr>
                <th>{{ trans('admin.Created at') }}</th>
                <th>{{ trans('admin.Asset Name') }}</th>
                <th>{{ trans('admin.Branch') }}</th>
                <th>{{ trans('admin.price') }}</th>
                <th>{{ trans('admin.status') }}</th>
                <th>{{ trans('admin.Created by') }}</th>
                <th>{{ trans('admin.Actions') }}</th>
              </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>
    </section>
@endsection

@section('script')
<script type="text/javascript">
   var table = $('.data-table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: "{{ route('dashboard.fixed-assets.index') }}",
        data: function (d) {
            d.branch_id = $('#branch_id').val();
        }
    },
    createdRow: function(row, data) {
        $(row).children('td:not(:last-child)').addClass('fire-popup')
            .attr('data-target', '#modal-default-big')
            .attr('data-toggle', 'modal')
            .attr('data-url', data.route)
            .css('cursor', 'pointer');
    },
    columns: [
        { data: 'created_at', name: 'created_at', render: function(data) { return moment(data).format('DD-MM-YYYY'); } },
        { data: 'name', name: 'name' },
        { data: 'branch', name: 'branch' },
        { data: 'price', name: 'price' },
        { data: 'status', name: 'status' },
        { data: 'created_by', name: 'created_by' },
        { data: 'action', name: 'action', orderable: false, searchable: false },
    ],
    dom: 'lBfrtip',
    buttons: [
        { extend: 'copy', exportOptions: { columns: ':visible' }},
        { extend: 'excel', exportOptions: { columns: ':visible' }},
        { extend: 'csv', exportOptions: { columns: ':visible' }},
        { extend: 'pdf', exportOptions: { columns: ':visible' }},
        { extend: 'print', exportOptions: { columns: ':visible' }},
        { extend: 'colvis', exportOptions: { columns: ':visible' }},
    ],
    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']]
});

$(document).on('change', '#branch_id', function() {
    table.ajax.reload();
});
</script>
@endsection
