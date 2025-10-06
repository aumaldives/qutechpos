@extends('layouts.auth')
@section('title', __('superadmin::lang.pricing'))

@section('content')

<div class="container">
    @include('superadmin::layouts.partials.currency')
    @include('layouts.partials.logo')
    <div class="row">
        <div class="pricing_table_container">
            <div class="heading">
                <h3 class="box-title text-center">@lang('superadmin::lang.packages')</h3>
            </div>
            <div class="filter-sec container">
                <ul class="nav nav-pills filter-bar">
                    <li class="nav-item">
                        <a class="nav-link filter-link active" href="#" data-filter="all">All</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link filter-link" href="#" data-filter="months">Monthly</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link filter-link" href="#" data-filter="years">Yearly</a>
                    </li>
                </ul>

                <ul class="nav nav-pills filter-curr">
                    <li class="nav-item">
                        <a class="nav-link {{ empty(Request::get('cur')) || Request::get('cur') === 'usd' ? 'active': '' }}" href="{{ route('pricing').'?lang='.Request::get('lang').'&cur=usd' }}">USD</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ Request::get('cur') === 'mvr' ? 'active': '' }}" href="{{ route('pricing').'?lang='.Request::get('lang').'&cur=mvr' }}">MVR</a>
                    </li>
                </ul>
            </div>
            <div class="plans">
                @include('superadmin::subscription.partials.packages_guest', ['action_type' => 'register'])
            </div>
        </div>
    </div>
</div>
@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){
        $('#change_lang').change( function(){
            window.location = "{{ route('pricing') }}?lang=" + $(this).val() + "&cur=" + $(".filter-curr").val();
        });

        $('.filter-curr').change( function(){
            if($(this).val() != "") {
                window.location = "{{ route('pricing') }}?lang=" + $('#change_lang').val() + "&cur=" + $(this).val();
            }
        });

        $(".filter-link").click(function() {
            var filter = $(this).data("filter");
            if (filter === "all") {
                $(".plan").show();
            } else {
                $(".plan").hide();
                $(".plan.package-" + filter).show();
            }
            // Update active class
            $(".filter-link").removeClass("active");
            $(this).addClass("active");
        })
    })
</script>
<style type="text/css">
    .pricing_table_container{
    font-family: -apple-system, BlinkMacSystemFont, Helvetica, 'Open Sans', 'Lato', 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
    scroll-behavior: smooth;
  	position: relative;
	padding: 0px;
    background-color: #EFF7FD;
    min-height: 100vh;
}
.pricing_table_container .heading {
    display: flex;
    flex-direction: column;
    margin: 20px auto 10px auto;
}
.pricing_table_container .heading h2{
    margin: auto auto 20px auto;
    text-align: center;
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
    color: #212121;
}
.pricing_table_container .heading h2 b{
    font-weight: 700;
}
.pricing_table_container .heading .text{
    text-align: center;
    font-family: 'Open Sans', sans-serif;
    color: #5c5c5c;
    line-height: 2;
}
.pricing_table_container .heading .text b{
    font-weight: 600;
}
.pricing_table_container .plans{
    display: flex;
    justify-content: center;
    max-width: 1200px;
    margin: auto;
}
.pricing_table_container .plans .plan{
    min-width: 280px;
    width: 320px;
    max-width: 400px;
    height: max-content;
    margin: auto 30px 20px 30px;
    background-color: #fff;
    border-radius: 10px;
    padding: 35px;
    position: relative;
    box-shadow: 1px 10px 20px -3px rgba(0,0,0,0.1);
    -webkit-box-shadow: 1px 10px 20px -3px rgba(0,0,0,0.1);
    -moz-box-shadow: 1px 10px 20px -3px rgba(0,0,0,0.1);
    transition: box-shadow 300ms;
}
.pricing_table_container .plans .plan:hover{
    box-shadow: 1px 10px 20px -3px rgba(0,0,0,0.2);
    -webkit-box-shadow: 1px 10px 20px -3px rgba(0,0,0,0.2);
    -moz-box-shadow: 1px 10px 20px -3px rgba(0,0,0,0.2);
}
.pricing_table_container .plans .plan.pro{
    background-color: #2F58CD;
    color: #fff;
}
.pricing_table_container .plans .plan .title-row{
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    align-content: flex-start;
}
.pricing_table_container .plans .plan .title{
    margin: 0 auto 20px 0;
}
.pricing_table_container .plans .plan .title h3{
    color: #212121;
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
    font-size: 16px;
}
.pricing_table_container .plans .plan.pro .title h3{
    color: #fff;
}
.pricing_table_container .plans .plan .title span{
    color: #6a6a6a;
    font-family: 'Poppins', sans-serif;
}
.pricing_table_container .plans .plan.pro .title span{
    color: #BDB8C8;
}
.pricing_table_container .plans .plan .price{
    background-color: #eee;
    padding: 8px 8px;
    font-family: 'Montserrat', sans-serif;
    border-radius: 6px;
    height: auto;
    display: flex;
    justify-content: center;
    flex-direction: column;
}
.pricing_table_container .plans .plan.pro .price{
    background-color: #3e6be4;
}

