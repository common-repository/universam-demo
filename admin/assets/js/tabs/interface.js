(function($)
{
	$.extend(USAM_Page_interface, 
	{		
		init : function() 
		{					
			$(function()
			{			
				$('.js-sort-items').sortable({cursor: "move", items: '.js-sort-item', axis: 'y', handle: '.sort'});
				USAM_Page_interface.wrapper = $('.tab_'+USAM_Tabs.tab);						
				if ( USAM_Page_interface[USAM_Tabs.tab] !== undefined )				
					USAM_Page_interface[USAM_Tabs.tab].event_init();
			});
		},			
	});
		
	USAM_Page_interface.product_view = 
	{		
		event_init : function() 
		{				
			USAM_Page_interface.wrapper			    
				.on('change','.template_active',USAM_Page_interface.product_view.template_active)
				.on('click','.up-carousel',USAM_Page_interface.product_view.up_carousel)
				.on('click','.down-carousel',USAM_Page_interface.product_view.down_carousel);				
		},
		
		change_template : function( item, carousel ) 
		{			
			var name_template = item.data('name_template');
			carousel.closest('.js-sort-item').find('.template_input').val(name_template);
		},
	
		up_carousel : function(e) 
		{ 
			e.preventDefault();	
			var carousel = $(this).siblings('#jcarousel');
			var item = carousel.find('ul .active');
			if ( item.length == 0)
			{
				item = carousel.find('li').first().addClass('active');
				USAM_Page_interface.product_view.change_template( item, carousel);
			}
			else
			{			
				var next_li = item.prev();
				if ( next_li.length != 0 )
				{
					$(item).removeClass('active');
					$(next_li).addClass('active');
					USAM_Page_interface.product_view.change_template( next_li, carousel);
				}
			}
		},	
		
		down_carousel : function(e) 
		{			
			e.preventDefault();	
			var carousel = $(this).siblings('#jcarousel');
			var item = carousel.find('ul .active');
			if ( item.length == 0)
			{
				item = carousel.find('li').first().addClass('active');
				USAM_Page_interface.product_view.change_template( item, carousel);
			}
			else
			{			
				var next_li = item.next();
				if ( next_li.length != 0 )
				{
					$(item).removeClass('active');
					$(next_li).addClass('active');
					USAM_Page_interface.product_view.change_template( next_li, carousel);
				}
			}
		},		
		
		template_active : function() 
		{
			$(this).closest('.postbox').toggleClass('active');
		},		
	};
})(jQuery);	
USAM_Page_interface.init();