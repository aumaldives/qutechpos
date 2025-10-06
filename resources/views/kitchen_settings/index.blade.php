@extends('layouts.app')
@section('title', __('kitchen.kitchen_settings'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('kitchen.kitchen_settings')</h1>
    <br>
    @include('layouts.partials.search_settings')
</section>

<!-- Main content -->
<section class="content">
{!! Form::open(['url' => route('kitchen-settings.update'), 'method' => 'post', 'id' => 'kitchen_settings_form']) !!}
    <div class="row">
        <div class="col-xs-12">
        <div class="col-xs-12 pos-tab-container">
            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-2 pos-tab-menu">
                <div class="list-group">
                    <a href="#" class="list-group-item text-center active">@lang('kitchen.category_settings')</a>
                </div>
            </div>
            <div class="col-lg-10 col-md-10 col-sm-10 col-xs-10 pos-tab">
                <!-- Category Settings Tab -->
                @include('kitchen_settings.partials.category_settings')
            </div>
        </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12">
            <button type="submit" class="btn btn-primary pull-right" id="submit_kitchen_settings_form">@lang('messages.update')</button>
        </div>
    </div>
{!! Form::close() !!}
</section>
<!-- /.content -->

@endsection

@section('javascript')
<script>
$(document).ready(function(){
    // Initialize Select2 for categories
    $('#auto_cook_categories').select2({
        placeholder: '@lang("kitchen.select_categories")',
        allowClear: true,
        multiple: true,
        ajax: {
            url: "{{ route('kitchen-settings.categories') }}",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term,
                    page: params.page
                };
            },
            processResults: function (data, params) {
                params.page = params.page || 1;
                return {
                    results: data,
                    pagination: {
                        more: false
                    }
                };
            },
            cache: true
        }
    });

    // Pre-select existing categories
    @if(!empty($auto_cook_categories))
        @foreach($auto_cook_categories as $category_id)
            @php
                $category = $categories->firstWhere('id', $category_id);
                if ($category) {
                    $text = $category->name;
                    if ($category->parent_id) {
                        $parent = $categories->firstWhere('id', $category->parent_id);
                        if ($parent) {
                            $text = $parent->name . ' → ' . $category->name;
                        }
                    }
                }
            @endphp
            @if(isset($text))
                var option = new Option("{{ $text }}", "{{ $category_id }}", true, true);
                $('#auto_cook_categories').append(option).trigger('change');
            @endif
        @endforeach
    @endif
});
</script>
@endsection