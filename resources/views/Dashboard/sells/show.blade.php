@php
    $deliveryStatuses = [
        'delivered' => trans('admin.Delivered'),
        'shipped' => trans('admin.Shipped'),
        'ordered' => trans('admin.Ordered')
    ];
    $paymentStatuses = [
        'due' => trans('admin.Due'),
        'final' => trans('admin.Final'),
        'partial' => trans('admin.Partial')
    ];
@endphp
<div class="row">

    <div class="col-lg-12">
        <div class="row">   
            <div class="col-lg-4">
                @include('components.form.input', [
                    'class' => 'form-control',
                    'name' => "contact",
                    'label' => trans('admin.contact'),
                    'value' => $transaction->Contact?->name,
                    'attribute' => 'required disabled',
                ])
            </div>
            <div class="col-lg-4">
                @include('components.form.input', [
                    'class' => 'form-control',
                    'name' => "branch",
                    'label' => trans('admin.branch'),
                    'value' => $transaction->Branch?->name,
                    'attribute' => 'required disabled',
                ])
            </div>
            <div class="col-lg-4">
                @include('components.form.input', [
                    'class' => 'form-control',
                    'name' => "ref_no",
                    'label' => trans('admin.ref_no'),
                    'value' => $transaction->ref_no,
                    'attribute' => 'required disabled',
                ])
            </div>
        </div>
    </div>
    <div class="col-lg-12">
        <div class="row">   
            <div class="col-lg-4">
                @include('components.form.input', [
                    'class' => 'form-control',
                    'name' => "transaction_date",
                    'label' => trans('admin.Created at'),
                    'value' => Carbon\Carbon::parse($transaction->transaction_date)->format('d-m-Y h:i A'),
                    'attribute' => 'required disabled',
                ])
            </div>
            <div class="col-lg-4">
                @include('components.form.input', [
                    'class' => 'form-control text-color-primary',
                    'name' => "delivery_status",
                    'label' => trans('admin.Delivery-Status'),
                    'value' => $deliveryStatuses[$transaction->delivery_status] ?? '',
                    'attribute' => 'required disabled',
                ])
            </div>
            <div class="col-lg-4">
                @include('components.form.input', [
                    'class' => 'form-control text-color-primary',
                    'name' => "payment_status",
                    'label' => trans('admin.payment_status'),
                    'value' => $paymentStatuses[$transaction->payment_status] ?? '',
                    'attribute' => 'required disabled',
                ])
            </div>
        </div>
    </div>

    <div class="col-lg-12">
        <table id="example1" class="table table-bordered table-striped responsive">
            <thead>
            <tr>
                <th>{{ trans('admin.product') }}</th>
                <th>{{ trans('admin.quantity') }}</th>
                @if ($settings->display_warehouse)
                <th>المخزن</th>
                @endif
                <th>{{ trans('admin.unit') }}</th>
                <th>{{ trans('admin.unit_price') }}</th>
                <th>{{ trans('admin.total') }}</th>

            </tr>
            </thead>
            <tbody>
                @foreach ($transaction->TransactionSellLines as $line)
                    <tr>
                        <td>{{$line->Product?->name}}</td>
                        <td>{{$line->quantity}}</td>
                        @if ($settings->display_warehouse)
                        <td>{{ $line->warehouse_id  != null ? $line->warehouse->name : 'الفرع' }}</td>
                        @endif
                        <td>{{$line->Unit?->actual_name}}</td>
                        <td>{{$line->unit_price}}</td>
                        <td>{{$line->total}}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($transaction->sellUpdateHistories->count() > 0 || $transaction->ReturnTransactions->count() > 0)
    <div class="col-lg-12 mt-4">
        <h4>{{ trans('admin.transaction_history') }}</h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>{{ trans('admin.date') }}</th>
                        <th>{{ trans('admin.type') }}</th>
                        <th>{{ trans('admin.ref_no') }}</th>
                        <th>{{ trans('admin.old_qty') }}</th>
                        <th>{{ trans('admin.new_qty') }}</th>
                        <th>{{ trans('admin.old_total') }}</th>
                        <th>{{ trans('admin.new_total') }}</th>
                        <th>{{ trans('admin.amount') }}</th>
                        <th>{{ trans('admin.updated_by') }}</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Update History Rows --}}
                    @foreach($transaction->sellUpdateHistories->sortByDesc('created_at') as $history)
                    <tr>
                        <td>{{ Carbon\Carbon::parse($history->created_at)->format('d-m-Y h:i A') }}</td>
                        <td>
                            <span class="badge bg-warning">
                                {{ trans('admin.Update') }}
                            </span>
                        </td>
                        <td>{{ $transaction->ref_no }}</td>
                        <td>{{ $history->old_final_price }}</td>
                        <td>{{ $history->new_final_price }}</td>
                        <td>{{ $history->old_total }}</td>
                        <td>{{ $history->new_total }}</td>
                        <td>-</td>
                        <td>{{ $history->updatedBy->name }}</td>
                    </tr>
                    @endforeach

                    {{-- Return Transactions Rows --}}
                    @foreach($transaction->ReturnTransactions->sortBy('created_at') as $history)
                    <tr>
                        <td>{{ Carbon\Carbon::parse($history->created_at)->format('d-m-Y h:i A') }}</td>
                        <td>
                            <span class="badge {{ $history->type == 'sell_return' ? 'bg-danger' : 'bg-info' }}">
                                {{ trans('admin.' . ucfirst($history->type)) }}
                            </span>
                        </td>
                        <td>{{ $history->ref_no }}</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>-</td>
                        <td>{{ $history->final_price }}</td>
                        <td>{{ $history->createdBy->name ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif



    <div class="col-lg-12">
        <div class="row">
            <div class="col-lg-9"></div>
            <div class="col-lg-3">
                @if($transaction->ReturnTransactions->count() > 0)
                <h4>{{ trans('admin.net_amount') }} : {{$transaction->final_price + $transaction->ReturnTransactions->sum('final_price')}}</h4>
                <h4>{{ trans('admin.total_returned') }} : {{$transaction->ReturnTransactions->sum('final_price')}}</h4>
                @endif                
                <h4>{{ trans('admin.total') }} : {{$transaction->total}}</h4>
                <h4>{{ trans('admin.discount') }} : {{$transaction->discount_value ?? 0}}</h4>
                @if($transaction->tax_amount > 0)
                <h4>{{ trans('admin.tax') }} : {{$transaction->TransactionTaxes->sum('tax_amount')}} ({{ implode('- ', $transaction->TransactionTaxes->pluck('taxRate.name')->toArray()) }})</h4>
                @endif
                <h4>{{ trans('admin.final_price') }} : {{$transaction->final_price}}</h4>
            </div>
        </div>
    </div>
</div>