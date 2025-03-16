
<form action="{{ route('dashboard.hr.incentive.update', $incentive->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="form-group">
        <label for="amount">{{ trans('admin.amount') }}</label>
        <input type="number" step="0.01" min="0" value="{{ $incentive->amount }}" id="amount" name="amount" class="form-control" required>
    </div>
    <div class="form-group">
        <label for="notes">{{ trans('admin.notes') }}</label>
        <textarea id="notes" name="notes" class="form-control" rows="3">{{ $incentive->notes }}</textarea>
    </div>
    <button type="submit" class="btn btn-primary">{{ trans('admin.update') }}</button>
</form>
