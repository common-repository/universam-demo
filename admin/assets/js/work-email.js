(function($)
{
	$.extend(USAM_Work_Email, 
	{ 		
		init : function() 
		{	
			$(function()
			{										
				$('#display_email_form .wp-list-table tbody')
					.on('click', 'td', USAM_Work_Email.change_email_message)	
					.on('click', '.importance', USAM_Work_Email.click_email_importance_table)	
					.on('click', '.js-search-email', USAM_Work_Email.click_search_email_table)	
					.on('click', '.usam-remove-link', USAM_Work_Email.click_delete_email)										
					.on('click', '.usam-spam-link', USAM_Work_Email.click_spam_email);									

				$('#letter_header')				
					.on('click', '.importance', USAM_Work_Email.click_email_importance_form);							
				
				$('#usam_page_tab')					
					.on('click', '.dashicons-arrow-up-alt2.nav-active', USAM_Work_Email.click_previous_message)						
					.on('click', '.dashicons-arrow-down-alt2.nav-active', USAM_Work_Email.click_next_message)
					.on('click', '#menu_email_folder', USAM_Work_Email.click_menu_email_folder)				
					.on('click', '.js-view-email-form .go_back', USAM_Work_Email.go_back_email);
					
				$('.messaging_management')
					.on('click', '.usam-zoom-link', USAM_Work_Email.zoom_message_email)					
					.on('click', '.usam-read-link', USAM_Work_Email.click_read_message_email)		
					.on('click', '.usam-add_contact-link', USAM_Work_Email.click_add_contact_email)		
					.on('click', '.usam-not_read-link', USAM_Work_Email.click_not_read_message_email)	
					.on('click', '.usam-remove-link', USAM_Work_Email.click_delete_message_email)						
					.on('click', '#close_view_email_full_screen', USAM_Work_Email.click_close_view_email_full_screen)
					.on('click', '.attached_message', USAM_Work_Email.click_attached_message)				
					.on('table_update', '#the-list', USAM_Work_Email.load_table_list);										
				
				$("body").on('keyup',USAM_Work_Email.keypress_current_message);	
			
				if ( $('iframe#display_email_iframe').length > 0)
				{
					var iframe = $('iframe#display_email_iframe', parent.document.body);					
					var height = $(document.body).height();
					if ( $('.js-email-hcSticky').length )
					{
						var top = 40;
						if ( $(".js-fasten-toolbar").length )
							top = 75;
					
						var height_email_header = $('#letter_header').height();
						height = height -(height_email_header+63);
						$('.js-email-hcSticky').hcSticky({stickTo: '.list_email', top: top});	
					}	
					iframe.height(height); 
				}
			}); 		
		},	
		
		click_menu_email_folder : function()
		{ 
			$(".list_email").toggle();
			$(".list_folders").toggle();				
		},		
		
		iframe_loaded_add_crm : function()
		{ 
			var list_iframe = $('#select_objects .js_iframe').contents();	
			list_iframe.on('click', '.column-select a', USAM_Work_Email.add_email_crm);
		},
		
		add_email_crm : function(e) 
		{	
			e.preventDefault();					
			usam_active_loader();
			var tr = $(this).parents('tr');			
			var object_id = tr.data('customer_id');				
			var object_type = $(this).parents('.usam_tab_table').find('.js-object').val();	
			var contact_name = tr.find('.js-object-value').text();			
			var id = $('.js-letter-buttons').data('letter_id');		
			var callback = function(r) 
			{	
				$('#select_objects').modal('hide');
				$('#attached_object').html(r);
			};		
			usam_send({nonce: USAM_Work_Email.add_email_object_nonce, action: 'add_email_object', id: id,	object_id: object_id, object_type: object_type}, callback);
		},
		
		change_email_message : function( e )
		{
			if ( $(e.target).closest(".row-actions").length || $(e.target).hasClass("object_link") || $(e.target).hasClass("importance") || $(e.target).hasClass("js-search-email") ) 
				return;		
			var t = $(this).closest('tr');
			
			if ( $('body.mobile').length )
			{					
				var id          = t.attr('data-id');	
				var callback = function( response )
				{		
					$(".list_email").hide();					
					$(".js-view-email-form").html( response );	
					USAM_Work_Email.change_email_iframe_size();					
				};			
				usam_send({action: 'display_email_form', 'id': id, object_id: USAM_Tabs.id, object_type: USAM_Tabs.tab, nonce: USAM_Work_Email.display_email_form_nonce}, callback);	
			}
			else
			{							
				USAM_Work_Email.load_email_message( t );
			}				
			return false;
		},					

		load_table_list : function( e )
		{
			var t = $('tr.message_current');
			USAM_Work_Email.load_email_message( t );	
		},	
		
		change_email_iframe_size : function() 
		{		
			setTimeout( function sayHi() 
			{
				$('iframe#display_email_iframe').height( $('iframe#display_email_iframe').contents().find('html').height());
			}, 1000);
		},
				
		load_email_message : function( t )
		{				
			$('.wp-list-table tbody tr.message_current').removeClass("message_current");
			t.addClass("message_current");
			var id          = t.attr('data-id');	
			if ( id > 0 )
			{ 
				var	callback = function( response )
				{			
					$('#display_email_iframe').siblings('#circle-loader').remove();										
					USAM_Work_Email.dispaly_message_email( response );					
				};			
				usam_send({action: 'display_email_message', 'email_id': id, object_id: USAM_Tabs.id, object_type: USAM_Tabs.tab, nonce: USAM_Work_Email.display_email_message_nonce}, callback);		
				$('#display_email_iframe').siblings('#circle-loader').remove();
				$('#display_email_iframe').before(usam_get_mini_loader());
				$('#display_email_iframe').contents().find('body').html( '' );					
			}			
		},		
		
		keypress_current_message : function( e )
		{
			var code = e.keyCode ? e.keyCode : e.which;
			if (code == 38) 
			{			
				var t = $('.message_current').prev();	
				if ( t.length )
				{			
					USAM_Work_Email.load_email_message( t );
				}
			}
			else if (code == 40) 
			{								
				var t = $('.message_current').next();	
				if ( t.length )
				{			
					USAM_Work_Email.load_email_message( t );
				}
			}
		},	
				
// Предыдущее письмо		
		click_previous_message : function() 
		{				
			usam_active_loader();
			var t = $(this);			
			var id = $('#display_email_iframe').attr("data-email_id");			
			var	callback = function(response)
			{	
				if ( response.id != 0 ) 
				{
					t.siblings('.nav').addClass("nav-active");	
					$('#letter_header').html(response.header);		
					$('#display_email_iframe').attr("data-email_id",response.id);								
					$('#display_email_iframe').contents().find('body').html(response.content);
					USAM_Work_Email.change_email_iframe_size();			
					if (  response.next == 0 )
						t.removeClass("nav-active");	
					usam_set_url_attr( 'email_id', response.id );
				}
				else
					t.removeClass("nav-active");			
			};			
			usam_send({action: 'previous_email_message', 'email_id': id, object_id: USAM_Tabs.id,	object_type: USAM_Tabs.tab, nonce: USAM_Work_Email.previous_email_message_nonce}, callback);
		},
// Следующие письмо
		click_next_message : function() 
		{		
			usam_active_loader();
			var t = $(this);
			var id = $('#display_email_iframe').attr("data-email_id");			
			var	callback = function(response)
			{					
				if ( response.id != 0 ) 
				{				
					t.siblings('.nav').addClass("nav-active");	
					$('#letter_header').html(response.header);					
					$('#display_email_iframe').attr("data-email_id",response.id);						
					$('#display_email_iframe').contents().find('body').html(response.content);
					USAM_Work_Email.change_email_iframe_size();		
					if (  response.next == 0 )
						t.removeClass("nav-active");	
				}
				else
					t.removeClass("nav-active");				
			};					
			usam_send({action: 'next_email_message', 'email_id': id, object_id: USAM_Tabs.id,	object_type: USAM_Tabs.tab,	nonce: USAM_Work_Email.next_email_message_nonce}, callback);
		},
		
		click_read_message_email : function( e ) 
		{ 
			e.preventDefault();		
			var t = $(this);
			var id = $("#display_email_iframe").attr("data-email_id");
			var tr = $('.messaging_management .usam_tab_table tr[data-id="'+id+'"]');	
			tr.removeClass("message_unread");
			var	callback = function( response )
			{		
				t.parent('li').html(response);
			};		
			usam_send({action: 'not_read_message_email', 'email_id': id, nonce: USAM_Work_Email.not_read_message_email_nonce}, callback);
		},
		
		click_add_contact_email : function( e ) 
		{ 
			e.preventDefault();		
			var t = $(this);
			var	callback = function( response )
			{	
				$('#letter_header').html(response);	
			};	
			usam_send({action: 'add_contact_from_email', 'id': t.data("id"), 'email': t.data("email"), 'name': t.data("name"), nonce: USAM_Work_Email.add_contact_from_email_nonce}, callback);
		},		
		
		click_not_read_message_email : function( e ) 
		{ 
			e.preventDefault();		
			var t = $(this);
			var id = $("#display_email_iframe").attr("data-email_id");
			var tr = $('.messaging_management .usam_tab_table tr[data-id="'+id+'"]');	
			tr.addClass("message_unread");		
			
			var	callback = function(response)
			{	
				t.parent('li').html(response);
			};			
			usam_send({action: 'read_message_email', 'email_id': id, nonce: USAM_Work_Email.read_message_email_nonce}, callback);
		},				
		
		go_back_email : function( e ) 
		{
			e.preventDefault();	
			$(".list_email").show();	
			$(".js-view-email-form").html('');	
		},		
		
		zoom_message_email : function( e ) 
		{				
			e.preventDefault();							
			
			var t = $(this).closest('tr');
			if ( !t.hasClass("message_current") )
			{
				USAM_Work_Email.load_email_message( t );	
			}
			var width_fill = $('.messaging_management').width( );
			var margin_left = $('.messaging_management').offset().left;
			var width_message = $('.messaging_management #letter_header').width( );		
			
			var left = margin_left-200;
			var width = width_message+left+20;	
		
			$('.view_email_full_screen').css('position', 'absolute').css('z-index', 1000).animate({width: width, left: -left});  
			$('#close_view_email_full_screen').show();					
		},	

		click_close_view_email_full_screen : function( ) 
		{				
			$('#close_view_email_full_screen').hide();
			$('.view_email_full_screen').removeAttr('style');		
		},	
		
		click_attached_message : function(e) 
		{				
			e.preventDefault();
			var id = $(this).data('id');			
			if ( id > 0 )
			{
				usam_active_loader();							
				var	callback = function(response)
				{							
					$('#attached_email_header').html(response.header);							
					$('#attached_display_email_iframe').contents().find('body').html(response.content);					
					$('#attached_message').modal();
					$('#attached_message').on('shown.bs.modal', function (e)
					{
						var iframe = $('#attached_display_email_iframe', parent.document.body);							
						var window_top = document.getElementById('attached_display_email_iframe').getBoundingClientRect().top+document.querySelector('#attached_message').getBoundingClientRect().top*2;
						var height = jQuery(window).height()-window_top;
						iframe.height(height);
					})
				};			
				usam_send({action: 'display_email_message', 'email_id': id, nonce: USAM_Work_Email.display_email_message_nonce}, callback);
			}			
		},	
		
		dispaly_message_email : function( email ) 
		{				
			if ( email.id != 0 )
			{
				if ( $('.mobile #display_email_form').length ) 	
				{		
					$(".mobile #display_email_form").html(email.iframe);	
					$('#display_email_iframe').contents().find('body').html(email.content);	
					USAM_Work_Email.change_email_iframe_size();			
				}
				else						
				{ 					
					$('#letter_header').html(email.header);							
					$('#display_email_iframe').contents().find('body').html(email.content);		
				}					
				usam_set_url_attr( 'email_id', email.id );	
				$('#display_email_iframe').attr("data-email_id", email.id);						
			}					
		},	
		
		click_delete_email : function(e)
		{		
			e.preventDefault();		
			var id = $(this).parents('tr').data('id');						
			USAM_Work_Email.delete_message_email( id );			
		},	
		
		click_delete_message_email : function( e ) 
		{ 
			e.preventDefault();
			var id = $("#display_email_iframe").attr("data-email_id");	
			USAM_Work_Email.delete_message_email( id );	
		},	
		
		delete_message_email : function( id ) 
		{ 				
			var change_letter = true;
			if ( $(".messaging_management .usam_tab_table tr").length )
			{				
				change_letter = false;
				var tr = $('.messaging_management .usam_tab_table tr[data-id="'+id+'"]');		
				if ( tr.hasClass("message_current") )
				{									
					var t = $('.message_current').next();			
					USAM_Work_Email.load_email_message( t );
				}
				tr.remove();
			}									
			var	callback = function(response)
			{	
				if ( response.id && change_letter ) 
				{		
					USAM_Work_Email.dispaly_message_email( response );
				}
			};				
			usam_send({action: 'delete_message_email', 'email_id': id, object_id: USAM_Tabs.id, object_type: USAM_Tabs.tab, nonce: USAM_Work_Email.delete_message_email_nonce}, callback);		
			usam_notifi({ 'text': UNIVERSAM.item_delete_text });				
		},			
				
		click_spam_email : function(e)
		{		
			e.preventDefault();		
			var row = $(this).parents('tr');			
			var id = row.data('id');			
			if ( row.hasClass('message_current') )
			{					
				var t = row.next('tr');							
				USAM_Work_Email.load_email_message( t );	
			}
			row.remove();
			usam_send({action: 'spam_email', 'id': id, nonce: USAM_Work_Email.spam_email_nonce});		
		},
		
		click_email_importance_table : function()
		{ 
			var t = $(this);
			var id = t.closest('tr').attr('data-id');	
			USAM_Work_Email.change_email_importance( t, id );
		},	
		
		click_search_email_table : function( e )
		{
			interface_filters.search = $(this).attr('email');
			interface_filters.requestData({s:interface_filters.search});
		},	
					
		click_email_importance_form : function()
		{ 
			var t = $(this);
			var id = t.closest('.display_email').find('#display_email_iframe').data('email_id');	
			USAM_Work_Email.change_email_importance( t, id );
		},	
		
		change_email_importance : function( t, id )
		{ 
			if ( id > 0 )
			{				
				var important = 1;
				if ( t.hasClass('important') )
					important = 0;				
				t.toggleClass('important');	
				if ( important )
					t.removeClass('dashicons-star-empty').addClass('dashicons-star-filled');
				else
					t.removeClass('dashicons-star-filled').addClass('dashicons-star-empty');
				usam_send({action: 'change_importance_email',	'id': id,'importance': important, nonce: USAM_Work_Email.change_importance_email_nonce});
			}
		},	
	});	
})(jQuery);	
USAM_Work_Email.init();

function email_iframeLoaded() 
{
	USAM_Work_Email.iframe_loaded_add_crm();
}