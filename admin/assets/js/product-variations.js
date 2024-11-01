(function($)
{
	var resize_iframe = function() {
		if (typeof window.parent.usam_resize_iframe != 'undefined') {
			window.parent.usam_resize_iframe();
		}
	};

	$(function()
	{
		resize_iframe();		
		$('.usam-variation-editor-link').on('click', function(e)
		{			
			e.preventDefault();		
			$('.wp-list-table .usam-editor-row').hide();
			$('.wp-list-table .row_product_variation').removeClass('active');
			var key = $(this).data('key');
			var parent = $(this).closest('tr'),
				target_row = parent.nextAll('.usam-'+key+'-editor-row').eq(0);
			target_row.show();
			parent.addClass('active');
			resize_iframe();
			return false;
		});
	});
	var new_variation_set_count = 0;

	$(function()
	{
		$('.variation_checkboxes').on('click', '.variation-set', event_toggle_checkboxes).
		                           on('click', 'a.expand', event_toggle_children).
		                           on('click', '.selectit input:checkbox', event_display_apply_variations).
		                           on('click', '.children input:checkbox', event_toggle_parent);

		$('a.add_variation_set_action').bind('click', event_add_new_variation_set);
		$('#add-new-variation-set .button').bind('click', event_variation_set_add);
		$('#add-new-variation-set input[type="text"]').bind('keypress', event_variation_set_inputs_keypress).
		                                               bind('focus', event_variation_set_inputs_focus).
		                                               bind('blur', event_variation_set_inputs_blur);
		$('.usam-product-variation-thumbnail a').bind('click', event_variation_thumbnail_click);
	});

	var event_variation_thumbnail_click = function( e ) 
	{		
		e.preventDefault();				
		e.stopPropagation();	
		
		var t = $(this);		
		window.parent.usam_display_media( t.data('attachment_id'), t.data('post_id'), t.data('product_title') );		
	};	

	var event_variation_set_add = function()
	{ 
		var form = $('#add-new-variation-set');
		form.find('.error').removeClass('error');

		form.find('input[type="text"]').each(function(){
			var t = $(this);
			if (t.val() == '') {
				t.parent().addClass('error');
			}
		});

		if (form.find('.error').length === 0) 
		{
			usam_active_loader();
			var data = {
					action        : 'add_variation',
					variation_set : $('#new-variation-set-name').val(),
					variants      : $('#new-variants').val(),
					post_id       : USAM_Product_Variations.product_id,
					nonce         : USAM_Product_Variations.add_variation_nonce
				},
				callback = function(r) 
				{
					var checklist, color, set_id, existing_set, content;
					
					checklist = $('.variation_checkboxes');
					content = $(r.content);
					set_id = content.attr('id');
					existing_set = checklist.find('#' + set_id);
					if (existing_set.length > 0) {
						existing_set.find('.children').append(content.find('.children .ajax'));
					} else {
						checklist.append(content);
					}

					color = checklist.find('li').css('backgroundColor') || '#FFFFFF';
					checklist.find('.ajax').
						animate({ backgroundColor: '#FFFF33' }, 'fast').
						animate({ backgroundColor: color }, 'fast', function(){
							$(this).css('backgroundColor', 'transparent');
						}).
						removeClass('ajax');					
					$('#new-variants').val('');
					form.find('label').show().css('opacity', '1');
				};
			usam_send(data, callback);

		}

		return false;
	};

	var event_variation_set_inputs_focus = function() {
		$(this).siblings('label').animate({opacity:0.5}, 150);
	};

	var event_variation_set_inputs_blur = function() {
		var t = $(this);
		if (t.val() == '') {
			t.siblings('label').show().animate({opacity:1}, 150);
		}
	};

	/**
	 * Remove class "error" when something is typed into the new variation set textboxes
	 */
	var event_variation_set_inputs_keypress = function(e) {
		var code = e.keyCode ? e.keyCode : e.which;
		if (code == 13) {
			$('#add-new-variation-set .button').trigger('click');
			e.preventDefault();
		} else {
			$(this).siblings('label').hide().removeClass('error');
		}
	};

	/**
	 * Show the Add Variation Set form and focus on the first text field
	 */
	var event_add_new_variation_set = function() {
		var t = $(this);
		$('#add-new-variation-set').show().find('#new-variation-set-name').focus();
		window.parent.usam_resize_iframe();
	};

	/**
	 * Deselect or Select all children variations when variation set is ticked.
	 */
	var event_toggle_checkboxes = function() {
		var t = $(this), checked;

		if (t.is(':checked')) {
			checked = true;
		} else {
			checked = false;
		}

		t.closest('li').find('.children input:checkbox').each(function(){
			this.checked = checked;
		});

		if (checked !== t.closest('li').hasClass('expanded'))
			t.parent().siblings('.expand').trigger('click');
	};

	/**
	 * Show children variant checkboxes when the triangle is clicked.
	 */
	var event_toggle_children = function() {
		var t = $(this);
		t.siblings('ul').toggle();
		resize_iframe();
		t.closest('li').toggleClass('expanded');
		return false;
	};

	/**
	 * Show the update variation button.
	 */
	var event_display_apply_variations = function() {
		$('.update-variations').fadeIn(150);
	};

	/**
	 * Deselect the variation set if none of its variants are selected Or select the variation set when at least one of its variants is selected.
	 */
	var event_toggle_parent = function() {
		var t = $(this),
			parent = t.closest('.children').parent();
			parent_checkbox = parent.find('.variation-set'),
			checked = this.checked;

		if (this.checked) {
			parent_checkbox[0].checked = true;
		} 
		else if (parent.find('.children input:checked').length == 0) 
		{
			parent_checkbox[0].checked = false;
			parent.find('.expand').trigger('click');
		}
	};
})(jQuery);