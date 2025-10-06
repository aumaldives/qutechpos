@extends('layouts.guest')
@section('title', $business->name)

@section('content')
<!-- Modern Header Section -->
<section class="modern-header" id="top">
    <div class="header-overlay"></div>
    <div class="container">
        <div class="header-content">
            <div class="business-logo">
                @if(!empty($business->logo))
                    <img src="{{asset( 'uploads/business_logos/' . $business->logo)}}" alt="{{$business->name}}" class="logo-img">
                @else
                    <div class="logo-placeholder">
                        <i class="fas fa-store"></i>
                    </div>
                @endif
            </div>
            <div class="business-info">
                <h1 class="business-name">{{$business->name}}</h1>
                <h3 class="location-name">{{$business_location->name}}</h3>
                <p class="location-address">{!! $business_location->location_address !!}</p>
            </div>
        </div>
    </div>
</section>

<!-- Modern Navigation -->
<nav class="modern-navbar no-print" id="navbar">
    <div class="container">
        <div class="nav-content">
            <button class="mobile-menu-toggle" id="mobile-toggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="nav-links" id="nav-links">
                @foreach($categories as $key => $value)
                    <a href="#category{{$key}}" class="nav-link menu">{{$value}}</a>
                @endforeach 
                <a href="#category0" class="nav-link menu">Uncategorized</a>
            </div>
        </div>
    </div>
</nav>
<!-- Modern Content Section -->
<section class="modern-content">
    <div class="container">
        @foreach($products as $product_category)
            <div class="category-section" id="category{{$product_category->first()->category->id ?? 0}}">
                <div class="category-header">
                    <h2 class="category-title">
                        <i class="fas fa-tags"></i>
                        {{$product_category->first()->category->name ?? 'Uncategorized'}}
                    </h2>
                    <div class="category-line"></div>
                </div>
                
                <div class="products-grid">
                    @foreach($product_category as $product)
                        <div class="product-card">
                            @php
                                $discount = $discounts->firstWhere('brand_id', $product->brand_id);
                                if(empty($discount)){
                                    $discount = $discounts->firstWhere('category_id', $product->category_id);
                                }
                                $max_price = $product->variations->max('sell_price_inc_tax');
                                $min_price = $product->variations->min('sell_price_inc_tax');
                            @endphp
                            
                            <div class="product-image-container">
                                <a href="#" class="show-product-details" data-href="{{action([\Modules\ProductCatalogue\Http\Controllers\ProductCatalogueController::class, 'show'],  [$business->id, $product->id])}}?location_id={{$business_location->id}}">
                                    <img src="{{$product->image_url}}" class="product-image" alt="{{$product->name}}">
                                    <div class="image-overlay">
                                        <i class="fas fa-eye"></i>
                                        <span>View Details</span>
                                    </div>
                                </a>
                                
                                @if(!empty($discount))
                                    <div class="discount-badge">
                                        <span>-{{($discount->discount_amount)}}%</span>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="product-info">
                                <h3 class="product-title">
                                    <a href="#" class="show-product-details" data-href="{{action([\Modules\ProductCatalogue\Http\Controllers\ProductCatalogueController::class, 'show'],  [$business->id, $product->id])}}?location_id={{$business_location->id}}">
                                        {{$product->name}}
                                    </a>
                                </h3>
                                
                                <div class="product-details">
                                    <div class="price-section">
                                        <div class="price-label">Price</div>
                                        <div class="price-value">
                                            <span class="display_currency" data-currency_symbol="true">{{($max_price)}}</span>
                                            @if($max_price != $min_price) 
                                                <span class="price-range">- <span class="display_currency" data-currency_symbol="true">{{($min_price)}}</span></span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="sku-section">
                                        <div class="sku-label">SKU</div>
                                        <div class="sku-value">{{$product->sku}}</div>
                                    </div>
                                </div>
                                
                                @if($product->type == 'variable')
                                    <div class="variations-section">
                                        @php
                                            $variations = $product->variations->groupBy('product_variation_id');
                                        @endphp
                                        @foreach($variations as $product_variation)
                                            <div class="variation-group">
                                                <label class="variation-label">{{$product_variation->first()->product_variation->name}}</label>
                                                <select class="modern-select">
                                                    @foreach($product_variation as $variation)
                                                        <option value="{{$variation->id}}">
                                                            {{$variation->name}} ({{$variation->sub_sku}}) - <span class="display_currency" data-currency_symbol="true">{{($variation->sell_price_inc_tax)}}</span>
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
    
    <!-- Modern Scroll to Top -->
    <div class='modern-scroll-top no-print' id="scroll-top">
        <i class="fas fa-arrow-up"></i>
    </div>
