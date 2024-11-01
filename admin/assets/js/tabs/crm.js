(function($)
{
	$.extend(USAM_Page_crm, 
	{	
		init : function() 
		{					
			$(function()
			{
				USAM_Page_crm.wrapper = $('.tab_'+USAM_Tabs.tab);						
				if ( USAM_Page_crm[USAM_Tabs.tab] !== undefined )				
					USAM_Page_crm[USAM_Tabs.tab].event_init();	
			});						
		},		
		
		find_duplicates : function() 
		{		
			usam_active_loader();
			data = {}
			if ( $('#find_duplicate_name').prop('checked') )
				data.name = 1;
			if ( $('#find_duplicate_phone').prop('checked') )
				data.phone = 1;
			if ( $('#find_duplicate_email').prop('checked') )
				data.email = 1;
			if ( $('#find_duplicate_user').prop('checked') )
				data.user = 1;		
			USAM_Tabs.table_view( data );
			$('#button-combine').removeClass('hide');	
		},
		
		combine_duplicates : function() 
		{		
			var duplicate = {}		
			var ok = false;
			$('.wp-list-table tbody .check-column input:checkbox:checked').each(function()
			{
				ok = true;
				$(this).removeAttr("checked");
				val = $(this).val();				
				duplicate[val] = [];
				i = 0;
				$('input[name="duplicate_'+val+'"]').each(function()
				{					
					duplicate[val][i] = $(this).val();
					i++;
				});					
			});	
			if ( ok )
			{
				usam_active_loader();				
				usam_send({nonce: USAM_Page_crm.combine_duplicate_nonce, action: 'combine_duplicate', table: USAM_Tabs.table, cd: duplicate}, USAM_Page_crm.find_duplicates);	
			}
		},
						
		open_task_windows : function(e) 
		{ 
			e.preventDefault();					
			var event_type = 'task';
			if ( $(this).hasClass('usam-add_meeting-link') )
				event_type = 'meeting';
			else if ( $(this).hasClass('usam-add_phone-link') )
				event_type = 'call';
			
			let url = new URL( document.location.href );				
					
			var object_type = 'company'; 
			var object_id = 0;
			if ( $('.element_form').length > 0 )
			{
				object_id = url.searchParams.get('id');
				object_type = url.searchParams.get('form_name');			
			}
			else
			{
				var row = $(this).closest('tr');
				object_id = row.data('customer_id');					
				var company_name = row.find('.js-object-value').text();	
				if ( USAM_Tabs.tab == 'contacts' )
				{
					title =  USAM_Page_crm.add_contact_text;	
					object_type = 'contact';
				}
				else
				{
					title =  USAM_Page_crm.add_company_text;
					object_type = 'company';
				}
				Vue.set(new_event, 'customer', {title:title,name:company_name});				
			}			
			Vue.set(new_event.event, 'links', [{object_id:object_id,object_type:object_type}]);
			Vue.set(new_event.event, 'type', event_type);		
			new_event.show_modal();				
		},	
		
		delete_row_means_communication : function( e ) 
		{				
			e.preventDefault();
			var row = $(this).closest('tr').remove();			
		},		
				
		add_row_means_communication : function( e ) 
		{				
			e.preventDefault();
			var row = $(this).closest('tr').prev();
			var html = row.html();			
			row.before('<tr>'+html+'</tr>');
		},	
		
		open_group_mailing : function(e) 
		{
			e.preventDefault();		
			var value = $(this).val();
			if ( value == 'list' )
			{ 
				var modal_id = 'subscriber_list_management';
				$('html').on('loading_modal', 'body', function(){ usam_active_loader(); });	
				$.usam_get_modal( modal_id );
				$( "body" ).on( "append_modal", function(e)
				{				
					$(".chzn-select").chosen({ width: "200px" });	
					var title = '';		
					var row = '';						
					$('.wp-list-table tbody .check-column input:checkbox:checked').each(function()
					{
						title = $(this).closest('tr').find('.row-title').html();
						row = row+ "<li>"+title+"</li>";			
					});
					if ( row != '' )
					{
						$('#'+modal_id+' .selected_items').html('<ul>'+row+'</ul>');
					}
					$("#"+modal_id).on('click', '#modal_action', USAM_Page_crm.change_subscriber_list);
				});	
			}		
		},	
		
		change_subscriber_list : function( ) 
		{ 
			usam_active_loader();		
			var ids = [];	
			var i = 0;
			$('.wp-list-table tbody .check-column input:checkbox:checked').each(function(){
				ids[i] = $(this).val();
				i++;
			});		
			var types = [];
			i = 0;
			$('#subscriber_type :selected').each(function(){
				types[i] = $(this).val();
				i++;				
			});
			var lists=[];
			i = 0;
			$('#subscriber_list :selected').each(function(){
				lists[i] = $(this).val();
				i++;
			});					
			var data = {
				nonce   : USAM_Page_crm.change_subscriber_list_nonce,
				action  : 'change_subscriber_list',		
				ids     : ids,	
				types   : types,
				lists   : lists,				
				tab     : USAM_Tabs.tab,
				operation : $('#subscriber_list_management #subscriber_operation').val(),					
			};
			var callback = function( response )
			{			
				$('#subscriber_list_management').modal('hide');			
			}; 
			usam_send(data, callback);			
		},
	});
		
	//Вкладка "контакты"	
	USAM_Page_crm.contacts = 
	{		
		not_send_form : true,		
		event_init : function() 
		{				
			USAM_Page_crm.wrapper
				.on('click', '#usam_means_communication table #add_row', USAM_Page_crm.add_row_means_communication)
				.on('click', '#usam_means_communication table tr .button_delete', USAM_Page_crm.delete_row_means_communication)
				.on('click', '.usam-add_meeting-link', USAM_Page_crm.open_task_windows)
				.on('click', '.usam-add_phone-link', USAM_Page_crm.open_task_windows)
				.on('click', '.usam-add_task-link', USAM_Page_crm.open_task_windows)	
				.on('change', '#bulk-action-selector-top', USAM_Page_crm.open_group_mailing)	
				.on('click', '#find-duplicates', USAM_Page_crm.find_duplicates)
				.on('click', '#button-combine', USAM_Page_crm.combine_duplicates);
		}
	};	
	
	// Вкладка "Компании"	
	USAM_Page_crm.companies = 
	{		
		not_send_form : true,		
		event_init : function() 
		{	
			USAM_Page_crm.wrapper
				.on('click', '#usam_means_communication table #add_row', USAM_Page_crm.add_row_means_communication)
				.on('click', '#usam_means_communication table tr a.button_delete', USAM_Page_crm.delete_row_means_communication)
				.on('click', '.usam-add_meeting-link', USAM_Page_crm.open_task_windows)
				.on('click', '.usam-add_phone-link', USAM_Page_crm.open_task_windows)
				.on('click', '.usam-add_task-link', USAM_Page_crm.open_task_windows)								
				.on('change', '#bulk-action-selector-top', USAM_Page_crm.open_group_mailing)
				.on('click', '#find-duplicates', USAM_Page_crm.find_duplicates)
				.on('click', '#button-combine', USAM_Page_crm.combine_duplicates);				
		}
	};		
})(jQuery);	
USAM_Page_crm.init();