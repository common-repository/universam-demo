(function($)
{
	$.extend(USAM_Work_Manager, 
	{			
		init : function() 
		{					
			$(function()
			{ 
				USAM_Work_Manager.wrapper = $('.tab_'+USAM_Tabs.tab);
				
				USAM_Work_Manager.wrapper							
					.on('change', '.js-select-status-record', USAM_Work_Manager.change_status_event)					
					.on('click', '.js_start_performing', USAM_Work_Manager.start_performing_event)					
					.on('click', '.wp-list-table #event_importance', USAM_Work_Manager.table_change_event_importance)
					.on('click', '.grid_view #event_importance', USAM_Work_Manager.grid_view_change_event_importance)	
					.on('change', '#bulk-action-selector-top', USAM_Work_Manager.open_change_task_participants)
					.on('submit', '#element_editing_form ', USAM_Work_Manager.submit_form);					

				$('#my_tasks_participants')
					.on('click', '#modal_action', USAM_Work_Manager.change_task_participants);							
			});						
		},		

		submit_form : function(  ) 
		{ 
			var status = $(".js-status").val();			
			if ( status == 'completed' )
			{ 
				var $request_solution = $("[name='request_solution']");
				if ( $request_solution.val() == '' )	
				{
					$request_solution.addClass('highlight');
					return false;
				}
			}
			return true;
		},	
						
		grid_view_change_event_importance : function()
		{ 
			var id = $(this).parents('.grid_view__item').data('id');			
			var importance = 0;
			if ( $(this).hasClass('dashicons-star-empty') )
			{							
				importance = 1;
				$(this).removeClass('dashicons-star-empty').addClass('dashicons-star-filled');
			}
			else
			{								
				$(this).removeClass('dashicons-star-filled').addClass('dashicons-star-empty');
			}		
			usam_api('event/'+id, {importance:importance}, 'POST');
		},		
		
		table_change_event_importance : function()
		{ 
			var id = $(this).parents('tr').data('id');			
			var importance = 0;
			if ( $(this).hasClass('dashicons-star-empty') )
			{							
				importance = 1;
				$(this).removeClass('dashicons-star-empty').addClass('dashicons-star-filled');
			}
			else
			{								
				$(this).removeClass('dashicons-star-filled').addClass('dashicons-star-empty');
			}						
			usam_api('event/'+id, {importance:importance}, 'POST');
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
			usam_api('event/'+id, {status:status}, 'POST');
		},				
		
		change_status_event : function()
		{ 
			var id        = jQuery(this).parents('tr').data('id'),		
			status = jQuery(this).val();
			usam_api('event/'+id, {status:status}, 'POST');
		},
		
		open_change_task_participants : function()
		{ 
			switch ( $(this).val() ) 
			{
				case 'participants' :				
					$(".chzn-select").chosen({ width: '100%' });	
					var title = '';		
					var row = '';						
					$('.wp-list-table tbody .check-column input:checkbox:checked').each(function()
					{
						title = $(this).closest('tr').find('.row-title').html();
						row = row+ "<li>"+title+"</li>";
					});
					if ( row != '' )
					{
						$('#my_tasks_participants .selection').html('<ul></ul>');
						$('#my_tasks_participants .selection ul').append( row );		
					}
					$('#my_tasks_participants').modal();									
				break;				
			}				
		},	
		
		change_task_participants : function( ) 
		{
			var tasks = [];	
			var i = 0;
			$('.wp-list-table tbody .check-column input:checkbox:checked').each(function(){
				tasks[i] = $(this).val();
				i++;
			});				
			
			var tab = $('.usam_view_form_tabs .header_tab .current').data('tab');
			var data = {
				nonce   : USAM_Work_Manager.change_task_participants_nonce,
				action  : 'change_task_participants',		
				ids     : tasks,		
				user_id : $('#my_tasks_participants #users').val(),	
				operation : $('#my_tasks_participants #operation').val(),	
			};
			var callback = function( response )
			{	
				$('#my_tasks_participants').modal('hide');				
			};
			usam_send(data, callback);			
		},
	});	
})(jQuery);	
USAM_Work_Manager.init();