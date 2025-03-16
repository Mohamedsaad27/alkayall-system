@extends('layouts.admin')

@section('title', trans('admin.User Attendance Report'))

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">{{ trans('admin.User Attendance Report') }}</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ trans('admin.Home') }}</a> /
                            {{ trans('admin.User Attendance Report') }}</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
    @include('Dashboard.hr-module.filter')
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ trans('admin.User Attendance Report') }}</h3>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <table class="table table-bordered table-striped responsive data-table">
                        <thead>
                            <tr>
                                <th>{{ trans('admin.User') }}</th>
                                <th>{{ trans('admin.Branch') }}</th>
                                <th>{{ trans('admin.date') }}</th>
                                <th>{{ trans('admin.Clock In') }}</th>
                                <th>{{ trans('admin.Clock Out') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be populated by DataTables -->
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
        $(document).ready(function() {
            var table = $('.data-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('dashboard.hr.attendance.report') }}",
                    type: 'GET',
                    data: function(d) {
                        d.branch_id = $('#branch_id').val();
                    d.user_id = $('#user_id').val();
                    d.date_from = $('#date_from').val();
                    d.date_to = $('#date_to').val();
                    }
                },
                columns: [{
                        data: 'user',
                        name: 'user',
                        searchable: true
                    },
                    {
                        data: 'branch',
                        name: 'branch',
                        searchable: true
                    },
                    {
                        data: 'date',
                        name: 'date',
                        searchable: true
                    },
                    {
                        data: 'clock_in',
                        name: 'clock_in',
                        searchable: false
                    },
                    {
                        data: 'clock_out',
                        name: 'clock_out',
                        searchable: false
                    },
                ],
                dom: 'lBfrtip',
                buttons: [
                    'copy', 'excel', 'csv', 'pdf', 'print', 'colvis'
                ],
                lengthMenu: [
                    [10, 25, 50, -1],
                    [10, 25, 50, 'All']
                ]
            });
            $(document).on('change', '#branch_id, #user_id, #date_from, #date_to', function() {
                table.ajax.reload();
            });
        });
    </script>
@endsection
