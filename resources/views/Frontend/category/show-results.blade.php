@extends('layouts.frontend')

@section('title', trans('frontend.home'))

@section('header')
<div class="container">
    <div class="container-inner">
        <h1>'<span>{{ $query }}'</h1>
    </div>
</div>
@endsection 
@section('content')
    @include('Frontend.category.products')
@endsection
 