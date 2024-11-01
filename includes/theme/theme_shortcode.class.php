<?php
/**
 * Класс шоркодов
*/
new USAM_Theme_Shortcode();
class USAM_Theme_Shortcode
{			
	public function __construct() 
	{				
		add_shortcode( 'usam_product_attribute', [&$this, 'product_attribute']);
		add_shortcode( 'usam_product', [&$this, 'product_shorttag']);
		add_shortcode( 'buy_product', [&$this, 'buy_product']);
		add_shortcode( 'promotion_timer', [&$this, 'promotion_timer']);
		add_shortcode( 'company', [&$this, 'company']);
	}	
	
	function company( $atts ) 
	{	
		$info = usam_shop_requisites_shortcode();
		$content = '';
		if( !empty($atts['property']) )
		{
			if ( isset($info[$atts['property']]) )
				$content = $info[$atts['property']];
			elseif( $atts['property'] === 'site_url' )
				$content = get_bloginfo('url');
			elseif( $atts['property'] === 'contact_page' )
				$content = home_url( 'contacts' );
			elseif( $atts['property'] === 'registration_page' )
				$content = usam_get_url_system_page('login');				
		}
		return $content;	
	}
	
	function promotion_timer( $atts ) 
	{	
		global $wp_query; 
		ob_start();		
		if ( isset($wp_query->query['usam-category_sale']) )
		{  
			$term = get_term_by('slug', $wp_query->query['usam-category_sale'], 'usam-category_sale');
			$end_date = usam_get_term_metadata($term->term_id, 'end_date_stock');				
			if( $end_date )
			{
				?>
				<div class="promotion_timer">
					<timer :date="'<?php echo $end_date; ?>'"></timer>
				</div>
				<?php			
			}
		}
		return ob_get_clean();
	}
	
	function product_attribute( $atts ) 
	{	
		$content = usam_get_product_property( $atts['product_id'], $atts['attribute'] );
		return $content;	
	}
	
	function buy_product($atts)
	{
		return usam_get_buy_button_and_gotocart( $atts['product_id'] );
	}
	
	function product_shorttag( $atts )
	{		
		$product_id = $atts['product_id'];	
		
		if ( !empty($atts['width']) && !empty($atts['height']) )
			$size = array($atts['width'], $atts['height']);
		else
			$size = 'product-thumbnails';
		
		$product_id = $atts['product_id'];
		$product = get_post( $product_id );		
		if ( empty($product) )
			return;
		
		$out = '<div class="usam_product_shorttag">';
			if ( !empty($atts['thumbnail']) )
				$out .= "<a href='".usam_product_url( $product->ID )."'>".usam_get_product_thumbnail($product_id, $size, $product->post_title)."</a>";		
			if ( !empty($atts['title']) )
				$out .= "<p class ='product_title'>$product->post_title</p>"; 
			if ( !empty($atts['price']) )
				$out .= "<p class ='product_price'>".usam_get_product_price_currency( $product_id )."</p>"; 
			
			if ( !empty($atts['add_to_cart']) )
			{					
				$out .= usam_add_to_cart_button( $product_id, true );				
			}			
		$out .= "</div>";
		return $out;
	}
}
?>