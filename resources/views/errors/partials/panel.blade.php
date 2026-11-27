@extends('layouts.admin.main')
@section('page_title', $code.' '.$title)
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">{{ $title }}</li>
@endsection
@section('content')
    @include('errors.partials.card')
@endsection
