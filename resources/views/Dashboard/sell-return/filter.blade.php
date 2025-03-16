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
            <div class="col-lg-12">
                <?php
                    $branchescollection = collect($branches)->pluck('name', 'id');
                    $customerCollection = collect($customers)->pluck('name', 'id');
                    $usersCollection = collect($users)->pluck('name', 'id');
                ?>
                <div class="row">
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label>{{ trans('admin.branch') }}</label>
                            <select class="form-control select2 branch_id" name="branch_id" id="branch_id" style="width: 100%;">
                                <option value="" selected >{{ trans('admin.Select') }}</option>
                                @foreach ($branchescollection as $id => $name)
                                    <option value="{{ $id }}" @if (Request()->branch_id == $id) selected @endif>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label>{{ trans('admin.customer') }}</label>
                            <select class="form-control select2 customer_id" name="contact_id" id="contact_id" style="width: 100%;">
                                <option value="" selected >{{ trans('admin.Select') }}</option>
                                @foreach ($customerCollection as $id => $name)
                                    <option value="{{ $id }}" @if (Request()->contact_id == $id) selected @endif>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label>{{ trans('admin.Created by') }}</label>
                            <select class="form-control select2 user_id" name="created_by" id="created_by" style="width: 100%;">
                                <option value="" selected >{{ trans('admin.Select') }}</option>
                                @foreach ($usersCollection as $id => $name)
                                    <option value="{{ $id }}" @if (Request()->created_by == $id) selected @endif>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label>{{ trans('admin.date_from') }}</label>
                            <input type="date" class="form-control" name="from_date" id="from_date" value="{{ Request()->from_date }}">
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label>{{ trans('admin.date_to') }}</label>
                            <input type="date" class="form-control" name="to_date" id="to_date" value="{{ Request()->to_date }}">
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        <!-- /.card-body -->
      </div>
    </div>
  </section>