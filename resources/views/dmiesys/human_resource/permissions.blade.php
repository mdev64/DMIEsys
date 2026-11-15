@extends('layouts.dmiesys.app')

@section('title', '| User Permissions')
@section('back_page', 'Go back to Dashboard')
@section('back_link') {{ route('dashboard') }} @endsection




@section('content')

<div class="wide-screen">

    @include('components.premission-indicator', [
    'page' => 'Manage User Permissions',
    ])


    <div class="lg:px-24 2xl:px-44">

        @livewire('roles-list')

    </div>



</div>

@endsection
