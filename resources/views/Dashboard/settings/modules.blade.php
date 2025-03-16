@extends('layouts.admin')

@section('title', trans('admin.Modules-settings'))

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
            <h1 class="m-0">{{ trans('admin.Modules-settings') }}</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{route('dashboard.home')}}">{{ trans('admin.Home') }}</a> / {{ trans('admin.Modules-settings') }}</li>
            </ol>
            </div><!-- /.col -->
        </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
    
    <section class="content">
        <div class="container-fluid">
        <div class="row">
            <!-- left column -->
            <div class="col-md-12">
            <!-- general form elements -->
            <div class="card card-primary">
                <div class="card-header">
                <h3 class="card-title">{{ trans('admin.Modules-settings') }}</h3>
                </div>
                <!-- /.card-header -->
                <!-- form start -->
                <form method="post" action="{{route('dashboard.settings.updateModules')}}" >
                    @csrf
                    <div class="card-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="hidden" name="hr_module" value="0">
                                    <input type="checkbox" 
                                           class="custom-control-input" 
                                           id="hr_module" 
                                           name="hr_module" 
                                           value="1"
                                           @if(isset($setting) && $setting->hr_module) checked @endif>
                                    <label class="custom-control-label" for="hr_module">
                                        {{ trans('admin.HR Module') }}
                                    </label>
                                </div>
                                @error('hr_module')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="hidden" name="manufacturing_module" value="0">
                                    <input type="checkbox" 
                                           class="custom-control-input" 
                                           id="manufacturing_module" 
                                           name="manufacturing_module" 
                                           value="1"
                                           @if(isset($setting) && $setting->manufacturing_module) checked @endif>
                                    <label class="custom-control-label" for="manufacturing_module">
                                        {{ trans('admin.Manufacturing Module') }}
                                    </label>
                                </div>
                                @error('manufacturing_module')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">{{ trans('admin.Save') }}</button>
                    </div>
                </form>
            </div>
            <!-- /.card -->
            </div>
        </div><!-- /.container-fluid -->
    </section>
@endsection