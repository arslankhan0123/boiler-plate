@extends('ecommerce.store.layout')
@section('title', $selectedCategory->name ?? (request('q') ? 'Search: '.request('q') : 'Shop'))

@use('App\Support\Ecommerce\Money')

{{-- The tenant's chosen layout decides which sections the catalog page shows.
     See App\Support\Ecommerce\StoreTheme::LAYOUTS and store/layouts/*.blade.php --}}
@php $layoutView = 'ecommerce.store.layouts.'.(in_array($themeLayout ?? 'classic', ['classic','minimal','showcase'], true) ? $themeLayout : 'classic'); @endphp

@section('content')
@include($layoutView)
@endsection
