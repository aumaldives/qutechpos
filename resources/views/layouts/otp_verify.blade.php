@extends('layouts.auth3')

@section('title', 'Email OTP Verification')

@section('content')

<div class="row justify-content-center mt-5 login-form">
    <h1 class="fw-bold">Email OTP Verification</h1>

    @if(session('status'))
    @if(session('status')['success'] === 0)
        <div class="alert alert-danger">
            {{ session('status')['msg'] }}
        </div>
    @elseif(session('status')['success'] === 1)
        <div class="alert alert-success">
            {{ session('status')['msg'] }}
        </div>
    @endif
@endif

    <form method="POST" action="{{ route('otp.verify.post') }}">
        {{ csrf_field() }}
        <div class="mb-3 form-group has-feedback {{ $errors->has('otp_number') ? ' has-error' : '' }}">
            <label for="otp_number" class="form-label">Enter OTP</label>
            <input id="otp_number" type="text" class="form-control" name="otp_number" value="{{ old('otp_number') }}" required autofocus placeholder="OTP Password">

            @if ($errors->has('otp_number'))
            <span class="help-block">
                <strong>{{ $errors->first('otp_number') }}</strong>
            </span>
            @endif

        </div>

        <input type="hidden" name="token" value="{{ @md5($user->email) }}">

        <button type="submit" class="btn btn-green w-100 btn-lg">Verify Email OTP</button>
    </form>


    <form action="{{ route('otp.resend') }}" method="post">
        {{ csrf_field() }}
        <input type="hidden" name="token" value="{{ @md5($user->email) }}">
        <button type="submit" class="btn btn-green w-100 mt-5">Resend OTP</button>
    </form>


    
</div>

@endsection