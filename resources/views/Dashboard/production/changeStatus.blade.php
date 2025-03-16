<form action="{{ route('dashboard.production.changeStatusPost', $production->id) }}" method="post">
    @csrf
    <div class="card-body">
        <div class="form-group">
            <label for="status">{{ trans('admin.status') }}</label>
            <select name="status" id="status" class="form-control">
                <option disabled selected>اختر</option>
                <option value="1" {{ $production->is_ended ? 'selected' : '' }}>{{ trans('admin.ended') }}</option>
                <option value="0" {{ $production->is_ended ? '' : 'selected' }}>{{ trans('admin.not_ended') }}</option>

            </select>
        </div>
    </div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary">{{ trans('admin.Save') }}</button>
    </div>
</form>
