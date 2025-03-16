@extends('layouts.admin')

@section('title', trans('admin.attendance_management'))

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">{{ trans('admin.attendance_management') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ trans('admin.Home') }}</a></li>
                        <li class="breadcrumb-item active">{{ trans('admin.attendance_management') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ trans('admin.attendance_list') }}</h3>
                    <div class="card-tools">
                        <a href="#" id="selected-users-attendance" class="btn btn-primary btn-sm mr-3">
                            <i class="fas fa-plus"></i> {{ trans('admin.Add Attendance') }}
                        </a>
                        <a href="{{ route('dashboard.hr.incentive.index') }}" class="btn btn-success btn-sm mr-1">
                            <i class="fas fa-plus"></i> {{ trans('admin.Incentive') }}
                        </a>
                        <a href="{{ route('dashboard.hr.discount.index') }}" class="btn btn-danger btn-sm mr-1">
                            <i class="fas fa-plus"></i> {{ trans('admin.Discount') }}
                        </a>
                        <a href="{{ route('dashboard.hr.overtime.index') }}" class="btn btn-warning btn-sm mr-1">
                            <i class="fas fa-plus"></i> {{ trans('admin.Overtime Hours') }}
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    {{-- Filters --}}
                    <form action="#" method="GET" class="mb-3">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="branch_id">{{ trans('admin.Branch') }}</label>
                                <select name="branch_id" class="form-control">
                                    <option value="">{{ trans('admin.All Branches') }}</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="date_from">{{ trans('admin.Date From') }}</label>
                                <input type="date" name="date_from" class="form-control"
                                    value="{{ request('date_from', date('Y-m-d')) }}">
                            </div>
                            <div class="col-md-3">
                                <label for="date_to">{{ trans('admin.Date To') }}</label>
                                <input type="date" name="date_to" class="form-control"
                                    value="{{ request('date_to', date('Y-m-d')) }}">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary mt-4">{{ trans('admin.Filter') }}</button>
                                <a href="{{ route('dashboard.hr.index') }}"
                                    class="btn btn-danger mt-4">{{ trans('admin.Clear') }}</a>
                            </div>
                        </div>
                    </form>

                    {{-- Attendance Table --}}
                    {{-- Attendance Table --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped w-100" id="attendance-table">
                            <thead>
                                <tr>
                                    <th class="w-4">
                                        <input type="checkbox" id="select-all-users">
                                    </th>
                                    <th class="w-auto">{{ trans('admin.Name') }}</th>
                                    <th class="w-auto">{{ trans('admin.Branch') }}</th>
                                    <th class="w-16">{{ trans('admin.Total Days') }}</th>
                                    <th class="w-16">{{ trans('admin.Present') }}</th>
                                    <th class="w-16">{{ trans('admin.Absent') }}</th>
                                    <th class="w-16">{{ trans('admin.Half-day') }}</th>
                                    <th class="w-16">{{ trans('admin.Late Hours') }}</th>
                                    <th class="w-20">{{ trans('admin.Total Hours') }}</th>
                                    <th class="w-20">{{ trans('admin.Overtime Hours') }}</th>
                                    <th class="w-16">{{ trans('admin.Incentives') }}</th>
                                    <th class="w-16">{{ trans('admin.Discounts') }}</th>
                                    <th class="w-20">{{ trans('admin.Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                    @php
                                        $attendance = $attendances->get($user->id);
                                    @endphp
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="selected_users[]" value="{{ $user->id }}"
                                                class="user-checkbox">
                                        </td>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->mainBranch->name ?? trans('admin.No Branch') }}</td>
                                        <td class="text-center">{{ $attendance->total_days ?? 0 }}</td>
                                        <td class="text-center">{{ $attendance->total_present ?? 0 }}</td>
                                        <td class="text-center">{{ $attendance->total_absent ?? 0 }}</td>
                                        <td class="text-center">{{ $attendance->total_half_day ?? 0 }}</td>
                                        <td class="text-center">{{ $attendance->total_late_time ?? 0 }}</td>
                                        <td class="text-center">
                                            @if (isset($attendance->total_hours))
                                                {{ floor($attendance->total_hours) }}:{{ sprintf('%02d', ($attendance->total_hours - floor($attendance->total_hours)) * 60) }}
                                            @else
                                                00:00
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $user->overtimes->sum('hours') ?? 0 }}</td>
                                        <td class="text-center">{{ $user->incentives->sum('amount') ?? 0 }}</td>
                                        <td class="text-center">{{ $user->discounts->sum('amount') ?? 0 }}</td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="#salaryModal-{{ $user->id }}" class="btn btn-sm btn-info mr-1"
                                                    title="show Salary" data-toggle="modal">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @php
                                                    $salaryObject = \App\Models\UserAttendance::where(
                                                        'user_id',
                                                        $user->id,
                                                    )->first();
                                                @endphp
                                                <a href="{{ route('dashboard.expenses.create', ['user_id' => $user->id, 'salary' => $salaryObject ? $salaryObject->calculateSalary($user) : 0, 'branch_id' => $user->mainBranch->id]) }}"
                                                    class="btn btn-sm btn-primary mr-1" title="Add Salary"><i
                                                        class="fas fa-money-check-alt"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                    
                                @empty
                                    <tr>
                                        <td colspan="13" class="text-center">{{ trans('admin.No data found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('script')
    <script>
        $(document).ready(function() {
            // DataTable initialization

            $('#select-all-users').change(function() {
                $('.user-checkbox').prop('checked', $(this).prop('checked'));
            });

            $('#selected-users-attendance').click(function(e) {
                e.preventDefault();

                var selectedUsers = $('.user-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();

                if (selectedUsers.length === 0) {
                    window.location.href = "{{ route('dashboard.hr.attendance.create') }}";
                    return;
                }
                window.location.href = "{{ route('dashboard.hr.attendance.create') }}?users=" +
                    selectedUsers.join(',');
            });
        });
    </script>
@endsection
