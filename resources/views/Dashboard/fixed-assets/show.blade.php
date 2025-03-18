<div class="row">
    <div class="col-lg-12">
        <div class="row">
            <div class="col-lg-3">
                @include('components.form.input', [
                    'class' => 'form-control',
                    'name' => "name",
                    'label' => trans('admin.name'),
                    'value' => $fixedAsset->name,
                    'attribute' => 'required disabled',
                ])
            </div>
            <div class="col-lg-3">
                @include('components.form.input', [
                    'class' => 'form-control',
                    'name' => "branch",
                    'label' => trans('admin.branch'),
                    'value' => $fixedAsset->branch?->name,
                    'attribute' => 'required disabled',
                ])
            </div>
        </div>
    </div>

    <div class="col-lg-12">
        <table id="example1" class="table table-bordered table-striped responsive">
            <thead>
            <tr>
                <th>{{ trans('admin.Price') }}</th>
                <th>{{ trans('admin.Status') }}</th>
                <th>{{ trans('admin.Note') }}</th>
                <th>{{ trans('admin.Created by') }}</th>
                <th>{{ trans('admin.Created at') }}</th>
            </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{$fixedAsset->price}}</td>
                    <td>{{ trans('admin.' . $fixedAsset->status) }}</td>
                    <td>{{$fixedAsset->note ?? trans('admin.No note') }}</td>
                    <td>{{$fixedAsset->createdBy?->name}}</td>
                    <td>{{\Carbon\Carbon::parse($fixedAsset->created_at)->format('d-m-Y h:i')}}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
