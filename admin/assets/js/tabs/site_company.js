(function($)
{	
	$.extend(USAM_Page_site_company, 
	{				
		init : function() 
		{							
			$(function()
			{	
				USAM_Page_site_company.wrapper = $('.tab_'+USAM_Tabs.tab);				
				if ( USAM_Page_site_company[USAM_Tabs.tab] !== undefined )				
					USAM_Page_site_company[USAM_Tabs.tab].event_init();	
			}); 			
		}				
	});
	
	USAM_Page_site_company.employees = 
	{				
		event_init : function() 
		{				
			USAM_Page_site_company.wrapper			
				.on('click', '#add-bonus', USAM_Page_site_company.employees.add_bonus);
		},		
		
		add_bonus : function() 
		{			
			var bonus = $('.js-bonus').val(); 
			var description = $('.js-description').val();						
			if ( bonus == '' )
				$('.js-bonus').addClass('highlight');
			else
				$('.js-bonus').removeClass('highlight');
			
			if ( description == '' )
				$('.js-description').addClass('highlight');
			else
				$('.js-description').removeClass('highlight');
			if ( bonus != '' && description != '' )
			{
				usam_active_loader();
				bonus = parseInt(bonus); 
				var data   = {
						action        : 'add_bonus',
						'id'          : USAM_Tabs.id,		
						'bonus'       : bonus,						
						'description' : description,							
						 nonce        : USAM_Page_site_company.add_bonus_nonce
					},
					callback = function(response)
					{					   
						if ( $('.usam_view_form_tabs .header_tab .usam_menu-transactions').hasClass('active') )
							$('.usam_tab_table').html(response);
						$('.js-bonus').val('');
						$('.js-description').val('');
					};		
				usam_send(data, callback);		
			}			
		},
	};	
})(jQuery);	
USAM_Page_site_company.init();