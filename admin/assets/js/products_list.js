(function($)
{
	$.extend(USAM_Products, 
	{ 		
		init : function() 
		{		
			$(function()
			{ 
				document.querySelector('.tablenav.top').prepend( document.querySelector('#post_filters') );
				$('.post-type-usam-product')					
					.on('click', '.tablenav-pages a', USAM_Products.table_navigation )
					.on('click', '.manage-column.sortable a, .manage-column.sorted a', USAM_Products.table_sorting )
					.on('keyup', '.tablenav-pages input[name=paged]', USAM_Products.table_select_page_number );
			});	
		},		
		list_table_query( url, variable ) 
		{
			var vars = usam_get_url_attrs( url );
			if ( typeof vars[variable] !== typeof undefined ) 
				return vars[variable];
			return false;
		},
		table_select_page_number(e) 
		{	
			if ( 13 == e.which )
			{
				e.preventDefault();
				USAM_Products.table_view({paged: parseInt($(this).val()) || '1'});
			}
		},		
		table_sorting( e ) 
		{ 
			usam_active_loader();
			e.preventDefault();	
			var	data = Object.assign({}, interface_filters.filtersData);			
			var query = e.currentTarget.getAttribute('href');			
			var $table = $(this).parents('#posts-filter');
			if( $table.find('input[name=paged]').length )
				data.paged = parseInt($table.find('input[name=paged]').val());				
			data.order = USAM_Products.list_table_query( query, 'order' ) || 'asc';
			data.orderby = USAM_Products.list_table_query( query, 'orderby' ) || 'title';	
			$table.find('input[name=order]').val(data.order);
			$table.find('input[name=orderby]').val(data.orderby);			
			USAM_Products.table_view( data, $table );
		},		
		table_navigation( e ) 
		{ 
			e.preventDefault();
			usam_active_loader();		
			var	data = Object.assign({}, interface_filters.filtersData);
			var $table = $(this).parents('.tablenav-pages');
			if ( $(this).hasClass('last-page') )
				data.paged = parseInt($table.find('.total-pages').html().replace(/\D/g,''));
			else if ( $(this).hasClass('first-page') )
				data.paged = 1;	
			else if ( $(this).hasClass('prev-page') )
			{	
				data.paged = parseInt($table.find('input[name=paged]').val());
				if( data.paged > 1 )
					data.paged--;				
			}
			else if ( $(this).hasClass('next-page') )
			{	
				data.paged = parseInt($table.find('input[name=paged]').val());
				data.paged++;				
			}
			else
			{
				var query = this.search.substring( 1 );				
				data.paged = USAM_Products.list_table_query( query, 'paged' ) || 1;			
			}
			USAM_Products.table_view( data, $table );			
		},		
		table_view( d ) 
		{
			var $table = $('#posts-filter');
			var	data = Object.assign({}, d);			
			var post_status = USAM_Products.list_table_query( location.href, 'post_status' );
			if ( post_status )
				data.post_status = post_status;
			for (key in data) 
			{					
				if ( key != 'undefined' )
					usam_set_url_attr( key, data[key] );	
			}		
			usam_active_loader();					
			data.order = $table.find('input[name=order]').val();			
			data.orderby = $table.find('input[name=orderby]').val();
			data.action = 'get_products_table';					
			data.nonce  = USAM_Products.get_products_table;
			data.post_type = 'usam-product';
			data.screen_id = USAM_Products.screen_id;	
			usam_send(data, (r) =>
			{
				if ( typeof r.rows !== typeof undefined )
				{					
					jQuery('#the-list').html( r.rows );
					jQuery('#the-list').trigger('table_update',[r]);
					usam_lazy_image();
					if ( r.column_headers.length )
					jQuery('.wp-list-table thead tr').html( r.column_headers );					
					if ( r.column_footer.length )
						jQuery('.wp-list-table tfoot tr').html( r.column_footer );					
					if ( r.pagination.top.length )
						jQuery('.tablenav.top .tablenav-pages').html( jQuery(r.pagination.top).html() );
					if ( r.pagination.bottom.length )
						jQuery('.tablenav.bottom .tablenav-pages').html( jQuery(r.pagination.bottom).html() );	
					jQuery(".product_rating").rating({'selected': usam_save_product_rating });
				} 				
			}, 'GET');
		},	
	});	
})(jQuery);	
USAM_Products.init();


