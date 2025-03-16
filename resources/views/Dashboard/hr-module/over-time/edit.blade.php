
<form action="{{ route('dashboard.hr.overtime.update', $overtime->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="form-group">
        <label for="hours">{{ trans('admin.hours') }}</label>
        <input type="number" step="0.01" min="0" value="{{ $overtime->hours }}" id="hours" name="hours" class="form-control" required>
    </div>
    <div class="form-group">
        <label for="notes">{{ trans('admin.notes') }}</label>
        <textarea id="notes" name="notes" class="form-control" rows="3">{{ $overtime->notes }}</textarea>
    </div>
    <button type="submit" class="btn btn-primary">{{ trans('admin.update') }}</button>
</form>
