(function($)
{		
	$(function()
	{		
		$('body').on('click', '.js-thumbnail-add', set_thumbnail);
		$('body').on('click', '.js-thumbnail-remove', delete_thumbnail);	
	});	
	
	var delete_thumbnail = function(e) 
	{
		e.preventDefault();
		var box = $(this).parents('.usam_thumbnail');
		box.find('img').attr( 'src', USAM_Thumbnail.no_image );
		box.find('.js-thumbnail-add').data( 'attachment_id', 0 );
		box.find('.js-thumbnail-remove').addClass('hide');
		box.find('.js-thumbnail-id' ).val( 0 );
	};
		
	var set_thumbnail = function(e) 
	{
		e.preventDefault();
		button = $(this);
		var box = button.parents('.usam_thumbnail');
		var attachment_id = $( this ).data( 'attachment_id' );			
		var images_file_frame;
		images_file_frame = wp.media.frames.images_file_frame = wp.media( {
			title    : button.data( 'title' ),
			button   : { text : button.data( 'button_text' ) },
			library  : { type: 'image' },
			multiple : false
		} );		
		wp.media.frames.images_file_frame.on( 'open', function() {
			var selection = wp.media.frames.images_file_frame.state().get( 'selection' );
			
			if ( attachment_id > 0 ) 
			{
				attachment = wp.media.attachment( attachment_id );
				attachment.fetch();
				selection.add( attachment ? [ attachment ] : [] );
			}
		} );		
		images_file_frame.on( 'select', function() 
		{				
			attachment = images_file_frame.state().get( 'selection' ).first().toJSON();			
			attachment_id = attachment.id;			
			if ( attachment.sizes.thumbnail )
				var url = attachment.sizes.thumbnail.url;
			else
				var url = attachment.sizes.full.url;
			box.find('img').attr( 'src', url );
			box.find('.js-thumbnail-add').data( 'attachment_id', attachment_id );
			box.find('.js-thumbnail-remove').removeClass('hide');
			box.find('.js-thumbnail-id' ).val( attachment_id );
		} );
		images_file_frame.open();
	};	
})(jQuery);