.pricing_table_container .plans .plan .price h4{
    color: #3e6be4;
    text-align: center;
}
.pricing_table_container .plans .plan.pro .price h4{
    color: #fff;
}
.pricing_table_container .plans .plan .price span{
    color: #6a6a6a;
    text-align: center;
}
.pricing_table_container .plans .plan.pro .price span{
    color: #BDB8C8;
}
.pricing_table_container .plans .plan hr{
    border: none;
    border-bottom: 1.5px solid #E8E8E8;
    margin: 6px 0;
}
.pricing_table_container .plans .plan.pro hr{
    border-bottom-color: #517cf5;
}

.pricing_table_container .plans .plan button{
    background-color: #FF7557;
    color: #fff;
    border:none;
    border-radius: 6px;
    border: 2px solid transparent;
    width: 100%;
    font-family: 'Poppins', sans-serif;
    font-weight: 500;
    cursor: pointer;
    transition: all 500ms;
}
.pricing_table_container .plans .plan button a {
    display: flex;
    justify-content: center;
    padding: 10px;
    margin: 0;
}
.pricing_table_container .plans .plan.pro button{
    border: 2px solid #fff;
    background-color: #2F58CD;
    color: #fff;
}
.pricing_table_container .plans .plan.ultimate button{
    background-color : #E2E2F7;
    color: #2F58CD;
}
.pricing_table_container .plans .plan button:hover{
    transform: scale(1.015);
}
.pricing_table_container .plans .plan.basic button:hover{
    background-color: #ff532c;
}
.pricing_table_container .plans .plan.pro button:hover{
    background-color: #fff;
    color: #2F58CD;
}
.pricing_table_container .plans .plan.ultimate button:hover{
    background-color: #afaffb;
}

.pricing_table_container .plans .plan ul{
    margin: 25px 0;
    padding: 0;
}
.pricing_table_container .plans .plan ul li{
    font-family: 'Open Sans', sans-serif;
    margin-bottom: 12px;
    list-style-position: inside;
    list-style-type:none;
    margin-left: 20px;
    color: #212121;
    font-family: 'Open Sans', sans-serif;
}
.pricing_table_container .plans .plan.pro ul li{
    color: #fff;
}
.pricing_table_container .plans .plan.basic ul li b{
    color: #2F58CD;
}
.pricing_table_container .plans .plan.pro ul li b{
    color: #fff;
}
.pricing_table_container .plans .plan.ultimate ul li b{
    color: #2F58CD;
}
.pricing_table_container .plans .plan ul li .fa-li{
    left: 28px;
}
.pricing_table_container .plans .plan ul li.disabled{
    color: #aeaeae;
}
@media (max-width: 1250px) {
    .pricing_table_container .plans{
        flex-wrap: wrap;
    }
    .pricing_table_container .plans .plan{
        margin: 20px auto;
    }
}
@media (max-width: 1024px) {
    .pricing_table_container .plans .plan{
        margin: 30px 22px;
    }
}
@media (max-width: 767px) {
    .pricing_table_container .heading{
        margin: 80px 10px;
    }
    .pricing_table_container .plans{
        flex-direction: column;
        flex-wrap: wrap;
    }
    .pricing_table_container .plans .plan{
        margin: auto auto 30px auto;
        padding: 35px 25px;
    }
    .pricing_table_container .plans .plan ul li .fa-li{
        left: 20px;
    }
    .pricing_table_container .plans .plan ul li{
        margin-left: 28px;
    }
}
@media (max-width: 376px) {
    .pricing_table_container .plans .plan{
        margin: auto 25px 50px 25px;
        min-width: 150px;
        max-width: auto;
        padding: 25px 18px;
    }
    .pricing_table_container .plans .plan ul li .fa-li{
        left: 12px;
    }
    .pricing_table_container .plans .plan ul li{
        margin-left: 25px;
    }
}
@media (max-width: 321px) {
    .pricing_table_container .plans .plan{
        margin: auto 15px 50px 15px;
    }
}
ul.nav.nav-pills.filter-bar a.active, ul.nav.nav-pills.filter-bar a:hover {
    background-color: #FF7557;
    color: #fff;
}
ul.nav.nav-pills.filter-curr a.active, ul.nav.nav-pills.filter-curr a:hover {
    background-color: #FF7557;
    color: #fff;
}

.filter-sec {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
} 

.filter-bar {
    display: flex;
    flex-wrap: wrap;
}

.filter-link {
    margin-right: 10px;
}
.filter-curr{
    width: auto;
}
body {
    background-color: #EFF7FD;
}
h1.page-header {
    color: #000;
}
.look-btn {
    width: 100%;
    display: block;
}
</style>
@endsection
