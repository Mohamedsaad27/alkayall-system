@extends('layouts.admin')
@section('title', trans('admin.Record Attendance'))

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">{{ trans('admin.user_attendance_record') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ trans('admin.Home') }}</a></li>
                        <li class="breadcrumb-item active">{{ trans('admin.user_attendance_record') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">{{ trans('admin.attendance_management') }}</h3>
                </div>

                <form action="{{ route('dashboard.hr.attendance.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <!-- Bulk Attendance Options -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ trans('admin.attendance_date') }}</label>
                                    <input type="date" disabled name="attendance_date" class="form-control"
                                        value="{{ now()->format('Y-m-d') }}">
                                </div>
                            </div>
                            <div class="col-md-4 align-self-end mb-3">
                                <button name="all_present" type="submit" id="apply-bulk-status" class="btn btn-primary">
                                    {{ trans('admin.all_present') }}
                                </button>
                            </div>
                        </div>

                        <!-- User Attendance Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="select-all-users">
                                        </th>
                                        <th>{{ trans('admin.name') }}</th>
                                        <th>{{ trans('admin.status') }}</th>
                                        <th>{{ trans('admin.clock_in') }}</th>
                                        <th>{{ trans('admin.clock_out') }}</th>
                                        <th>{{ trans('admin.notes') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($users as $user)
                                        <tr>
                                            <td>
                                                <input type="hidden" name="user_ids[]" value="{{ $user->id }}">
                                            </td>
                                            <td>{{ $user->name }}</td>
                                            <td>
                                                <select id="status-{{ $user->id }}" name="status[{{ $user->id }}]"
                                                    class="form-control status-dropdown">
                                                    <option value="present">{{ trans('admin.present') }}</option>
                                                    <option value="absent">{{ trans('admin.absent') }}</option>
                                                    <option value="late">{{ trans('admin.late') }}</option>
                                                    <option value="half-day">{{ trans('admin.half_day') }}</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="time" id="clock-in-{{ $user->id }}"
                                                    name="clock_in[{{ $user->id }}]" class="form-control clock-in">
                                            </td>
                                            <td>
                                                <input type="time" id="clock-out-{{ $user->id }}"
                                                    name="clock_out[{{ $user->id }}]" class="form-control clock-out">
                                            </td>
                                            <td>
                                                <textarea id="notes-{{ $user->id }}" name="notes[{{ $user->id }}]" class="form-control" rows="1"></textarea>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">{{ trans('admin.save_attendance') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

@section('script')
    <script>
        $(document).ready(function() {
            // Select/Deselect all checkboxes
            $('#select-all-users').change(function() {
                $('.user-checkbox').prop('checked', $(this).prop('checked'));
            });

            // Apply bulk status to selected users
            $('#apply-bulk-status').click(function() {
                var bulkStatus = $('select[name="bulk_status"]').val();

                $('.user-checkbox:checked').each(function() {
                    var row = $(this).closest('tr');
                    row.find('.status-dropdown').val(bulkStatus);
                });
            });

            $(document).ready(function() {
                // Function to toggle clock-in and clock-out fields
                function toggleClockFields(row, status) {
                    const clockInField = row.find('.clock-in');
                    const clockOutField = row.find('.clock-out');

                    if (status === 'present') {
                        clockInField.prop('disabled', false);
                        clockOutField.prop('disabled', false);
                    } else if (status === 'late') {
                        clockInField.prop('disabled', false);
                        clockOutField.prop('disabled', false);
                    } else if (status === 'half-day') {
                        clockInField.prop('disabled', false);
                        clockOutField.prop('disabled', false);
                    } else if (status === 'absent') {
                        clockInField.prop('disabled', true);
                        clockOutField.prop('disabled', true);
                    } else {
                        clockInField.prop('disabled', false);
                        clockOutField.prop('disabled', false);
                    }
                }

                // Trigger toggle on status change
                $('.status-dropdown').change(function() {
                    const row = $(this).closest('tr');
                    const status = $(this).val();
                    toggleClockFields(row, status);
                });

                // Initial check on page load
                $('.status-dropdown').each(function() {
                    const row = $(this).closest('tr');
                    const status = $(this).val();
                    toggleClockFields(row, status);
                });
            });

        });
    </script>
@endsection
