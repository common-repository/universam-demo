(function($)
{
	$.extend(USAM_Page_social_networks, 
	{
		send:false,
		init : function() 
		{					
			$(function()
			{						
				USAM_Page_social_networks.wrapper = $('.tab_'+USAM_Tabs.tab);						
				if ( USAM_Page_social_networks[USAM_Tabs.tab] !== undefined )				
					USAM_Page_social_networks[USAM_Tabs.tab].event_init();
			});
		},	

		add_modal : function( modal_id, callback ) 
		{		
			var data = { 'profile_id' : $("#profile_id").val() };		
			$('html').on('loading_modal', 'body', function(){ usam_active_loader(); });	
			$.usam_get_modal(modal_id, data );
			$( "body" ).on( "append_modal", function(e)
			{				
				$('.loader__full_screen').remove();						
				var title = '';		
				var row = '';						
				$('.wp-list-table tbody .check-column input:checkbox:checked').each(function()
				{						
					title = $(this).closest('tr').find('.column-primary .product_title_link').text();								
					row = row+ "<li>"+title+"</li>";
				});
				if ( row != '' )
				{
					$('#'+modal_id+' .selected_items').html('<ul>'+row+'</ul>');
				}
				$(".chzn-select").chosen({ width: "200px" });
				$("#"+modal_id).on('click', '#modal_action', callback);
			});
		},
				
		publish_content : function( modal_id, post_data ) 
		{	
			if ( !USAM_Page_social_networks.send )
			{
				var content = [];	
				var i = 0;			
				$('.wp-list-table tbody .check-column input:checkbox:checked').each(function(){
					content[i] = $(this).val();
					i++;
				});
				post_data.ids = content;	
				USAM_Page_social_networks.send = true;	
				$('#'+modal_id+'').modal('hide');			
				usam_send(post_data, (r) => USAM_Page_social_networks.send = false);
			}
		},
		
	});
	
	/**
	 * Вкладка "Управление публикацией вКонтакте"	
	 */
	USAM_Page_social_networks.vk_products = 
	{		
		event_init : function() 
		{						
			USAM_Page_social_networks.wrapper
				.on('change', '#bulk-action-selector-top', USAM_Page_social_networks.vk_products.change_bulk_action);							
		},
				
		change_bulk_action : function() 
		{						
			switch ( $(this).val() ) 
			{				
				case 'add_post' :					
					if ( $("table.vk_products").length )
						modal_id = 'vk_product_publication_wall';				
					else
						modal_id = 'vk_post_publication_wall';							
					
					USAM_Page_social_networks.add_modal( modal_id, USAM_Page_social_networks.vk_products.publish_posts );
				break;
				case 'add_product' :												
					USAM_Page_social_networks.add_modal( 'vk_product_publication', USAM_Page_social_networks.vk_products.publish_product );
				break;
				case 'add_image' :												
					USAM_Page_social_networks.add_modal( 'vk_image_publication_album', USAM_Page_social_networks.vk_products.publish_image );
				break;				
			}					
		},	
		
		publish_posts : function( )	
		{	
			if ( $("table.vk_products").length )
				modal_id = 'vk_product_publication_wall';				
			else
				modal_id = 'vk_post_publication_wall';				
			var date = $('#'+modal_id+'  #usam_date_picker-current_time').val();
			if ( date != '' )
				date = date + " "+$('#'+modal_id+' #date_time-current_time').val();
	
			var data = {
				nonce              : USAM_Page_social_networks.add_posts_vk_nonce,	
				action             : 'add_posts_vk',			
				date               : date,	
				place_sale         : $('#'+modal_id+' #place_sale').val(),		
				market             : $('#'+modal_id+' #vk_add_market').val(),	
				link               : $('#'+modal_id+' #vk_add_link').val(),	
				services           : $('#'+modal_id+' #vk_services').val(),
				message_format     : $('#'+modal_id+' #message_format').val(),		
				profile_id 		   : $('#'+modal_id+' select#profiles').val(),	
			};				
			USAM_Page_social_networks.publish_content( modal_id, data );				
		},	
		
		publish_product : function() 
		{	
			var modal_id = 'vk_product_publication';				
			var category_id = $('#'+modal_id+' select#category').val();			
			if ( category_id == '' )
			{
				$('.colum-category label').css('color', '#DC143C');
				return false;
			}
			var data = {
				nonce              : USAM_Page_social_networks.add_products_vk_nonce,	
				action             : 'add_products_vk',		
				album_id           : $('#'+modal_id+' select#market_album').val(),					
				group_id 		   : $('#'+modal_id+' select#profiles').val(),	
				category_id        : category_id,				
			};		
			USAM_Page_social_networks.publish_content( modal_id, data );	
		},	
					
		publish_image : function() 
		{	
			var modal_id = 'vk_image_publication_album';				
			var category_id = $('#'+modal_id+' select#category').val();			
			if ( category_id == '' )
			{
				$('.colum-category label').css('color', '#DC143C');
				return false;
			}
			var thumb_ids = []; 
			$('#'+modal_id+' input:checked.input-checkbox').each(function() { 
                thumb_ids.push($(this).val()); 
            }); 
			var data = {
				nonce              : USAM_Page_social_networks.add_image_vk_nonce,	
				action             : 'add_image_vk',		
				thumb_ids          : thumb_ids,	
				profile_id 		   : $('#'+modal_id+' #profile_id').val(),			
			};	
			USAM_Page_social_networks.publish_content( modal_id, data );	
		},	
	};	
	
	USAM_Page_social_networks.facebook = 
	{		
		event_init : function() 
		{						
			USAM_Page_social_networks.wrapper
				.on('change','#bulk-action-selector-top', USAM_Page_social_networks.facebook.change_bulk_action);							
		},
				
		change_bulk_action : function() 
		{						
			switch ( $(this).val() ) 
			{				
				case 'add_post' :				
					if ( $("table.fb_products").length )
						modal_id = 'fb_product_publication_wall';				
					else
						modal_id = 'fb_post_publication_wall';							
					
					USAM_Page_social_networks.add_modal( modal_id, USAM_Page_social_networks.facebook.publish_posts );
				break;
				case 'add_product' :												
					USAM_Page_social_networks.add_modal( 'fb_product_publication', USAM_Page_social_networks.facebook.publish_product );
				break;							
			}					
		},	
		
		publish_posts : function( )	
		{	
			if ( $("table.fb_products").length )
				modal_id = 'fb_product_publication_wall';				
			else
				modal_id = 'fb_post_publication_wall';				
			var date = $('#'+modal_id+'  #usam_date_picker-current_time').val();
			if ( date != '' )
				date = date + " "+$('#'+modal_id+' #date_time-current_time').val();
	
			var data = {
				nonce              : USAM_Page_social_networks.add_posts_fb_nonce,	
				action             : 'add_posts_fb',			
				date               : date,	
				place_sale         : $('#'+modal_id+' #place_sale').val(),		
				market             : $('#'+modal_id+' #fb_add_market').val(),	
				link               : $('#'+modal_id+' #fb_add_link').val(),	
				services           : $('#'+modal_id+' #fb_services').val(),
				message_format     : $('#'+modal_id+' #message_format').val(),		
				profile_id 		   : $('#'+modal_id+' select#profiles').val(),	
			};				
			USAM_Page_social_networks.publish_content( modal_id, data );				
		},	
		
		publish_product : function() 
		{	
			var modal_id = 'fb_product_publication';				
			var category_id = $('#'+modal_id+' select#category').val();			
			if ( category_id == '' )
			{
				$('.colum-category label').css('color', '#DC143C');
				return false;
			}
			var data = {
				nonce              : USAM_Page_social_networks.add_products_fb_nonce,	
				action             : 'add_products_fb',		
				album_id           : $('#'+modal_id+' select#market_album').val(),					
				group_id 		   : $('#'+modal_id+' select#profiles').val(),	
				category_id        : category_id,				
			};		
			USAM_Page_social_networks.publish_content( modal_id, data );	
		},		
	};
	/**
	 * Вкладка "Управление публикацией в Одноклассниках"	
	 */
	USAM_Page_social_networks.ok_products = 
	{		
		event_init : function() 
		{						
			USAM_Page_social_networks.wrapper
				.on('change', '#bulk-action-selector-top', USAM_Page_social_networks.ok_products.add_modal);							
		},
				
		add_modal : function() 
		{						
			switch ( $(this).val() ) 
			{				
				case 'add_post' :					
					if ( $("table.ok_products").length )
						modal_id = 'ok_product_publication_wall';				
					else
						modal_id = 'ok_post_publication_wall';							
					USAM_Page_social_networks.add_modal( modal_id, USAM_Page_social_networks.ok_products.publish_posts );
				break;				
			}					
		},	
		
		publish_posts : function() 
		{				
			if ( $("table.ok_products").length )
				modal_id = 'ok_product_publication_wall';				
			else
				modal_id = 'ok_post_publication_wall';		
			
			var post_data = {
				nonce              : USAM_Page_social_networks.add_posts_ok_nonce,	
				action             : 'add_posts_ok',	
				link               : $('#'+modal_id+' #add_link').val(),	
				message_format     : $('#'+modal_id+' #message_format').val(),		
				profile_id 		   : $('#'+modal_id+' select#profiles').val(),	
			};				
			USAM_Page_social_networks.publish_content( modal_id, post_data );				
		},		
	};	
})(jQuery);	
USAM_Page_social_networks.init();