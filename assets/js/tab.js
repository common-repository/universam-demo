+function ($) 
{ 
	var Tab = function (element) {	   
		this.element = $(element)
	}

	Tab.TRANSITION_DURATION = 150
	Tab.prototype.show = function () 
	{			
		var target  = this.element.attr('href');
		if ( typeof target === 'undefined' ) 
			return false;			
		
		var header_tab = this.element.closest('.header_tab');
		this.activate( target, header_tab );		
	}
	
	Tab.prototype.activate = function (target, header_tab) 
	{ 
		var countent_tabs = header_tab.siblings('.countent_tabs');
	//	if ( countent_tabs.children(target).hasClass('current') )
	//		return;		
		var current = header_tab.find('.current').removeClass('current');		
		countent_tabs.find('.current').removeClass('current');
		if (countent_tabs.find('.tab:visible').length > 0 ) 
		{				
			countent_tabs.children('.tab:visible:first').fadeOut( 100, function() {		
				countent_tabs.children(target).addClass('current').fadeIn('fast');
			});			
		} 
		else 
		{
			countent_tabs.find( target ).fadeIn('fast');
		} 
		header_tab.find('a.tab[href="'+target+'"]').addClass('current');
	}	

	function Plugin(option) 
	{ 
		return this.each(function () 
		{
			var $this = $(this)
			var data  = $this.data('bs.tab')

			if (!data) $this.data('bs.tab', (data = new Tab(this)))
			if (typeof option == 'string') data[option]()
		})
	}

	var old = $.fn.tab

	$.fn.tab             = Plugin
	$.fn.tab.Constructor = Tab

	$.fn.tab.noConflict = function () { 
		$.fn.tab = old
		return this
	}
  
	var clickHandler = function (e) 
	{
		e.preventDefault();					
		Plugin.call( $(this), 'show' );			
		location.hash = this.hash.replace("#","#_");	
		return false;	
	}
	
	jQuery(document).ready(function()
	{
		$(document).on('click', '.usam_tabs .header_tab a', clickHandler);
		
		var tab_id = window.location.hash.replace("#_","").replace("#","").replace("/","");				
		$('.usam_tabs').each(function(i,elem) 
		{
			if ( tab_id != '' && $(this).find('#'+tab_id).length > 0 )
			{
				$(this).find('.header_tab a[href="#'+tab_id+'"]').tab('show');
			}
			else if ( $(this).find('.header_tab a.current').length > 0 )
			{		
				$(this).find('.header_tab a.current').tab('show');
			}
			else
			{				
				$(this).find('.header_tab a.tab:first').tab('show');
			}			
		});			
	});	
}(jQuery);