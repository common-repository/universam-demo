/**
 * @example jQuery('.rating').rating();
 */ 
(function( $ )
{					
	var methods = 
	{			
		init : function( settings ) 
		{  
			var options = {selected : null, star: 'star'};	
			options = $.extend( options, settings );			
			return this.each(function()
			{        
				var $this = $(this),
					data = $this.data('rating');			
				if ( ! data ) 
				{					
					$this.data('rating', options);	
					$this.find('.'+options.star).on("mouseover.rating", options, function( e ) 
					{ 
						$(this).addClass('rating__hover').prevAll('.'+e.data.star).addClass('rating__hover');	
						$(this).nextAll().removeClass('rating__hover');
					
					}).on("click.rating", options, function( e ) 
					{ 		
						if ( e.data.selected != null )
						{	
							var $this = $(this);
							
							$this.addClass('rating__selected').removeClass('rating__hover');
							$this.siblings('.rating__hover').addClass('rating__selected').removeClass('rating__hover');							
							var rating = $this.siblings('.rating__selected').length;
							rating = rating + 1;
							var callbacks = jQuery.Callbacks();
							callbacks.add( e.data.selected );
							callbacks.fire( rating, $this );	
						}		
					});						
				}					
		   });		   
		},
		
		close : function( )
		{
		   return this.each(function()
		   {
				var $this = $(this),
				data = $this.data('rating');				
				$(this).find('.'+data.star).unbind("mouseover.rating");
		   })
		},		
	};	 
	 
	$.fn.rating = function( method )
	{ 	
		if ( methods[method] ) {
			return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} 
		else if ( typeof method === 'object' || ! method ) 
		{
			return methods.init.apply( this, arguments );
		} 
		else 
		{
			$.error( 'Метод с именем ' +  method + ' не существует для jQuery.rating' );
		}   		
	};
})( jQuery );