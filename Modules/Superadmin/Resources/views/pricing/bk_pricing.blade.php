@extends('layouts.pricing')
@section('title', __('superadmin::lang.pricing'))

@section('content')

 <div class="pricing_table_container">
    <div class="heading">
        <h2>Our <b>Pricing</b> Plans</h2>
        <p class="text">A plan for every business.<br><b>All plans offer all advanced features of our POS software</b></p>

        <div class="row switch-container">
            <div class="switch-wrapper">
                <input id="monthly" type="radio" name="switch" checked>
                <input id="yearly" type="radio" name="switch">
                <label for="monthly">Monthly</label>
                <label for="yearly">Yearly</label>
                <span class="highlighter"></span>
            </div>
        </div>

        <div class="row">
            <button type="button" class="btn-free-trial w-50">
			<a href="/business/register?package=16" class="btn btn-block btn-success">Start your free trial now</a>
        </button>
        </div>
        
        </h2>
        
    </div>
        @include('superadmin::subscription.partials.new_packages', ['action_type' => 'register'])
</div> 
@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){

        $(".plan.package-months").show();

        $("input[name='switch']").change(function () {
            var filter = $(this).attr("id");
            if (filter === "monthly") {
                $(".plan").hide();
                $(".plan.package-months").show();
            } else {
                $(".plan").hide();
                $(".plan.package-years").show();
            }
        });
    })
</script>

@endsection