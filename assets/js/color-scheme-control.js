( function( api ) 
{
	var scheme = document.getElementById('_customize-input-color_scheme') ? api('color_scheme')() :'default';	
		
	api.controlConstructor.select = api.Control.extend({
		ready: function() 
		{ 
			if ( 'color_scheme' === this.id ) 
			{
				this.setting.bind( 'change', function( scheme ) 
				{
					var styles = Color_Scheme[scheme].styles;	
					_.each( styles, function( color, setting ) 
					{						
						api( setting ).set( color );
						api.control( setting ).container.find('.color-picker-hex').data('data-default-color', color).wpColorPicker( 'defaultColor', color );
					});
					api.previewer.send( 'update-color-scheme-css', styles );
				});
			}
		}
	});

	function updateCSS() 
	{		
		var styles = Color_Scheme[scheme].styles;	
		_.each( Color_Scheme.default.styles, function( v, setting ) {	
			styles[setting] = api( setting )();
		});
		api.previewer.send( 'update-color-scheme-css', styles );
	}
	_.each( Color_Scheme[scheme].styles, function( v, setting ) 
	{ 
		api( setting, function( setting ) {
			setting.bind( updateCSS );
		});		
	});
})( wp.customize );