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
              <div class="col-lg-6">
                  <?php
                      $collection = collect([["name" => "customer"],["name" => "supplier"]]);
                  ?>
                  @include('components.form.select', [
                    'collection' => $collection,
                    'index' => 'name',
                    'select' => (Request()->type) ? Request()->type : old('type'),
                    'name' => 'type',
                    'label' => trans('admin.type'),
                    'class' => 'form-control select2 type',
                    'id' => '',
                    'display'   => 'name',
                    'firstDisabled' => false,
                  ])
              </div>
              
          </div>
      </div>
      <!-- /.card-body -->
    </div>
  </div>
</section>