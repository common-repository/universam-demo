(function( $ ) {
	var api = wp.customize;
	api('blogname', function( value ) {
		value.bind( function( to ) {
			$('.site-title a').text( to );
		} );
	} );
	api('blogdescription', function( value ) {
		value.bind( function( to ) {
			$('.site-description').text( to );
		} );
	} );
	api.bind('preview-ready', function() {
		api.preview.bind('update-color-scheme-css', function( css ) {
			_.each( css, function( v, setting ) { 
				document.documentElement.style.setProperty('--'+setting, v);
			});	
		} );
	} );
})( jQuery );
