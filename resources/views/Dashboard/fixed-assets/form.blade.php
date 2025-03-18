<form method="post" action="{{ isset($fixedAsset) ? route('dashboard.fixed-assets.update', $fixedAsset->id) : route('dashboard.fixed-assets.store') }}" class="form">
    @csrf
    @if(isset($fixedAsset))
        @method('PUT')
    @endif

    <div class="card-body">
        <div class="row">
            <!-- Branch -->
            <div class="col-lg-4">
                <div class="form-group">
                    <label for="branch_id">{{ trans('admin.Select Branch') }}</label>
                    <select name="branch_id" id="branch_id" class="form-control select2">
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}"
                                {{ (isset($fixedAsset) && $fixedAsset->branch_id == $branch->id) ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('branch_id')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- Asset Name -->
            <div class="col-lg-4">
                <div class="form-group">
                    <label for="name">{{ trans('admin.Asset Name') }}</label>
                    <input type="text" name="name" id="name" class="form-control"
                        value="{{ isset($fixedAsset) ? $fixedAsset->name : old('name') }}" required>
                    @error('name')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- Price -->
            <div class="col-lg-4">
                <div class="form-group">
                    <label for="price">{{ trans('admin.Price') }}</label>
                    <input type="number" step="0.01" name="price" id="price" class="form-control"
                        value="{{ isset($fixedAsset) ? $fixedAsset->price : old('price') }}" required>
                    @error('price')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Created By -->
            <div class="col-lg-6">
                <div class="form-group">
                    <label for="created_by">{{ trans('admin.Created By') }}</label>
                    <select name="created_by" id="created_by" class="form-control select2">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}"
                                {{ (isset($fixedAsset) && $fixedAsset->created_by == $user->id) ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('created_by')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- Status -->
            <div class="col-lg-6">
                <div class="form-group">
                    <label for="status">{{ trans('admin.Status') }}</label>
                    <select name="status" id="status" class="form-control">
                        <option value="active" {{ (isset($fixedAsset) && $fixedAsset->status == 'active') ? 'selected' : '' }}>
                            {{ trans('admin.Active') }}
                        </option>
                        <option value="inactive" {{ (isset($fixedAsset) && $fixedAsset->status == 'inactive') ? 'selected' : '' }}>
                            {{ trans('admin.Inactive') }}
                        </option>
                        <option value="sold" {{ (isset($fixedAsset) && $fixedAsset->status == 'sold') ? 'selected' : '' }}>
                            {{ trans('admin.sold') }}
                        </option>
                    </select>
                    @error('status')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- Note -->
        </div>
        <div class="row">
            <div class="col-12">
                <div class="form-group">
                    <label for="note">{{ trans('admin.Note') }}</label>
                    <textarea name="note" id="note" class="form-control" rows="3">{{ isset($fixedAsset) ? $fixedAsset->note : old('note') }}</textarea>
                    @error('note')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card-footer">
        <button type="submit" class="btn btn-primary">
            {{ isset($fixedAsset) ? trans('admin.update') : trans('admin.Create') }}
        </button>
        <a href="{{ route('dashboard.fixed-assets.index') }}" class="btn btn-secondary">
            {{ trans('admin.cancel') }}
        </a>
    </div>
</form>
