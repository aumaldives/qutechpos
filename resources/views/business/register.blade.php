@extends('layouts.auth3')
@section('title', __('lang_v1.register'))

@section('content')

<link rel="stylesheet" href="{{ asset('css/register.css') }}">

<div class="login-form col-md-12 col-xs-12 right-col-content-register">
    {!! Form::open(['url' => route('business.postRegister'), 'method' => 'post', 
                            'id' => 'business_register_form','files' => true ]) !!}
        @include('business.partials.register_form')
        {!! Form::hidden('package_id', $package_id); !!}
    {!! Form::close() !!}
</div>
@stop
@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){
        $('#change_lang').change( function(){
            window.location = "{{ route('business.getRegister') }}?lang=" + $(this).val();
        });

        $('input').iCheck({
            checkboxClass: 'icheckbox_square-blue',
            radioClass: 'iradio_square-blue',
            increaseArea: '20%'
        });

        $('.section-to-hide').removeClass('d-none');
    })
</script>
@endsection