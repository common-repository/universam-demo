var product_quantity_basket = false;
function usam_update_product_quantity_basket( quantity, id )
{ 	
	if ( typeof id !== 'undefined' )
	{ 
		product_quantity_basket = id;
		var handler = function( r ) 
		{					
			if ( product_quantity_basket == id )
			{
				jQuery(".usam_basket__content").empty().append( r.cart_page ); 
				usam_update_basket_counters( r );
			}
		};					
		usam_send({quantity: quantity, product_key: id, action: 'update_product_quantity_basket'}, handler);	
	}
}

function usam_update_basket_counters( c )
{	
	if ( c.number_goods )
		jQuery('.js-basket-icon').addClass('items_basket');
	else
		jQuery('.js-basket-icon').removeClass('items_basket');
			
	var counters = {widget:'widget', number_goods:'number-goods-basket', number_goods_message:'number_items_basket', total_number_items_basket:'basket_counter', basket_weight:'basket_weight_counter', basket_subtotal:'basket_subtotal', basket_total:'basket-total', total_number_items_basket_message:'total_number_items_basket'}
	for (var k in c)
	{
		el = document.querySelectorAll('.js-'+counters[k]);
		if ( el )
			el.forEach((e)=>{ e.innerHTML = c[k]; });
	}		
	jQuery('.js_basket_counter').addClass('site_counter');		
	jQuery('.js_basket_weight_counter').addClass('site_counter');
	jQuery('.is-loading-basket').removeClass("is-loading-basket");
}	

jQuery(document).ready(function($)
{			
	$('body').on('click', '.js-empty-cart', function(e)
	{	
		e.preventDefault();	
		$(this).addClass("is-loading");	
		callback = (response) =>
		{							
			$(this).removeClass("is-loading");	
			$('.usam_basket__content').html(response.cart_page);				
			usam_update_basket_counters( response );					
		};				
		usam_send({action: 'empty_cart'}, callback);
	});
	
	jQuery(document).on("click", ".js-return-bonuses", function(e) 	
	{	
		e.preventDefault();	
		var parameters = {	
			action        : 'return_bonuses',
			},					
			response_handler = function( response ) 
			{
				jQuery(".usam_basket__content").empty().append( response.cart_page ); 
				usam_update_basket_counters( response );
			};					
		usam_send(parameters, response_handler);
	});	
	
	jQuery(document).on("click", ".js-spend-bonuses", function(e) 	
	{	
		e.preventDefault();	
		var parameters = {
			action        : 'spend_bonuses',
			},					
			response_handler = function( response ) 
			{	
				jQuery(".usam_basket__content").empty().append( response.cart_page ); 
				usam_update_basket_counters( response );
			};					
		usam_send(parameters, response_handler);		
	});	
	
	
	$('body').on("click", ".js_apply_coupon", function(e) 	
	{	
		e.preventDefault();	
		var handler = function( response ) 
		{	
			jQuery(".usam_basket__content").empty().append( response.cart_page ); 
			usam_update_basket_counters( response );				
		};					
		usam_send({coupon_number : jQuery('#coupon_number').val(), action: 'apply_coupon'}, handler);	
	});	
	
	$('body').on("click", ".js_basket_button_minus", function(e) 	
	{	
		e.preventDefault();	
		var $quantity = $(this).siblings(".js_update_quantity");		
		var quantity = parseFloat( $quantity.val() );		
		if ( typeof $quantity.attr('step') !== typeof undefined ) 		
		{					
			quantity = quantity - parseFloat($quantity.attr('step'));			
			if ( !Number.isInteger(quantity) )			
				quantity = quantity.toFixed(1);
		}
		else
			quantity = quantity - 1;		
		if ( quantity < 0 )		
			return false;				
		$quantity.val(quantity);		
		var product_row = $(this).closest('.js-product-row');
		usam_update_product_quantity_basket( quantity, product_row.data("item_id") );			
	});	

	$('body').on("click", ".js_basket_button_plus",function(e) 	
	{		
		e.preventDefault();		
		var $quantity = $(this).siblings(".js_update_quantity");		
		var quantity = parseFloat( $quantity.val() );	
		if ( typeof $quantity.attr('step') !== typeof undefined ) 		
		{					
			quantity = quantity + parseFloat($quantity.attr('step'));
			if ( !Number.isInteger(quantity) )	
				quantity = quantity.toFixed(1);
		}
		else
			quantity = quantity + 1;
		
		var max_stock = $quantity.attr('max');	
		if ( typeof max_stock !== typeof undefined && max_stock !== false ) 
		{				
			max_stock = parseFloat(max_stock);	
			if ( max_stock < quantity )		
				return false;
		}
		$quantity.val(quantity);
		var product_row = $(this).closest('.js-product-row');
		var key = product_row.data("item_id");
		usam_update_product_quantity_basket( quantity, key );	
	});
	
	$('body').on( "change", ".js_update_quantity", function(e) 	
	{	
		var quantity = parseFloat( $(this).val() );		
		if ( typeof $(this).attr('step') !== typeof undefined )
		{				
			step = parseFloat($(this).attr('step'));	
			quantity = Math.round(quantity/step)*step;
		}				
		var product_row = $(this).closest('.js-product-row');
		if ( quantity > 0 )
		{
			$(this).val(quantity);
			usam_update_product_quantity_basket( quantity, product_row.data("item_id") );		
		}
	});	

	$(document).on('click', ".js_button_remove", function(e) 	
	{	
		e.preventDefault();	
		var row = $(this).closest('.js-product-row');
		var key = row.data("item_id");
		usam_update_product_quantity_basket( 0, key );	
		row.remove();
	});		
});

if( document.getElementById('basket') )
{
	basket.main = new Vue({		
		el: '#basket',
		mixins: [handler_checkout],//sliderScroll
		data() {
			return {				
				coupon_name:'',	
			};
		},	
		mounted() {
			let arr = window.location.pathname.split('/');			
			this.page = arr[arr.length - 1];
			if( !this.page )
				this.page = arr[arr.length - 2];
			this.loadProperties();		
			usam_api('basket/cross_sells', 'GET', (r) => {
				if( r )
				{
					this.cross_sells = r;				
					setTimeout(()=> {				
						jQuery('.js-carousel-products').owlCarousel({autoplay:false, autoWidth:false, loop:false, nav:true, dots:false, responsive:{200:{items:2},600:{items:3},1024:{items:5}}});	
					}, 100);
				}
			//	this.initSliderScroll();
			});			
			usam_api('gifts', 'GET', (r) => this.gifts = r);			
			usam_api('types_payers', {order:'ASC'}, 'POST', (r) => this.types_payers = r.items);			
			usam_api('companies', {user_id:-1}, 'POST', (r) => this.companies = r.items );	
		},	
	})
}