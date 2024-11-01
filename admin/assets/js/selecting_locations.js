/**
* Вывести форму выбора местоположений
*/
var Selecting_Locations = [];
(function($)
{	
	$.extend(Selecting_Locations, 
	{						 
		init : function() 
		{				
			$(function()
			{				
			$('.display_locations').
				on('click', 'input[type="checkbox"]', Selecting_Locations.checkbox_locations).
				on('click', 'span', Selecting_Locations.display_list_locations);				
				Selecting_Locations.load_list_locations();	
			});
		},	
		
		hide_list_locations : function()
		{				
			$(".display_locations ul ul").hide();	
			$('.display_locations ul li input:checked').each(function()
			{	
				$(this).parent("li").find("ul").find("input").prop("disabled", true);				
				$(this).parents("ul").show();				
			});			
			
		},			
		
		load_list_locations : function()
		{					
			$(".display_locations ul ul").hide();	
			$('.display_locations ul li input:checked').each(function()
			{	
				var block = $(this).parent('li');		
				block.find("ul").find("input").prop("disabled", true);				
				$(this).parents("ul").show();					
				block.parents('li').each(function()
				{				
					//var location_id = $(this).data('location');	
					$(this).siblings('span').html(" - ");	
				});						
			});				
		},		
		
		display_list_locations : function()
		{						
			var block = $(this).parent('li');							
			if ( block.children("ul").is(':visible') )		
				$(this).html(" + ");		
			else
				$(this).html(" - ");
			block.children("ul").toggle();				
		},
		
		checkbox_locations : function()
		{	
			if ( $(this).prop("checked") )
				$(this).parent('li').children("ul").find("input").prop("disabled", true);		
			else
				$(this).parent('li').children("ul").find("input").prop("disabled", false);	
		},		
	});	
})(jQuery);	
Selecting_Locations.init();