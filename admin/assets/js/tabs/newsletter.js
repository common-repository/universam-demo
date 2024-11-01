(function($)
{
	$.extend(USAM_Page_newsletter, 
	{	
		init : function() 
		{					
			$(function()
			{	
				USAM_Page_newsletter.wrapper = $('.tab_'+USAM_Tabs.tab);						
				if ( USAM_Page_newsletter[USAM_Tabs.tab] !== undefined )				
					USAM_Page_newsletter[USAM_Tabs.tab].event_init();								
			});
		},		
		
		event_submit_screen_end : function(e) 
		{			
			if ( $('#list_of_subscribers input').length != 0 )
			{
				var checked = false;
				$('#list_of_subscribers input').each(function()
				{
					if ( $(this).is(':checked') )
					{
						checked = true;
						return false;
					}
				});			
				if ( !checked )
				{
					usam_notifi({text: 'Выберете список подписчиков'});
					$('#list_of_subscribers input').css("border", "1px solid #a4286a");		
					e.preventDefault();		
				}
			}			
		},
		
		event_email_action : function( ) 
		{			
			var status = $(this).hasClass('status-5')?4:5;	
			if ( status )
				$(this).removeClass('status-4').addClass('status-5');
			else				
				$(this).removeClass('status-5').addClass('status-4');
			var mail_id = $(this).data('mail_id');
			if ( mail_id )
				usam_api('newsletter/'+mail_id, {status:status}, 'POST', (r) => { USAM_Tabs.update_table(); });
		},	
	});
	
	/**
	 * Вкладка "Email-рассылка"	
	 */
	USAM_Page_newsletter.email_newsletters = 
	{		
		event_init : function() 
		{			
			USAM_Page_newsletter.wrapper									
				.on('click','.js-newsletter-status', USAM_Page_newsletter.event_email_action);
		}
	};	
	
	USAM_Page_newsletter.sms_newsletters = 
	{		
		event_init : function() 
		{			
			USAM_Page_newsletter.wrapper			
				.on('submit', 'form#element_editing_form ', USAM_Page_newsletter.event_submit_screen_end)
				.on('click','.js-newsletter-status', USAM_Page_newsletter.event_email_action);		
		},			
	};	
})(jQuery);	
USAM_Page_newsletter.init();