</section>
<!-- /.content -->
<!-- Add currency related field-->
<input type="hidden" id="__code" value="{{$business->currency->code}}">
<input type="hidden" id="__symbol" value="{{$business->currency->symbol}}">
<input type="hidden" id="__thousand" value="{{$business->currency->thousand_separator}}">
<input type="hidden" id="__decimal" value="{{$business->currency->decimal_separator}}">
<input type="hidden" id="__symbol_placement" value="{{$business->currency->currency_symbol_placement}}">
<input type="hidden" id="__precision" value="{{$business->currency_precision}}">
<input type="hidden" id="__quantity_precision" value="{{$business->quantity_precision}}">
<div class="modal fade product_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>
@stop
@section('javascript')
<style>
/* Modern Catalogue Styles */
* {
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    color: #333;
    background: #f8f9fa;
}

/* Modern Header */
.modern-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 4rem 0;
    position: relative;
    overflow: hidden;
}

.header-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.1);
}

.header-content {
    display: flex;
    align-items: center;
    gap: 2rem;
    position: relative;
    z-index: 2;
}

.business-logo {
    flex-shrink: 0;
}

.logo-img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 4px solid rgba(255,255,255,0.3);
    object-fit: cover;
}

.logo-placeholder {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    border: 4px solid rgba(255,255,255,0.3);
}

.business-info {
    flex: 1;
}

.business-name {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.location-name {
    font-size: 1.3rem;
    margin: 0.5rem 0;
    opacity: 0.9;
}

.location-address {
    font-size: 1rem;
    margin: 0;
    opacity: 0.8;
}

/* Modern Navigation */
.modern-navbar {
    background: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
    transition: all 0.3s ease;
}

.nav-content {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem 0;
    position: relative;
}

.mobile-menu-toggle {
    display: none;
    background: none;
    border: none;
    flex-direction: column;
    gap: 4px;
    cursor: pointer;
    position: absolute;
    right: 0;
}

.mobile-menu-toggle span {
    width: 25px;
    height: 3px;
    background: #333;
    transition: all 0.3s ease;
}

.nav-links {
    display: flex;
    gap: 2rem;
    align-items: center;
    flex-wrap: wrap;
    justify-content: center;
}

.nav-link {
    color: #333;
    text-decoration: none;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    transition: all 0.3s ease;
    position: relative;
}

.nav-link:hover {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    text-decoration: none;
    transform: translateY(-2px);
}

/* Modern Content */
.modern-content {
    padding: 2rem 0;
}

.category-section {
    margin-bottom: 4rem;
}

.category-header {
    text-align: center;
    margin-bottom: 3rem;
}

.category-title {
    font-size: 2rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 1rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.category-title i {
    color: #667eea;
}

.category-line {
    width: 60px;
    height: 4px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    margin: 0 auto;
    border-radius: 2px;
}

/* Products Grid */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
}

.product-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.product-image-container {
    position: relative;
    height: 250px;
    overflow: hidden;
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: all 0.3s ease;
}

.product-card:hover .product-image {
    transform: scale(1.05);
}

.image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(102, 126, 234, 0.9);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
    opacity: 0;
    transition: all 0.3s ease;
}

.product-card:hover .image-overlay {
    opacity: 1;
}

.image-overlay i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.discount-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
    box-shadow: 0 2px 8px rgba(231, 76, 60, 0.3);
}

.product-info {
    padding: 1.5rem;
}

.product-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 1rem;
    line-height: 1.4;
}

.product-title a {
    color: #333;
    text-decoration: none;
    transition: color 0.3s ease;
}

.product-title a:hover {
    color: #667eea;
    text-decoration: none;
}

