<?php
/**
 * Передача данных яндекса
 */
class USAM_Yandex_Metrika_Counter 
{		
	private static $metrika;	
	public static function print_script( ) 
	{				
		$yandex = get_option('usam_yandex');	
		if ( empty($yandex['metrika']['counter_id']) )
			return;			

		self::$metrika = $yandex['metrika'];						
		self::display_counter();		
		if ( !empty(self::$metrika['ecommerce']) )
		{ 		
			?>
			<script>
				window.dataLayer = window.dataLayer || [];		
			</script>	
			<?php 
			if ( usam_is_product() ) 
				self::product_display( );
			elseif ( usam_is_transaction_results('success') && isset($_GET['payment_number']) ) 
				self::add_pushes( );
				
			self::add_to_basket();
		}
	}	
	
	private static function product_display( ) 
	{	
		global $post;
		$category_list = get_the_terms( $post->ID, 'usam-category' );
		$category_name = array();
		if ( !empty($category_list) )
			foreach ( $category_list as $category )
				$category_name[] = $category->name;		
			
		$brand = usam_get_product_brand_name( $post->ID );				
		?>	
		<script>
		dataLayer.push({
		  'ecommerce' : {
			'detail' : {
			  'products' : [{'name':'<?php echo get_the_title( $post->ID ) ?>','id':'<?php echo usam_get_product_meta($post->ID, 'sku') ?>','price' : '<?php echo usam_get_product_price( $post->ID ); ?>','brand' :  '<?php echo $brand; ?>','category' : '<?php echo implode( ',', $category_name); ?>',}]
			}
		  }
		});
		</script>	
		<?php 	
	}
	
	private static function add_pushes( ) 
	{	
		$output = "";			
		$document = usam_get_payment_document( $_GET['payment_number'], 'number' );	
		if ( empty($document) )
			return $output;
		
		$order = usam_get_order( $document['document_id'] );
		if ( empty($order) )
			return '';
		
		$html_products = array();
		$products = usam_get_products_order( $order['id'] );	
		foreach( $products as $product ) 
		{
			$category = usam_get_product_category_name( $product->product_id );
			$brand = usam_get_product_brand_name( $product->product_id );			
			$html_products[] = "{
			  'name' : '$product->name',
			  'id' : '$product->product_id',
			  'price' : $product->price,
			  'brand' : '$brand',
			  'category' : '$category',
			  'quantity': $product->quantity,
			}";		
		}			
		$total_tax = usam_get_tax_amount_order( $order['id'] );
		$coupon_name = (string)usam_get_order_metadata( $order['id'], 'coupon_name');
		?>	
		<script>
			dataLayer.push({
			  'ecommerce' : {
				'purchase' : {
				  'actionField' : { 
					'id' : '<?php echo $order['id']; ?>', 
					'affiliation' : '<?php echo get_bloginfo('name'); ?>', 
					'revenue' : <?php echo $order['totalprice']; ?>, 
					'tax' : <?php echo $total_tax; ?>, 
					'shipping' : <?php echo $order['shipping']; ?>, 
					'coupon' : '<?php echo $coupon_name; ?>'			
				 },
				 'products' : [ <?php echo implode(",",$html_products) ?> ]
				}
			  }
			});
		</script>	
		<?php 		
	}
		
	private static function add_to_basket( ) 
	{ 
		?>	
		<script>
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
							dataLayer.push({
							  'ecommerce' : {
								'add' : { 
								  'products' : [
									{
									  'name'  : response.title,
									  'id'    : product_id, 
									  'price' : response.price,
									  'brand' : response.brand,
									  'category' : response.categories.join(" > "),
									  'quantity': 1
									}
								  ]
								}
							  }
							});
						}						
					};				
					usam_send(data, ajax_callback);
				});					
			});    
			</script>	
		<?php 	
	}
	
	private static function display_counter( ) 
	{			
		if( usam_check_bot('yandexmetrika') || !usam_is_bot() )
		{ 
			?>		
			<script>	
				(function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)}; var z = null;m[i].l=1*new Date(); for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }} k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)}) (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym"); ym(<?php echo self::$metrika['counter_id']; ?>, "init", { clickmap:true, trackLinks:true, accurateTrackBounce:true, webvisor:<?php echo empty(self::$metrika['webvisor'])?'false':'true'; ?>, trackHash:true<?php echo empty(self::$metrika['ecommerce'])?'':',ecommerce:"dataLayer"'; ?> }); 
			 </script>			
			<?php	
		}
	}
}
?>