/**
* Объект и функции USAM_Page_marketing.  
*/
(function($)
{
	$.extend(USAM_Page_marketing, 
	{		
		/**
		 * Обязательные события
		 */
		init : function() 
		{					
			$(function()
			{			
				USAM_Page_marketing.wrapper = $('.tab_'+USAM_Tabs.tab);						
				if ( USAM_Page_marketing[USAM_Tabs.tab] !== undefined )				
					USAM_Page_marketing[USAM_Tabs.tab].event_init();
			});
		},				
	});		
	
	/**
	 * Перекрёстные продажи
	 */
	USAM_Page_marketing.crosssell = 
	{		
		event_init : function() 
		{		
			USAM_Page_marketing.wrapper
			        .on('change', '#check_type', USAM_Page_marketing.crosssell.event_select_check_type)
					.on('click', '#usam_conditions .condition-logic', USAM_Page_marketing.crosssell.change_condition_logic);
		},
	
		change_condition_logic : function() 
		{			
			if ( $(this).hasClass("condition_logic_and") ) 
			{
				$(this).removeClass('condition_logic_and');
				$(this).addClass('condition_logic_or');	
				$(this).find('span').html(USAM_Page_marketing.text_or);
				$(this).find('input').val('OR');
			}
			else
			{
				$(this).removeClass('condition_logic_or');
				$(this).addClass('condition_logic_and');	
				$(this).find('span').html(USAM_Page_marketing.text_and);	
				$(this).find('input').val('AND');					
			}
		},
		
		event_select_check_type : function() 
		{ 
			var type = $(this).val();			
			var parent = $(this).closest('tr');
			
			parent.find(".check_blok").addClass('hidden').find('.condition_value').attr('disabled', true);
			parent.find("#check_"+type).removeClass('hidden').addClass('show').find('.condition_value').attr('disabled', false);			
		},		
	};
})(jQuery);	
USAM_Page_marketing.init();