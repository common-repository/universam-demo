(function($){
	$.extend(USAM_Admin_Bar, 
	{		
		timeout    : 10000,		
		init : function() 
		{
			$(function()
			{
				if ( $('#wp-admin-bar-online_consultant .selector_status_consultant').hasClass('active') )		
				{		
					setInterval('USAM_Admin_Bar.refresh()',USAM_Admin_Bar.timeout); 
				}				
			});
		},	
		
		refresh : function() 
		{				
			usam_send({'action': 'number_of_unread_menager_chat_messages', 'nonce': USAM_Admin_Bar.number_of_unread_menager_chat_messages_nonce}, (r) =>
			{ 
				if ( r )
				{						
					var number = $('#wp-admin-bar-online_consultant .number_events .numbers').text( );	
					if ( number < r )
					{ 
						$('#wp-admin-bar-online_consultant .number_events').removeClass( 'count-'+number );	
						$('#wp-admin-bar-online_consultant .number_events').addClass('count-'+r);									
						$('#wp-admin-bar-online_consultant .number_events .numbers').text( r );								
						if ( $('#chat_audio').length != 0 )
							$('#chat_audio')[0].play();	
					}
				}
			});				
		},			
	});
})(jQuery);
USAM_Admin_Bar.init();


document.addEventListener("DOMContentLoaded", () => {
	make('.js-chat-switch', 'click', (e) =>
	{			
		e.preventDefault();		
		var el = e.currentTarget;		
		el.classList.toggle('active');
		usam_send({'action': 'update_consultant_status', 'nonce': USAM_Admin_Bar.update_consultant_status_nonce, status:el.classList.contains('active')});
	});	
	make('.js-change-theme-edit', 'click', (e) =>
	{			
		e.currentTarget.classList.toggle('active');
		document.querySelectorAll('.change_block').forEach((el) => {
			el.classList.toggle('hide');
		});	
		usam_api('theme/edit', 'GET');
	});	
	
	if( document.getElementById('task_manager') )
	{
		var taskManager = new Vue({		
			el: '#task_manager',
			data() {
				return {					
					processes:[],				
				};
			},
			mounted: function () {	 
				var el = document.querySelector('#wp-admin-bar-task_manager')
				if ( el )
					el.addEventListener("click", this.load );
			},	
			methods: {
				load()
				{
					usam_api('processes', 'GET', (r) => { 
						this.processes = r.items;
						usam_set_height_modal( jQuery('#task_manager') );
					});
				},
				pause(k)
				{
					this.processes[k].status = 'pause';
					usam_api('process/'+this.processes[k].id, {status:'pause'}, 'POST');
				},
				start(k)
				{
					this.processes[k].status = 'start';
					usam_api('process/'+this.processes[k].id, {status:'start'}, 'POST');
				},				
				del(k)
				{
					usam_api('process/'+this.processes[k].id, {}, 'DELETE');
					this.processes.splice(k, 1);
					if ( this.processes.length == 0 )
					{
						var el = document.querySelector('#wp-admin-bar-task_manager #usam_progress')
						if ( el )
							el.remove();
						var el = document.querySelector('#wp-admin-bar-task_manager .number_events')
						if ( el )
							el.remove();						
					}
					else
						var el = document.querySelector('#wp-admin-bar-task_manager .numbers')
						if ( el )
							el.innerText = this.processes.length;
				}
			}
		})
	}
})				