@extends('layouts.admin')
@section('title', trans('admin.Add Overtime Hours'))

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">{{ trans('admin.Add Overtime Hours') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ trans('admin.Home') }}</a></li>
                        <li class="breadcrumb-item active">{{ trans('admin.Add Overtime Hours') }}</li>
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
                    <h3 class="card-title">{{ trans('admin.Add Overtime Hours') }}</h3>
                </div>

                <form action="{{ route('dashboard.hr.overtime.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <!-- Bulk Attendance Options -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ trans('admin.overtime_date') }}</label>
                                    <input type="date" disabled name="date" class="form-control"
                                        value="{{ now()->format('Y-m-d') }}">
                                </div>
                            </div>
                            <div class="col-md-4 align-self-end mb-3">
                                <button name="add_overtime_for_all" type="button" id="apply-bulk-status"
                                    class="btn btn-primary" data-toggle="modal" data-target="#addOvertimeModal">
                                    {{ trans('admin.add_overtime_for_all_users') }}
                                </button>
                            </div>
                        </div>

                        <!-- User Attendance Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>{{ trans('admin.name') }}</th>
                                        <th>{{ trans('admin.hours') }}</th>
                                        <th>{{ trans('admin.notes') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($users as $user)
                                        <tr>
                                            <input type="hidden" name="user_ids[]" value="{{ $user->id }}">
                                            <td>{{ $user->name }}</td>
                                            <td>
                                                <input type="number" id="amount-{{ $user->id }}"
                                                    placeholder="{{ trans('admin.hours') }}"
                                                    name="hours[{{ $user->id }}]" class="form-control">
                                                </select>
                                            </td>
                                            <td>
                                                <textarea id="notes-{{ $user->id }}" name="notes[{{ $user->id }}]" class="form-control" rows="1">{{ trans('admin.notes') }}</textarea>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">{{ trans('admin.save_overtime') }}</button>
                    </div>
                </form>

                <!-- Add Incentive Modal -->
                <!-- Add Overtime Modal -->
                <div class="modal fade" id="addOvertimeModal" tabindex="-1" role="dialog"
                    aria-labelledby="addOvertimeModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addOvertimeModalLabel">
                                    {{ trans('admin.add_overtime_for_all_users') }}</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form action="{{ route('dashboard.hr.overtime.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="add_overtime_for_all" value="1">
                                    <div class="form-group">
                                        <label for="hours">{{ trans('admin.hours') }}</label>
                                        <input type="number" id="hours" name="hours" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="notes">{{ trans('admin.notes') }}</label>
                                        <textarea id="notes" name="notes" class="form-control" rows="3"></textarea>
                                    </div>
                                    <button type="submit"
                                        class="btn btn-primary">{{ trans('admin.save_overtime') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
