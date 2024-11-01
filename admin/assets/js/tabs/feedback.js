(function($)
{
	$.extend(USAM_Page_feedback, 
	{	
		init : function() 
		{			
			$(function()
			{				
				USAM_Page_feedback.wrapper = $('#tab_'+USAM_Tabs.tab);
				if ( USAM_Page_feedback[USAM_Tabs.tab] !== undefined )				
					USAM_Page_feedback[USAM_Tabs.tab].event_init();			
				$('.mailing').on('click', '.js-modal', USAM_Page_feedback.open_address_book);				
			});
		},		
		
		open_address_book : function(e)
		{ 		
			$('.js_address_book').removeClass('js_address_book');
			$(this).parents('tr').find('.js_email_adress').addClass('js_address_book');				
		}						
	});	
	
	USAM_Page_feedback.contacting = 
	{		
		event_init : function() 
		{				
			USAM_Page_feedback.wrapper							
				.on('change', '.js-select-status-record', USAM_Page_feedback.contacting.change_status_event)					
				.on('click', '.js_start_performing', USAM_Page_feedback.contacting.start_performing_event);	
		},
			
		start_performing_event : function(e)
		{ 
			e.preventDefault();			
			var row    = jQuery(this).parents('tr');
			var id     = row.data('id');
			var status = jQuery(this).data('status');
			row.find(".status_title").remove();							
			row.find(".js_status_result_"+status).removeClass('hide');		
			row.find(".js_start_performing").remove();
			usam_api('contacting/'+id, {status:status}, 'POST');
		},				
		
		change_status_event : function()
		{ 
			var id        = jQuery(this).parents('tr').data('id'),		
			status = jQuery(this).val();
			usam_api('contacting/'+id, {status:status}, 'POST');
		},
	};	
			
	USAM_Page_feedback.chat = 
	{		
		event_init : function() 
		{				
			if ( $('#tab_chat table.chat').length != 0 )	
			{			
				setInterval('USAM_Page_feedback.chat.refresh_dialogs()', 20000);		
			}	
			USAM_Page_feedback.wrapper
				.on('click', 'table.chat tbody tr', USAM_Page_feedback.chat.click_dialog)
				.on('click', '.go_back', USAM_Page_feedback.chat.go_dialogs);			
			let url = new URL( document.location.href );	
			let params = new URLSearchParams(url.search);
			let sel = Number(params.get('sel'));			
			USAM_Page_feedback.chat.go_dialog( sel );		
		},
		
		go_dialogs : function(e) 
		{				
			e.preventDefault();		
			Vue.set(manager_chat, 'id', 0);
			usam_set_url_attr( 'sel', 0);
		},			
		
		click_dialog : function() 
		{				
			if ( !$(this).hasClass('current_sel') )
			{
				$('.current_sel').removeClass('current_sel');
				$(this).addClass('current_sel');					
				USAM_Page_feedback.chat.go_dialog( $(this).attr('dialog_id') );		
			}
		},	
		
		go_dialog : function( dialog_id ) 
		{
			if ( dialog_id )
			{
				$('.chat_view').addClass('dialog_open');
				usam_set_url_attr( 'sel', dialog_id);
				manager_chat.id = parseInt(dialog_id);
				manager_chat.loadDialog( dialog_id );
			}
		},
		
		refresh_dialogs : function() 
		{					
			var data = Object.assign({}, interface_filters.filtersData);	
			data.paged = $('input[name=paged]').val();
			data.status = $('input[name=status]').val();	
			USAM_Tabs.table_view( data );
		},		
	};	
			
	// Вкладка письма
	USAM_Page_feedback.email = 
	{		
		mailbox_id: 0,
		folder_id: 0,
		event_init : function() 
		{						 
			$('.list_folders')				
				.on('click','#add_folder', USAM_Page_feedback.email.display_add_folder)	
				.on('click','#read_folder', USAM_Page_feedback.email.read_folder)	
				.on('click','#open_clear_folder', USAM_Page_feedback.email.open_clear_folder)	
				.on('click','#delete_duplicate', USAM_Page_feedback.email.delete_duplicate)					
				.on('click','#remove_folder', USAM_Page_feedback.email.remove_folder);			
			
			USAM_Page_feedback.wrapper
				.on('click', '#test', USAM_Page_feedback.email.open_windows_test)	
				.on('change', '#set_folder', USAM_Page_feedback.email.change_email_folder)
				.on('change', '#sort_email', USAM_Page_feedback.email.change_sort_email);		
			
			$('#usam_template_selection')
				.on('click', '#set_template', USAM_Page_feedback.email.template_selection)
				.on('click', '#open_preview_template', USAM_Page_feedback.email.open_preview_template);
					
			$('#usam_template_editor')
				.on('click', '#open_editor_preview_mail', USAM_Page_feedback.email.open_editor_preview_mail);
						
			$('#operation_confirm').on('click', '#clear_folder', USAM_Page_feedback.email.clear_folder);	
			
			$('body').on('click', '#add_folder_window #save_action', USAM_Page_feedback.email.add_folder);		
			   
		/*	if ( $(".wp-list-table tbody tr.message_current").length )
			{			
				$(document).ready(function()
				{
					USAM_Page_feedback.email.load_message( $(".wp-list-table tbody tr.message_current") ); 
				});
			}	*/
			$(document).ready(function() 
			{     
				document.oncontextmenu = function(e)
				{			
					if ( $(e.target).hasClass("folder") || $(e.target).parent().hasClass('folder') )		
					{
						$('.menu_content.select_menu').removeClass('select_menu');						
						$(e.target).closest(".folder").find('.menu_content').toggleClass('select_menu');
						setTimeout(function () {
							var f = (e) =>{					
								if ( $(e.target).hasClass("menu_content") || $(e.target).hasClass("menu_name") || $(this).siblings('.menu_content').length ) 
									return;
								$('.menu_content').removeClass('select_menu');
								$(document).off('click', f);
							}
							$(document).on("click", f);
						}, 200);
						return false;
					}
				};	
			}); 
		},		
						
		open_clear_folder : function(e) 
		{ 
			e.preventDefault();		
			var t = $(this).closest('li.folder');
			USAM_Page_feedback.email.folder_id = t.data('folder');
			$('#operation_confirm').modal();			
		},	

		read_folder : function()
		{ 
			var t = $(this);		
			var	callback = function(response)
			{							
				var $folder = t.closest(".folder");
				$folder.find('.new_email_numbers').remove();
				if ( $folder.hasClass('folder_current') )
				{						
					$('.usam_list_table_wrapper .message_unread').removeClass('message_unread');	
				}
			};					
			usam_send({action: 'read_email_folder', 'folder_id': t.closest('li.folder').data('folder'), nonce: USAM_Page_feedback.read_email_folder_nonce}, callback);
		},	

		delete_duplicate : function()
		{ 
			var t = $(this);
			usam_send({action: 'delete_duplicate', 'folder_id': t.closest('li.folder').data('folder'), 'mailbox_id': t.closest('.folders').data('mailbox_id'), nonce: USAM_Page_feedback.delete_duplicate_nonce});
		},			
		
		clear_folder : function()
		{ 								
			var	callback = function(response)
			{											
				var $folder = $('.folders [data-folder='+USAM_Page_feedback.email.folder_id+']').closest(".folder");
				$folder.find('.new_email_numbers').remove();
				if ( $folder.hasClass('folder_current') )
				{						
					$('.usam_list_table_wrapper tbody tr').remove();	
				}
			};		
			usam_send({action: 'clear_email_folder', 'folder_id': USAM_Page_feedback.email.folder_id,	nonce: USAM_Page_feedback.clear_email_folder_nonce}, callback);
		},	
		
		remove_folder : function()
		{ 
			var t = $(this).closest('li.folder');		
			var	callback = function(response)
			{	
				var $folder = t.closest(".folder");									
				if ( $folder.hasClass('folder_current') )
				{						
					$('.usam_list_table_wrapper tbody tr').remove();	
				}
				$folder.remove();
			};					
			usam_send({action: 'remove_email_folder', 'folder_id': t.data('folder'), nonce: USAM_Page_feedback.remove_email_folder_nonce}, callback);
		},
				
		display_add_folder : function(e)
		{ 
			e.preventDefault();	
			USAM_Page_feedback.email.mailbox_id = $(this).closest('.folders').data('mailbox_id');		
			$.usam_get_modal('add_folder_window');
		},
		
		add_folder : function()
		{			
			$('#add_folder_window').modal('hide');
			var name = $('#add_folder_window #folder_name').val();	
			var callback = function(r)
			{
				usam_notifi({ 'text': USAM_Page_feedback.message_add_folder });	
				var html = '<li class="" data-folder="'+r.id+'"><a href="'+USAM_Tabs.url+'&m='+USAM_Page_feedback.email.mailbox_id+'&f='+r.slug+'">'+name+'</a></li>';
				$('#folders_mailbox_'+USAM_Page_feedback.email.mailbox_id+' ul').append( html );	
			};				
			usam_send({action: 'add_email_folder', 'name': name, 'mailbox_id': USAM_Page_feedback.email.mailbox_id,	nonce: USAM_Page_feedback.add_email_folder_nonce}, callback);
		},
		
		iframe_loaded_add_contact : function()
		{ 
			var list_iframe = $('#select_email .js_iframe').contents();	
			list_iframe.on('click', '.js-select-email', USAM_Page_feedback.email.select_email);
		},
		
		select_email : function(e)
		{ 
			e.preventDefault();				
			var email_adress = $(this).text();		
			var name_customer = $(this).parents('tr').find('.js-object-value').text();	
			if ( name_customer != '' )
				email_adress = name_customer+" <"+email_adress+">";
			
			var value = $('.js_address_book').val();
			if( value.indexOf(email_adress) == -1)
			{
				if ( value != '' )
				{
					email_adress = value + ", " + email_adress;
				}	
				$('.js_address_book').val( email_adress );
			}
		},	
		
		change_sort_email : function()
		{			
			$('#usam_table_filter_form').submit();			
		},			
		
		change_email_folder : function()
		{			
			var folder = $('#set_folder option:selected').val();				
			if ( folder == '' ) 
				return false;
			
			if ( $(".wp-list-table input:checkbox:checked").length  )
			{
				var id = $(".wp-list-table input:checkbox:checked").serializeArray();
				$(".wp-list-table input:checkbox:checked").parents('tr').remove();
			}
			else
			{
				var id = $('.wp-list-table tbody .message_current').data('id');		
				$('.wp-list-table tbody .message_current').remove();				
			}			
			$("#set_folder [value='']").attr("selected", "selected");
			 
			var	post_data   = {
				action        : 'change_email_folder',					
				'folder'      : folder,		
				'id'          : id,						
				nonce         : USAM_Page_feedback.change_email_folder_nonce
			};			
			usam_send(post_data);
		},			

		open_editor_preview_mail : function(e)
		{
			e.preventDefault();	
			usam_set_height_modal( $('#mail_template_preview') );			
			var mailtemplatepreview = tinymce.get('stylingmailtemplate').getContent();
			jQuery("#mail_template_preview_iframe").contents().find("html").html( mailtemplatepreview ); 
		},
		
		open_preview_template : function(e)
		{			
			e.preventDefault();				
			
			var t = $(this).parents('.theme');	
			var template = t.data('template');				
		
			if ( template == 'none')
				return false;
			
			usam_active_loader();			
			var callback = function(response) 
			{														
				usam_set_height_modal( $('#mail_template_preview') );			
				jQuery("#mail_template_preview_iframe").contents().find("html").html( response ); 
			};		
			usam_send({nonce: USAM_Page_feedback.get_mail_template_nonce, template: template,	action: 'get_mail_template'}, callback);		
		},
		
		template_selection : function(e)
		{ 		
			e.preventDefault();	
			var t = $(this).parents('.theme');		

			$('#usam_template_selection .themes .theme').removeClass('active');	
			t.addClass('active');				
			var template = t.data('template');	
			$('input[name="template_name"]').val( template );			

			if ( template == 'none')
			{
				tinyMCE.get('stylingmailtemplate').setContent('');
			}
			else
			{					
				usam_active_loader();
				var callback = function(response) 
				{														
					tinyMCE.get('stylingmailtemplate').setContent( response );
				};		
				usam_send({nonce: USAM_Page_feedback.get_mail_template_nonce,	template: template,	action: 'get_mail_template'}, callback);
			}
		},				
			
		open_windows_test : function() 
		{			
			$('#check_connection').modal();							
			$('#check_connection .status_connection').html(usam_get_mini_loader());
			var callback = function(response)
			{									
				$('#check_connection .status_connection').html( response );
			};				
			usam_send({action: 'test_mailbox', 'id': USAM_Tabs.id, nonce: USAM_Page_feedback.test_mailbox_nonce}, callback);	
			return false;		
		},				
	};
	
	USAM_Page_feedback.sms = 
	{		
		event_init : function() 
		{	
			$('.wp-list-table tbody').on('click','td',USAM_Page_feedback.sms.change_message);				
			USAM_Page_feedback.wrapper.on('table_update','#the-list', USAM_Page_feedback.sms.load_table_list);									
			
			var iframe = $('.email_body iframe', parent.document.body);		
			   iframe.height($(document.body).height());
			   
			if ( $(".wp-list-table tbody tr.message_current").length )
			{			
				USAM_Page_feedback.sms.load_message( $(".wp-list-table tbody tr.message_current") );  
			}	
			$('#sms_list')					
				.on('click','#menu_email_folder', USAM_Page_feedback.sms.click_menu_folder);
		},	
		
		iframe_loaded_add_contact : function()
		{ 
			var list_iframe = $('#select_phone .js_iframe').contents();	
			list_iframe.on('click','.js_select_phone',USAM_Page_feedback.sms.select_phone);
		},
		
		select_phone : function(e)
		{ 
			e.preventDefault();				
			var phone = $(this).text();					
			$('.js_address_book').val( phone );
		},	
		
		load_table_list : function( e, data )
		{
			var t = $('tr.message_current');
			USAM_Page_feedback.sms.load_message( t );		
		},
		
		click_menu_folder : function()
		{  
			$(".list_email").toggle();
			$(".list_folders").toggle();				
		},	
		
		change_message : function( e )
		{						
			if ( $(e.target).closest(".row-actions").length ) 
				return;		
		
			var t = $(this).closest('tr')
			USAM_Page_feedback.sms.load_message( t );		
			$('.wp-list-table tbody tr').removeClass("message_current");	
			t.addClass("message_current");							
			return false;
		},	

		load_message : function( t )
		{						
			var id          = t.attr('data-id'),					
				post_data   = {
					action        : 'display_sms',					
					'id'          : id,						
					 nonce        : USAM_Page_feedback.display_sms_nonce
				},
				callback = function(r)
				{				
					$('.js-sms-phone').html(r.phone);		
					$('.js-sms-date').html(r.date);						
					$('.js-sms-text').html(r.message);	
				};	
			usam_set_url_attr( 'email_id', id );				
			usam_send(post_data, callback);		
		}			
	};	
})(jQuery);	
USAM_Page_feedback.init();

function iframeLoaded() 
{
    USAM_Page_feedback.email.iframe_loaded_add_contact();
	USAM_Page_feedback.sms.iframe_loaded_add_contact();
}