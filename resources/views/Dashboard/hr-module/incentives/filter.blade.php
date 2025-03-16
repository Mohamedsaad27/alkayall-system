<section class="content">
    <div class="container-fluid">
      <div class="card collapsed-card">
        <div class="card-header">
          <h3 class="card-title">{{ trans('admin.filter') }}</h3>

          <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i>
            </button>
          </div>
          <!-- /.card-tools -->
        </div>
        <!-- /.card-header -->
        <div class="card-body">
            <div class="row">
                <div class="col-lg-4">
                    <?php
                        $collection = $users;
                    ?>
                    @include('components.form.select', [
                      'collection' => $collection,
                      'index' => 'id',
                      'select' => (Request()->user_id) ? Request()->user_id : old('user_id'),
                      'name' => 'user_id',
                      'label' => trans('admin.User'),
                      'class' => 'form-control select2 user_id',
                      'id' => '',
                      'display'   => 'name',
                      'firstDisabled' => false,
                  ])
                </div>
                <div class="col-lg-4">
                    <div class="form-group">
                        <label>{{ trans('admin.date_from') }}</label>
                        <input type="date" class="form-control" name="date_from" id="date_from" value="{{ Request()->date_from }}">
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="form-group">
                        <label>{{ trans('admin.date_to') }}</label>
                        <input type="date" class="form-control" name="date_to" id="date_to" value="{{ Request()->date_to }}">
                    </div>
                </div>
            </div>
        </div>
        <!-- /.card-body -->
      </div>
    </div>
  </section>