(function($)
{
	$.extend(USAM_Page_manage_prices, 
	{			
		init : function() 
		{					
			$(function()
			{					
				USAM_Page_manage_prices.wrapper = $('.tab_'+USAM_Tabs.tab);						
				if ( USAM_Page_manage_prices[USAM_Tabs.tab] !== undefined )				
					USAM_Page_manage_prices[USAM_Tabs.tab].event_init();	
			});
		},							
	});			

	USAM_Page_manage_prices.products = 
	{		
		event_init : function() 
		{	
			USAM_Page_manage_prices.wrapper			
				.on('click','#save-button', USAM_Page_manage_prices.products.save_all)
				.on('click','#apply', USAM_Page_manage_prices.products.bulk_actions);
		},
					
		bulk_actions : function( e ) 
		{				
			e.preventDefault();				
			var operation = $('#price_global_operation').val();			
			var type_operation = $('#price_global_type_operation').val();	
			var value = $('#price_global_value').val();						
			if ( value != '' )
			{				
				value = parseFloat(value);	
				var price = 0;
				var margin = 0;
				$(".wp-list-table .column-price input:text").each( function()
				{						
					price = $(this).val();	
					price = parseFloat(price);					
					switch ( type_operation ) 
					{
						case 'p':	
							margin = price*value/100;						
							margin = parseFloat(margin.toFixed(2));	
						break;	
						case 'f':	
							margin = value;	
						break;								
						default:	
							price = value;
							margin = 0;
						break;	
					}		
					if ( operation == '+' )
						price = price + margin;
					else
						price = price - margin;							
					$(this).addClass("change_made").val(price);
				});	
			}	
		},				
		
		save_all : function(e) 
		{							
			e.preventDefault();
			usam_active_loader();
			
			var products = [];
			var i = 0;			
			$(".column-price .change_made").each( function()
			{					
				products[i] = { "price" : $(this).val(), "product_id" : $(this).data('product_id') };
				$(this).removeClass("change_made");	
				i++;
			});					
			usam_send({action: 'change_product_price', 'products': products, 'code_price': $('#type_price').val(), nonce: USAM_Page_manage_prices.change_product_price_nonce});	
		},			
	};
})(jQuery);	
USAM_Page_manage_prices.init();

document.addEventListener("DOMContentLoaded", () => {	
	if( document.getElementById('mass_price_change') )
	{ 
		new Vue({		
			el: '#mass_price_change',
			data() {
				return {					
					options:{},				
					selected:{category: '', brands: '', category_sale: '', catalog: '', operation: '', type_price: '', markup: ''}
				};	
			},	
			mounted() {
				usam_api('filters',{category: '', brands: '', category_sale: '', catalog: '', code_price:{base_type:'0', type:'all'}}, 'POST', (r)=>this.options=r);
			},	
			methods: {
				change(e)
				{ 		
					if ( this.selected.markup && this.selected.operation && this.selected.type_price )
					{
						data = structuredClone(this.selected);
						data.action = 'change_group_price';
						data.nonce = USAM_Page_manage_prices.change_group_price_nonce;						
						usam_send(data);	
						this.selected.markup = '';
					}
				}
			}
		})	
	}
})