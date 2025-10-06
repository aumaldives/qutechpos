@extends('layouts.app')
@section('title', __('messages.settings'))

@section('vue')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>{{ __('messages.settings') }}</h1>
</section>

@if(empty($is_demo))
<section class="content">
	@component('components.widget', ['class' => 'box-solid', 'title' => ''])
        <form method="POST" action="{{ route('plasticbag.store') }}">
            @csrf
            <div class="form-group">
                <label for="number_field">Plastic bag fees(per bag):</label>
                <input type="number" id="plasticbag_per_piece" value="{{ $settingRow['plasticbag_per_piece'] ?? '' }}" name="plasticbag_per_piece" class="form-control" placeholder="Enter Plastic bag fees(per bag)" required />
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    @endcomponent
</section>
@endif

@stop