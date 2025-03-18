<section class="content">
    <div class="container-fluid">
        <div class="card collapsed-card">
            <div class="card-header">
                <h3 class="card-title">{{ trans('admin.filter') }}</h3>

                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
                </div>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
                <div class="col-lg-6">
                    <?php
                        $branchCollection = collect($branches)->pluck('name', 'id');
                    ?>
                    <div class="form-group">
                        <label>{{ trans('admin.branch') }}</label>
                        <select class="form-control select2 branch_id" name="branch_id" id="branch_id" style="width: 100%;">
                            <option value="" selected>{{ trans('admin.Select') }}</option>
                            @foreach ($branchCollection as $id => $name)
                                <option value="{{ $id }}" @if (Request()->branch_id == $id) selected @endif>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <!-- /.card-body -->
        </div>
    </div>
</section>
