<?php
class USAM_Facebook_Pixel
{		
	public static function pixel( )
	{						
		$pixel = get_option( 'usam_facebook_pixel', false );
		if ( !empty($pixel) )
		{			 			
			$currency = usam_get_currency_price_by_code();
			?>
			<script>
				!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];
				s.parentNode.insertBefore(t,s)}(window, document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','<?php echo $pixel; ?>');fbq('track', 'PageView');
				jQuery(document).ready(function()
				{
					jQuery('body').delegate('.js-product-add', 'product-add' , function( e, product_id, response )	
					{
						var data   = {				
							action     : 'get_product_data',			    
							product_id : product_id,						
						};
						ajax_callback = function(response)
						{								
							if ( response )
							{
								fbq('track', 'AddToCart', {
									content_name: response.title,
									content_category: response.categories.join(" > "),
									content_ids: [product_id],
									content_type: 'product',
									value: response.price,
									currency: '<?php echo $currency; ?>' 
								});        
							}						
						};				
						usam_send(data, ajax_callback);
					});	
					<?php
					if ( usam_is_product() ) 
						self::product_display( );	
					if ( usam_is_transaction_results('success') && isset($_REQUEST['payment_number']) )
					{
						$payment_number = sanitize_title($_REQUEST['payment_number']);
						$payment = usam_get_payment_document($payment_number, 'number');
						if ( !empty($payment['order_id']) )
						{
							$products = usam_get_products_order( $payment['order_id'] );
							$order = usam_get_order( $payment['order_id'] );
							$items = [];
							foreach( $products as $key => $product ) 
							{
								$category = usam_get_product_category_name( $product->product_id );
								$brand = usam_get_product_brand_name( $product->product_id );	
								$items[] = "{'id':'$product->product_id','quantity':'$product->quantity'}";
							}	
							$currency = usam_get_currency_price_by_code( $order['type_price'] );
							?>fbq('track','Purchase',{ content_type: 'product', contents:[<?php echo implode(",",$items); ?>], value: <?php echo $order['totalprice']; ?>, currency: '<?php echo $currency; ?>'});<?php
						}
					}
					?>						
				});  							
			</script>
			<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=<?php echo $pixel; ?>&ev=PageView&noscript=1"/></noscript>			
			<?php
		}
	}
	
	private static function product_display(  ) 
	{	
		global $post;				
		$post_title = get_the_title( $post->ID );
		$brand = usam_get_product_brand_name( $post->ID );
		$category = usam_get_product_category_name( $post->ID );	
		$price = usam_get_product_price( $post->ID );	
		$currency = usam_get_currency_price_by_code();
		
		echo "fbq('track', 'ViewContent', {content_type: 'product',content_ids: ['$post->ID'],content_name: '$post_title',content_category: '$category',value: $price,currency: '$currency'});"; 
	}
}
?>