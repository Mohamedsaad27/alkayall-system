<?php
    if(!isset($display))
        $display = "name";

    if(!isset($attribute))
        $attribute = "";

    if(!isset($id))
        $id = "";

    if(!isset($firstDisabled))
        $firstDisabled = false;

    if(!isset($no_select))
        $no_select = false;

    if(!isset($no_laple))
        $no_laple = false;
    if(!isset($index))
        $index = 'id';

    if(!isset($onchange))
        $onchange = '';
?>

<div class="form-group">
    @if ($no_laple == false)
        <label>{{$label}}</label>
    @endif

    <select class="{{$class}}" name="{{$name}}" id="{{$id}}" {{$attribute}} style="width: 100%;" onchange="{{$onchange}}">
        @if ($no_select == false)
            <option value="" selected >{{ trans('admin.Select') }}</option>
        @endif

        @foreach ($collection as $data)
            <option value="{{$data[$index]}}" @if (isset($data[$index]) && $data[$index] == $select) selected @endif>{{$data[$display]}}</option>
        @endforeach
    </select>

    @error($name)
        <span style="color: red; margin: 20px;">{{ $message }}</span>
    @enderror
</div>

