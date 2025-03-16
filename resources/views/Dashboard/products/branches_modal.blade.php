@if($product->ProductBranchDetails->isNotEmpty())
    <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr>
                <th>{{ trans('admin.BranchName') }}</th>
                <th>{{ trans('admin.QtyAvailable') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($product->productBranchDetails as $detail)
                <tr>
                    <td>{{ $detail->Branch->name ?? trans('admin.BranchNotFound') }}</td>
                    <td>{{ $detail->qty_available }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <p>لا يوجد كميات لهذا المنتج في اي فرع</p>
@endif