jQuery(document).ready(function($)
{	
	$('#the-list').on('click', '.editinline', function() 	
	{
		inlineEditPost.revert();
		var post_id = $( this ).closest( 'tr' ).attr( 'id' );
		post_id = post_id.replace( 'post-', '' );
		var sku = $('#product_sku-' + post_id).text();
		$( 'input[name="sku"]', '.inline-edit-row' ).val( sku );
	});		 
	var script = usam_addScript('rating.js');
	script.onload = function() 
	{
		jQuery(".product_rating").rating({'selected': usam_save_product_rating });
	}	
	if ( $('.wrap a.page-title-action').length )
	{ 
		if ( typeof USAM_Admin.action_urls !== typeof undefined )
		{
			var $action = $( '.wrap a.page-title-action:first' );			
			if ( typeof USAM_Admin.action_urls.export_products !== typeof undefined ) {
				$action.after('<a href="' + USAM_Admin.action_urls.export_products + '" class="page-title-action">' + USAM_Admin.action_message.export_products + '</a>');
			}
			if ( typeof USAM_Admin.action_urls.import_products !== typeof undefined ) {
				$action.after( '<a href="' + USAM_Admin.action_urls.import_products + '" class="page-title-action">' + USAM_Admin.action_message.import_products + '</a>' );
			}
		}
		if ( USAM_Admin.usam_multisite )
			$('.page-title-action').remove();
	}	
	
	$(document).on('click','#delete_all', function(e)
	{			
		e.preventDefault();			
		var vars = usam_get_url_attrs( location.href );									
		if ( typeof vars['post_type'] !== typeof undefined) 
		{							
			usam_send({nonce: USAM_Admin.bulkactions_nonce, action: 'bulkactions', a: 'empty_trash', item: 'posts', post_type: vars['post_type']});
		}
	});	
	$(document).on('click','.js-featured-product-toggle', function(e)
	{			
		e.preventDefault();
		var t = $(this),
			id = t.parents('tr').attr('id').split('-');			
		usam_api('list/post', {list:'sticky', post_id: id[1]}, 'POST', (r) => { 
			if ( r == 'deleted' )
				t.removeClass('list_selected');			
			else if ( r == 'add' )				
				t.addClass('list_selected');
		})		
	});
	
	var images_file_frame;	
	
	jQuery('body').on('change','#bulk-action-selector-top, #bulk-action-selector-bottom', function(e) 
	{
		var modal_id = '';		
		switch ( jQuery(this).val() ) 
		{
			case 'category' :			
			case 'brand' :			
			case 'category_sale' :				
				modal_id = 'bulk_actions_terms';									
			break;
			case 'product_attribute' :				
				modal_id = 'bulk_actions_product_attribute';	
			break;
			case 'system_product_attribute' :				
				modal_id = 'bulk_actions_system_product_attribute';		
			break;		
			case 'product_thumbnail' :					
				var product_ids = [];
				var i = 0;
				jQuery('.wp-list-table tbody .check-column input:checkbox:checked').each(function()
				{
					product_ids[i] = jQuery(this).val();
					i++;
				});									
				images_file_frame = wp.media.frames.images_file_frame = wp.media({
					title    : jQuery('.wp-heading-inline').text(),
					library  : { type: 'image' },
					multiple : false
				});		
				wp.media.frames.images_file_frame.on('open', function() 
				{			
					var selection = wp.media.frames.images_file_frame.state().get( 'selection' );
				});				
				images_file_frame.on('select', function() 
				{									
					usam_active_loader();
					attachment = images_file_frame.state().get( 'selection' ).first().toJSON();										
					var url = attachment.sizes.thumbnail.url;		
					usam_send({action: 'set_products_thumbnail', product_ids: product_ids, attachment_id: attachment.id, nonce: USAM_Admin.set_products_thumbnail_nonce}, (r) =>
					{				
						jQuery('.wp-list-table .check-column input:checkbox:checked').removeAttr("checked");
						jQuery('.loader__full_screen').remove();
					});	
				}); 
				images_file_frame.open();
			break;			
		}			
		var product_title = '';		
		var row = '';						
		jQuery('.wp-list-table tbody .check-column input:checkbox:checked').each(function()
		{
			product_title = jQuery(this).closest('tr').find('.product_title_link').html(); 
			row = row+ "<li>"+product_title+"</li>";				
		});
		if ( modal_id != '' )
		{			
			jQuery('html').on('loading_modal', 'body', function(){ usam_active_loader(); });	
			jQuery.usam_get_modal( modal_id );
			jQuery( "body" ).on( "append_modal", function(e)
			{				
				jQuery('.loader__full_screen').remove();				
				if ( row != '' )
				{
					jQuery('#'+modal_id+' .colum2 .products').html('<ul></ul>');
					jQuery('#'+modal_id+' .colum2 .products ul').append( row );		
				}
				jQuery(".chzn-select").chosen({width: '100%'});	
			});	
		}
	});		
			
	$('body').on('click', '#bulk_actions_system_product_attribute #modal_action', function() 
	{						
		var product_attribute = [];		
		var val = '';
		var i = 0;	
		$('#bulk_actions_system_product_attribute .system_characteristics select').each(function()
		{
			val = $(this).val();
			if ( val != '' )
			{ 
				product_attribute[i] = {value: val, key: $(this).attr('id') };
				$(this).prop('selectedIndex',0);
				i++;
			}
		});	
		$('#bulk_actions_system_product_attribute .system_characteristics input').each(function()
		{
			val = $(this).val();
			if ( val != '' )
			{ 
				product_attribute[i] = {value: val, key: $(this).attr('id') };
				i++;
			}
		});
		if ( product_attribute.length )
		{
			$('#bulk_actions_system_product_attribute').modal('hide');
			usam_active_loader();
			var data = interface_filters.get_filters_bulk_actions();			
			data = Object.assign(data, {action: 'bulk_actions_system_product_attribute', attributes: product_attribute, nonce: USAM_Admin.bulk_actions_system_product_attribute});	
			usam_send( data );
		}
	});			
	
	$('body').on('click', '#bulk_actions_product_attribute #modal_action', function() 
	{			
		var product_attribute = [];
		var i = 0;	
		$("#bulk_actions_product_attribute .product_attributes .change_made").each( function()
		{				
			product_attribute[i] = {value: $(this).val(), slug: $(this).data('slug') };
			$(this).removeClass("change_made");	
			i++;
		});			
		if ( product_attribute.length )
		{
			$('#bulk_actions_product_attribute').modal('hide');
			usam_active_loader();
			var data = interface_filters.get_filters_bulk_actions();
			data = Object.assign(data, {action: 'bulk_actions_product_attribute', attributes: product_attribute, nonce: USAM_Admin.bulk_actions_product_attribute});	
			usam_send( data );				
		}
	});			
	
	$('body').on('click','#bulk_actions_terms #modal_action',function() 
	{			
		var terms = {};
		let category = $('select#category').val();
		let brands = $('select#brands').val();
		let category_sale = $('select#category_sale').val();
		let catalog = $('select#catalog').val();
		let selection = $('select#selection').val();
		if ( category )
			terms.category = parseInt(category);
		if ( brands )
			terms.brands = parseInt(brands);
		if ( category_sale )
			terms.category_sale = parseInt(category_sale);
		if ( catalog )
			terms.catalog = parseInt(catalog);
		if ( selection )
			terms.selection = parseInt(selection);
		if ( Object.keys(terms).length )
		{
			var data = interface_filters.get_filters_bulk_actions();
			data = Object.assign(data, {nonce: USAM_Admin.bulk_actions_terms, action: 'bulk_actions_terms', operation: $('select#operation').val(), terms: terms});	
			usam_send( data );
			$('#bulk_actions_terms').modal('hide');
			usam_active_loader();	
		}
	});	
	
	setTimeout(bulkedit_edit_tags_hack, 1000);	
});

function usam_save_product_rating( rating, t) 
{
	var $product_rating = t.parent('.product_rating');
	var product_id = $product_rating.data('product_id');	
	usam_api('product/'+product_id+'/rating', {rating:rating}, 'POST', (r)=>
	{ 
		jQuery('#vote_total_'+product_id).text( r.rating_count );		
		var i = 0;
		$product_rating.find(".star").each( function()
		{			
			if ( i < r.rating )
				jQuery(this).addClass('selected');
			else
				jQuery(this).removeClass('selected');
			i++;
		});		
	});
}

// inline-edit-post.dev.js prepend tag edit textarea into the last fieldset. We need to undo that
function bulkedit_edit_tags_hack() {
	jQuery('<fieldset class="inline-edit-col-right"><div class="inline-edit-col"></div></fieldset>').insertBefore('#bulk-edit .usam-cols:first').find('.inline-edit-col').append(jQuery('#bulk-edit .inline-edit-tags'));
}