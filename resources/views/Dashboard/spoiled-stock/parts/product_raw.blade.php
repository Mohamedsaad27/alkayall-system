<tr class="product_row_{{$product_row['id']}}">
    <input type="hidden" name="products[{{$product_row['id']}}][id]" value="{{$product_row['id']}}">
    <input type="hidden" class="product_row_available_quantity_{{$product_row['id']}}" name="products[{{$product_row['id']}}][available_quantity]" value="{{$product_row['available_quantity']}}">
    <td>
        {{$product_row['name']}}
    </td>
    <td>
        <select class="form-control select2 unit-select" name="products[{{$product_row['id']}}][unit_id]" required data-product_row_id="{{$product_row['id']}}">
            @foreach ($product_row['units'] as $unit)
                <option value="{{$unit['id']}}" data-multipler="{{$unit->getMultiplier()}}">{{$unit['actual_name']}}</option>
            @endforeach
        </select>   
    </td>
    <td>
        {{$product_row['available_quantity']}}
    </td>
    <td>
        <input type="number" class="form-control product_row_quantity product_row_quantity_{{$product_row['id']}}"
                required name="products[{{$product_row['id']}}][quantity]" 
                value="0"
                data-product_row_id="{{$product_row['id']}}"
                min="0" max="{{$product_row['available_quantity']}}">
    </td>
    @if ($settings->display_warehouse)
    <td>
        <select class="form-control select2 " name="products[{{$product_row['id']}}][warehouse_id]"  data-product_row_id="{{$product_row['id']}}">
            <option value="">الفرع</option>
            @foreach ($product_row['warehouses'] as $warehouse)
                <option value="{{$warehouse['id']}}" >{{$warehouse['name']}}</option>
            @endforeach
        </select>   
    </td>
    @endif
    <td>
        <i class="fas fa-trash remove_row" style="color: #bd0d0d; cursor: pointer;" data-product_row_id="{{$product_row['id']}}"></i>
    </td>
</tr>
