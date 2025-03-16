<div class="col-lg-12">
    <table id="example1" class="table table-bordered table-striped responsive">
        <thead>
        <tr>
            <th>{{ trans('admin.name') }}</th>
            <th>{{ trans('admin.Rate') }}</th>
            <th>{{ trans('admin.is_active') }}</th>
            <th>{{ trans('admin.Created at') }}</th>
            <th>{{ trans('admin.Created by') }}</th>
        </tr>
        </thead>
        <tbody>
                <tr>
                    <td>{{$taxRate->name}}</td>
                    <td>{{$taxRate->rate}}</td>
                    <td>{{$taxRate->is_active ? trans('admin.Active') : trans('admin.Inactive')}}</td>
                    <td>{{\Carbon\Carbon::parse($taxRate->created_at)->format('d-m-Y h:i')}}</td>
                    <td>{{$taxRate->createdBy->name}}</td>
                </tr>

        </tbody>
    </table>
</div>
