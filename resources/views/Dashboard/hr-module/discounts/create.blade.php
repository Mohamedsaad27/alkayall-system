@extends('layouts.admin')
@section('title', trans('admin.Add Discount'))

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">{{ trans('admin.add_discount') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ trans('admin.Home') }}</a></li>
                        <li class="breadcrumb-item active">{{ trans('admin.add_discount') }}</li>
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
                    <h3 class="card-title">{{ trans('admin.add_discount') }}</h3>
                </div>

                <form action="{{ route('dashboard.hr.discount.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <!-- Bulk Attendance Options -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>{{ trans('admin.discount_date') }}</label>
                                    <input type="date" disabled name="date" class="form-control"
                                        value="{{ now()->format('Y-m-d') }}">
                                </div>
                            </div>
                            <div class="col-md-4 align-self-end mb-3">
                                <button name="add_discount_for_all" type="button" id="apply-bulk-status"
                                    class="btn btn-primary" data-toggle="modal" data-target="#addDiscountModal">
                                    {{ trans('admin.add_discount_for_all_users') }}
                                </button>
                            </div>
                        </div>

                        <!-- User Attendance Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>{{ trans('admin.name') }}</th>
                                        <th>{{ trans('admin.amount') }}</th>
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
                                                    placeholder="{{ trans('admin.amount') }}"
                                                    name="amount[{{ $user->id }}]" class="form-control">
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
                        <button type="submit" class="btn btn-primary">{{ trans('admin.save_discount') }}</button>
                    </div>
                </form>

                <!-- Add Incentive Modal -->
                <!-- Add Discount Modal -->
                <div class="modal fade" id="addDiscountModal" tabindex="-1" role="dialog"
                    aria-labelledby="addDiscountModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addDiscountModalLabel">
                                    {{ trans('admin.add_discount_for_all_users') }}</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form action="{{ route('dashboard.hr.discount.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="add_discount_for_all" value="1">
                                    <div class="form-group">
                                        <label for="amount">{{ trans('admin.amount') }}</label>
                                        <input type="number" id="amount" name="amount" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="notes">{{ trans('admin.notes') }}</label>
                                        <textarea id="notes" name="notes" class="form-control" rows="3"></textarea>
                                    </div>
                                    <button type="submit"
                                        class="btn btn-primary">{{ trans('admin.save_discount') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
