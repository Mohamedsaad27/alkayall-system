{{-- resources/views/Dashboard/expenses/create.blade.php --}}
<form method="post" action="{{ isset($expense) ? route('dashboard.expenses.update', $expense->id) : route('dashboard.expenses.store') }}" class="form">
    @csrf
    <div class="card-body">
        <div class="row">
            <div class="col-lg-4">
                <div class="form-group">
                    <label for="expense_category_id">{{ trans('admin.select_expense_categories') }}</label>
                    <select name="expense_category_id" id="expense_category_id" class="form-control select2" {{ isset($defaultValues['expense_category_id']) ? 'disabled' : '' }}>
                        @foreach($expenseCategories as $category)
                            <option value="{{ $category->id }}" 
                                {{ (isset($defaultValues['expense_category_id']) && $defaultValues['expense_category_id'] == $category->id) 
                                    || (isset($expense) && $expense->expense_category_id == $category->id) 
                                    ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @if(isset($defaultValues['expense_category_id']))
                        <input type="hidden" name="expense_category_id" value="{{ $defaultValues['expense_category_id'] }}">
                    @endif
                    @error('expense_category_id')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="col-lg-4">
                <div class="form-group">
                    <label for="account_id">{{ trans('admin.Select Account') }}</label>
                    <select name="account_id" id="account_id" class="form-control select2" {{ isset($defaultValues['account_id']) ? 'disabled' : '' }}>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}" 
                                {{ (isset($defaultValues['account_id']) && $defaultValues['account_id'] == $account->id) 
                                    || (isset($expense) && $expense->account_id == $account->id) 
                                    ? 'selected' : '' }}>
                                {{ $account->name }} ({{ $account->balance }})
                            </option>
                        @endforeach
                    </select>
                    @if(isset($defaultValues['account_id']))
                        <input type="hidden" name="account_id" value="{{ $defaultValues['account_id'] }}">
                    @endif
                    @error('account_id')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="col-lg-4">
                <div class="form-group">
                    <label for="created_by">{{ trans('admin.Created By') }}</label>
                    <select name="created_by" id="created_by" class="form-control select2" {{ isset($defaultValues['created_by']) ? 'disabled' : '' }}>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" 
                                {{ (isset($defaultValues['created_by']) && $defaultValues['created_by'] == $user->id) 
                                    || (isset($expense) && $expense->created_by == $user->id) 
                                    ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @if(isset($defaultValues['created_by']))
                        <input type="hidden" name="created_by" value="{{ $defaultValues['created_by'] }}">
                    @endif
                    @error('created_by')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="form-group">
                    <label for="branch_id">{{ trans('admin.Select Branch') }}</label>
                    <select name="branch_id" id="branch_id" class="form-control select2" {{ isset($defaultValues['branch_id']) ? 'disabled' : '' }}>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" 
                                {{ (isset($defaultValues['branch_id']) && $defaultValues['branch_id'] == $branch->id) 
                                    || (isset($expense) && $expense->branch_id == $branch->id) 
                                    ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                    @if(isset($defaultValues['branch_id']))
                        <input type="hidden" name="branch_id" value="{{ $defaultValues['branch_id'] }}">
                    @endif
                    @error('branch_id')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="col-lg-6">
                <div class="form-group">
                    <label for="amount">{{ trans('admin.amount') }}</label>
                    <input type="number" step="0.01" name="amount" id="amount" class="form-control" 
                        value="{{ isset($defaultValues['amount']) ? $defaultValues['amount'] : (isset($expense) ? $expense->amount : old('amount')) }}" 
                    required>
                    @error('amount')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="form-group">
                    <label for="note">{{ trans('admin.note') }}</label>
                    <textarea name="note" id="note" class="form-control" rows="3">{{ isset($defaultValues['note']) ? $defaultValues['note'] : (isset($expense) ? $expense->note : old('note')) }}</textarea>
                    @error('note')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card-footer">
        <button type="submit" class="btn btn-primary">
            {{ isset($expense) ? trans('admin.update') : trans('admin.Create') }}
        </button>
        <a href="{{ route('dashboard.expenses.index') }}" class="btn btn-secondary">
            {{ trans('admin.cancel') }}
        </a>
    </div>
</form>