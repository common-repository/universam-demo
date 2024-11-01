(function($){	
	
	$.extend( USAM_Sort, {
		init : function() {
			$(function()
			{	
				$(USAM_Sort).trigger('usam_loaded_sort');				
			});
		}
	});
	
	USAM_Sort.sort = {
		new_field_count : 0,
		
		event_init : function() {
			var wrapper = jQuery('.wrap');	
		
			wrapper.find('.wp-list-table').
				sortable({
					items       : 'tr',
					axis        : 'y',
					containment : 'parent',
					placeholder : 'checkout-placeholder',
					handle      : '.drag',
					sort        : USAM_Sort.sort.event_sort,
					helper      : USAM_Sort.sort.fix_sortable_helper,					
					update      : USAM_Sort.sort.event_sort_update
				});				
		},
		
		fix_sortable_helper : function(e, tr) {

			var row = tr.clone().width(tr.width());
			row.find('td').each(function(index){
				var td_class = jQuery(this).attr('class'), original = tr.find('.' + td_class), old_html = jQuery(this).html();
				jQuery(this).width(original.width());
			});
			return row;
		},
		
		event_sort_update : function(e, ui) 
		{	
			var data = [];	
			var i = 0;
			jQuery("tbody .svg_icon_drag").each(function()
			{				
				data[i] = $(this).data('id');
				i++;
			});					
			usam_send({action: 'update_'+USAM_Sort.page+'_sort_fields',	nonce: USAM_Sort.sort_fields_nonce,	sort_order: data});	
		}
	};
	$(USAM_Sort).bind('usam_loaded_sort', USAM_Sort.sort.event_init);
	
})(jQuery);
USAM_Sort.init();