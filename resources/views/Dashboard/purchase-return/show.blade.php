@php
    $deliveryStatuses = [
        'delivered' => trans('admin.Delivered'),
        'shipped' => trans('admin.Shipped'),
        'ordered' => trans('admin.Ordered')
    ];
@endphp
<div class="row">
    <div class="col-lg-12">
        <div class="row">
            <div class="col-lg-2">
                @include('components.form.input', [
                    'class' => 'form-control',
                    'name' => "contact",
                    'label' => trans('admin.contact'),
                    'value' => $transaction->Contact?->name,
                    'attribute' => 'required disabled',
                ])
            </div>
            <div class="col-lg-2">
                @include('components.form.input', [
                    'class' => 'form-control',
                    'name' => "branch",
                    'label' => trans('admin.branch'),
                    'value' => $transaction->Branch?->name,
                    'attribute' => 'required disabled',
                ])
            </div>
            <div class="col-lg-2">
                @include('components.form.input', [
                    'class' => 'form-control',
                    'name' => "purchase_return_ref_no",
                    'label' => trans('admin.purchase_return_ref_no'),
                    'value' => $transaction->ref_no,
                    'attribute' => 'required disabled',
                ])
            </div>
            <div class="col-lg-2">
                @include('components.form.input', [
                    'class' => 'form-control',
                    'name' => "parent_purchase_ref_no",
                    'label' => trans('admin.parent_purchase_ref_no'),
                    'value' => $transaction->parentPurchase?->ref_no,
                    'attribute' => 'required disabled',
                ])
            </div>
            <div class="col-lg-2">
                @include('components.form.input', [
                    'class' => 'form-control',
                    'name' => "transaction_date",
                    'label' => trans('admin.transaction_date'),
                    'value' => $transaction->transaction_date,
                    'attribute' => 'required disabled',
                ])
            </div>
            <div class="col-lg-2">
                @include('components.form.input', [
                    'class' => 'form-control text-color-primary',
                    'name' => "delivery_status",
                    'label' => trans('admin.Delivery-Status'),
                    'value' => $deliveryStatuses[$transaction->delivery_status] ?? '',
                    'attribute' => 'required disabled',
                ])
            </div>
        </div>
    </div>

    <div class="col-lg-12">
        <table id="example1" class="table table-bordered table-striped responsive">
            <thead>
            <tr>
                <th>{{ trans('admin.name') }}</th>
                <th>{{ trans('admin.quantity') }}</th>
                @if ($settings->display_warehouse)
                <th>المخزن</th>
                @endif
                <th>{{ trans('admin.unit_price') }}</th>
                <th>{{ trans('admin.total') }}</th>
            </tr>
            </thead>
            <tbody>
                @foreach ($transaction->TransactionSellLines as $line)
                    <tr>
                        <td>{{$line->Product?->name}}</td>
                        <td>{{$line->quantity}} {{$line->Unit?->actual_name}}</td>
                        @if ($settings->display_warehouse)
                        <td>{{ $line->warehouse_id  != null ? $line->warehouse->name : 'الفرع' }}</td>
                        @endif
                        <td>{{$line->unit_price}}</td>
                        <td>{{$line->unit_price * $line->quantity}}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="col-lg-12">
        <div class="row">
            <div class="col-lg-9"></div>
            <div class="col-lg-3">
                <h4>{{ trans('admin.total') }} : {{$transaction->total}}</h4>
            </div>
        </div>
    </div>
</div>