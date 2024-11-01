<?php
/**
 * Передача данных в MyTarget
 */
class USAM_MyTarget_Counter 
{		
	private static $counter;	
	public static function print_script( ) 
	{				
		self::$counter = get_option('usam_mytarget_counter');	
		if ( empty(self::$counter['counter_id']) )
			return;		
		?>		
		<script>
		var _tmr = window._tmr || (window._tmr = []);
		_tmr.push({id: "<?php echo self::$counter['counter_id']; ?>", type: "pageView", start: (new Date()).getTime(), pid: "USER_ID"});
		(function (d, w, id) {
		  if (d.getElementById(id)) return;
		  var ts = d.createElement("script"); ts.type = "text/javascript"; ts.async = true; ts.id = id;
		  ts.src = "https://top-fwz1.mail.ru/js/code.js";
		  var f = function () {var s = d.getElementsByTagName("script")[0]; s.parentNode.insertBefore(ts, s);};
		  if (w.opera == "[object Opera]") { d.addEventListener("DOMContentLoaded", f, false); } else { f(); }
		})(document, window, "topmailru-code");
		</script><noscript><div>
		<img src="https://top-fwz1.mail.ru/counter?id=<?php echo self::$counter['counter_id']; ?>;js=na" style="border:0;position:absolute;left:-9999px;" alt="Top.Mail.Ru" />
		</div></noscript>
		<?php 			
		if ( !empty(self::$counter['dynamic_remarketing']) )
		{ 		
			if ( usam_is_product() ) 
				self::product_display( );
			elseif ( usam_is_transaction_results('success') && isset($_GET['payment_number']) ) 
				self::add_pushes( );				
		}
	}	
	
	private static function product_display( ) 
	{	
		global $post;
		?>	
		<script>
		var _tmr = _tmr || [];
		_tmr.push({
			type: 'itemView',
			productid: '<?php echo $post->ID ?>',
			pagetype: 'product',
			list: '1',
			totalvalue: '<?php echo usam_get_product_price( $post->ID ); ?>'
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
			$html_products[] = $product->product_id;		
		}			
		?>	
		<script>
			var _tmr = _tmr || [];
			_tmr.push({
				type: 'itemView',
				productid: [ '<?php echo implode("','",$html_products) ?>' ],
				pagetype: 'purchase',
				list: '1',
				totalvalue: '<?php echo $order["totalprice"]; ?>'
			});
		</script>		
		<?php 		
	}
}
?>