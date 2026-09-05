@php
$configData = \App\Helpers\AdminHelpers::appClasses();
@endphp

@extends('admin.layouts/layoutMaster')

@section('title', 'Home')

@section('content')
<h4>Home Page</h4>
<p>For more layout options refer <a href="{{ config('admin-variables.documentation') ? config('admin-variables.documentation').'/laravel-introduction.html' : '#' }}" target="_blank" rel="noopener noreferrer">documentation</a>.</p>
@endsection