.product-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.price-section, .sku-section {
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 3px solid #667eea;
}

.price-label, .sku-label {
    font-size: 0.8rem;
    color: #6c757d;
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 0.25rem;
}

.price-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: #333;
}

.price-range {
    font-weight: 400;
    color: #6c757d;
}

.sku-value {
    font-size: 0.9rem;
    font-weight: 600;
    color: #333;
    font-family: monospace;
}

.variations-section {
    margin-top: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.variation-group {
    margin-bottom: 0.75rem;
}

.variation-label {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.25rem;
}

.modern-select {
    width: 100%;
    padding: 0.5rem;
    border: 2px solid #e9ecef;
    border-radius: 6px;
    background: white;
    font-size: 0.85rem;
    transition: all 0.3s ease;
}

.modern-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Modern Scroll to Top */
.modern-scroll-top {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    transition: all 0.3s ease;
    opacity: 0;
    visibility: hidden;
}

.modern-scroll-top.visible {
    opacity: 1;
    visibility: visible;
}

.modern-scroll-top:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .business-name {
        font-size: 2rem;
    }
    
    .mobile-menu-toggle {
        display: flex;
    }
    
    .nav-links {
        display: none;
        flex-direction: column;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        padding: 1rem;
        gap: 0.5rem;
    }
    
    .nav-links.active {
        display: flex;
    }
    
    .products-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .product-details {
        grid-template-columns: 1fr;
    }
}

@media print {
    .no-print {
        display: none !important;
    }
}
</style>

<script type="text/javascript">

    (function($) {
    $(document).ready( function() {
        //Set global currency to be used in the application
        __currency_symbol = $('input#__symbol').val();
        __currency_thousand_separator = $('input#__thousand').val();
        __currency_decimal_separator = $('input#__decimal').val();
        __currency_symbol_placement = $('input#__symbol_placement').val();
        if ($('input#__precision').length > 0) {
            __currency_precision = $('input#__precision').val();
        } else {
            __currency_precision = 2;
        }

        if ($('input#__quantity_precision').length > 0) {
            __quantity_precision = $('input#__quantity_precision').val();
        } else {
            __quantity_precision = 2;
        }

        //Set page level currency to be used for some pages. (Purchase page)
        if ($('input#p_symbol').length > 0) {
            __p_currency_symbol = $('input#p_symbol').val();
            __p_currency_thousand_separator = $('input#p_thousand').val();
            __p_currency_decimal_separator = $('input#p_decimal').val();
        }

        __currency_convert_recursively($('.modern-content'));
    });

    // Mobile menu toggle
    $(document).on('click', '#mobile-toggle', function() {
        $('#nav-links').toggleClass('active');
    });

    // Product details modal
    $(document).on('click', '.show-product-details', function(e){
        e.preventDefault();
        $.ajax({
            url: $(this).data('href'),
            dataType: 'html',
            success: function(result) {
                $('.product_modal')
                    .html(result)
                    .modal('show');
                __currency_convert_recursively($('.product_modal'));
            },
        });
    });

    // Smooth scroll navigation
    $(document).on('click', '.menu', function(e){
        e.preventDefault();
        $('#nav-links').removeClass('active');

        var cat_id = $(this).attr('href');
        if ($(cat_id).length) {
            $('html, body').animate({
                scrollTop: $(cat_id).offset().top - 100
            }, 800);
        }
    });

    // Modern scroll effects
    $(window).scroll(function() {
        var height = $(window).scrollTop();
        
        // Show/hide scroll to top
        if(height > 300) {
            $('#scroll-top').addClass('visible');
        } else {
            $('#scroll-top').removeClass('visible');
        }
        
        // Navbar shadow effect
        if(height > 50) {
            $('.modern-navbar').css('box-shadow', '0 4px 20px rgba(0,0,0,0.15)');
        } else {
            $('.modern-navbar').css('box-shadow', '0 2px 10px rgba(0,0,0,0.1)');
        }
    });

    // Scroll to top functionality
    $(document).on('click', '#scroll-top', function(e){
        e.preventDefault();
        $("html,body").animate({scrollTop: $("#top").offset().top}, 800);
    });

    })(jQuery);
</script>
@endsection
