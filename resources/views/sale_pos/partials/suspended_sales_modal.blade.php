<!-- Edit Order tax Modal -->
<div class="modal-dialog modal-lg" role="document">
	<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title">@lang('lang_v1.suspended_sales') <small class="text-muted">(Showing latest {{ count($sales) }} records)</small></h4>
		</div>
		<div class="modal-body">
			<div class="row" style="margin-bottom: 15px;">
				<div class="col-md-6">
					<div class="input-group">
						<input type="text" class="form-control" id="suspended_sales_search" placeholder="Search by invoice, customer, notes...">
						<span class="input-group-addon"><i class="fa fa-search"></i></span>
					</div>
				</div>
				<div class="col-md-6 text-right">
					<small class="text-info" id="records_info">
						<i class="fa fa-info-circle"></i> 
						Loaded {{ count($sales) }} records. Scroll down for more.
					</small>
				</div>
			</div>
			<div class="modal-body-scroll" style="max-height: 60vh; overflow-y: auto;" id="suspended_sales_scroll_container">
				<div class="row" id="suspended_sales_container">
					@include('sale_pos.partials.suspended_sales_items', ['sales' => $sales, 'is_tables_enabled' => $is_tables_enabled, 'is_service_staff_enabled' => $is_service_staff_enabled, 'transaction_sub_type' => $transaction_sub_type])
					@if(count($sales) == 0)
						<p class="text-center">@lang('purchase.no_records_found')</p>
					@endif
				</div>
				<div class="text-center" id="loading_more_suspended" style="display: none; padding: 20px;">
					<i class="fa fa-spinner fa-spin"></i> Loading more...
				</div>
				<div class="text-center" id="no_more_records" style="display: none; padding: 10px;">
					<small class="text-muted">No more records</small>
				</div>
			</div>
		</div>
		<div class="modal-footer">
		    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
		</div>
	</div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script>
$(document).ready(function() {
    var loadedRecords = {{ count($sales) }};
    var offset = {{ count($sales) }};
    var isLoading = false;
    var hasMoreRecords = {{ count($sales) >= 50 ? 'true' : 'false' }};
    var searchTerm = '';
    
    // Suspended sales search functionality
    $('#suspended_sales_search').on('keyup', function() {
        searchTerm = $(this).val().toLowerCase();
        var visibleCount = 0;
        
        $('.suspended-sale-item').each(function() {
            var searchTerms = $(this).data('search-terms');
            if (searchTerms.indexOf(searchTerm) !== -1 || searchTerm === '') {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });
        
        // Update search results info
        if (searchTerm !== '') {
            $('#records_info').html('<i class="fa fa-search"></i> Showing ' + visibleCount + ' of ' + loadedRecords + ' loaded records');
        } else {
            $('#records_info').html('<i class="fa fa-info-circle"></i> Loaded ' + loadedRecords + ' records' + (hasMoreRecords ? '. Scroll down for more.' : '.'));
        }
    });
    
    // Infinite scroll functionality
    $('#suspended_sales_scroll_container').on('scroll', function() {
        if (isLoading || !hasMoreRecords || searchTerm !== '') return;
        
        var scrollTop = $(this).scrollTop();
        var scrollHeight = $(this)[0].scrollHeight;
        var height = $(this).height();
        
        // Load more when user scrolls to bottom (with 100px threshold)
        if (scrollTop + height >= scrollHeight - 100) {
            loadMoreSuspendedSales();
        }
    });
    
    function loadMoreSuspendedSales() {
        if (isLoading || !hasMoreRecords) return;
        
        isLoading = true;
        $('#loading_more_suspended').show();
        
        $.ajax({
            url: '/sells/load-more-suspended',
            type: 'GET',
            data: {
                offset: offset,
                limit: 20,
                transaction_sub_type: '{{ $transaction_sub_type ?? "" }}'
            },
            success: function(data) {
                if (data.trim() === '') {
                    hasMoreRecords = false;
                    $('#no_more_records').show();
                } else {
                    $('#suspended_sales_container').append(data);
                    offset += 20;
                    loadedRecords += $(data).find('.suspended-sale-item').length;
                    
                    // Update records info
                    $('#records_info').html('<i class="fa fa-info-circle"></i> Loaded ' + loadedRecords + ' records. Scroll down for more.');
                    
                    // Check if we got less than requested (means no more records)
                    if ($(data).find('.suspended-sale-item').length < 20) {
                        hasMoreRecords = false;
                        $('#no_more_records').show();
                    }
                }
            },
            error: function() {
                console.log('Error loading more suspended sales');
            },
            complete: function() {
                isLoading = false;
                $('#loading_more_suspended').hide();
            }
        });
    }
});
</script>