(function($)
{
	$.extend(USAM_Page_manage_discounts, 
	{		
		terms   : '',	
		init : function() 
		{			
			$(function()
			{				
				USAM_Page_manage_discounts.wrapper = $('.tab_'+USAM_Tabs.tab);						
				if ( USAM_Page_manage_discounts[USAM_Tabs.tab] !== undefined )				
					USAM_Page_manage_discounts[USAM_Tabs.tab].event_init();
			});
		},	
	});
	
	/**
	 * Вкладка "Корзина"	 	
	 */
	USAM_Page_manage_discounts.basket = {
		
		event_init : function() 
		{				
			USAM_Page_manage_discounts.wrapper.on('change','#discount_cart_type', USAM_Page_manage_discounts.basket.display_action_cart_discont);	
			USAM_Page_manage_discounts.basket.display_action_cart_discont();
		},	
		
		display_action_cart_discont : function() 
		{			
			var type = $('#discount_cart_type').val();		
			if ( type == 'g' || type == 'gift_choice' || type == 'gift_one_choice' )
			{
				$('#discount_cart-value').addClass('hide');	
				$('#add_items_gift').removeClass('hide');	
			}
			else
			{
				$('#discount_cart-value').removeClass('hide');	
				$('#add_items_gift').addClass('hide');	
			}							
		},		
	};
			
	/**
	 * Вкладка "Купоны"
	 */
	USAM_Page_manage_discounts.coupons = {
		
		event_init : function() 
		{			
			USAM_Page_manage_discounts.wrapper.				
				on('click','.usam-editinline-link', USAM_Page_manage_discounts.coupons.editor_copon).
				on('click','.button_delete', USAM_Page_manage_discounts.coupons.delete_condition).
				on('submit','form#coupon_form', USAM_Page_manage_discounts.coupons.add_coupon_submit);
		},
		
		add_coupon_submit : function() 
		{			
			var title = jQuery("#coupon_form input[name='coupon[add_coupon_code]']").val();
			if ( title == '') 
			{
				jQuery('<div id="notice" class="error"><p>' + USAM_Page_manage_discounts.empty_coupon + '</p></div>').insertAfter('div.wrap > h2').delay(2500).hide(350);
				return false;
			}		
		},	
		
		/**
		 * Показать быстрое редактирование товара
		 */
		editor_copon : function() 
		{		
			var parent = $(this).closest('tr'),
				target_row = parent.nextAll('.usam-coupon-editor-row').eq(0);
			target_row.show();			
			parent.addClass('active');		
			return false;
		},		
		
		delete_condition : function(e) 
		{
			e.preventDefault();			
			jQuery(this).closest(".condition_coupon").remove();				
		},		
	};
})(jQuery);	
USAM_Page_manage_discounts.init();