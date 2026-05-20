@extends('layouts.app')

@section('title', 'Porta IT | User Profile')
@section('header_title', 'Account Settings')

@section('content')
<div class="row">
    <div class="col-12 col-lg-8 mx-auto">
        
        <div class="mb-4">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="mb-4">
            @include('profile.partials.update-password-form')
        </div>

        <div class="mb-5">
            @include('profile.partials.delete-user-form')
        </div>
        
    </div>
</div>
@endsection