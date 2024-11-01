<?php 
require_once( USAM_FILE_PATH . '/includes/exchange/trading_platforms.class.php' );
/*
  Name: Google Merchant Center
  Icon: googlemerchantcenter
  https://support.google.com/merchants/answer/7052112?hl=ru
 */
class USAM_Merchant_Center_Exporter extends USAM_Trading_Platforms_Exporter
{		
	protected function get_export_product( $post ) 
	{	
		$content = empty($post->post_excerpt)?$post->post_content:$post->post_excerpt;	
		$available = usam_product_has_stock($post->ID) > 0?'in stock':'';	
		
		$out  = "    <item>\n\r";
	//	$out .= "      <g:payment_notes>" . __('Google Wallet', 'usam') . "</g:payment_notes>\n\r";	
		$out .= "      <g:id>$post->ID</g:id>\n\r";		
		$out .= "      <g:title><![CDATA[".$post->ID.' '.$this->get_product_title( $post )."]]></g:title>\n\r";	
		$out .= "      <g:description><![CDATA[ ".$this->text_decode( $content )." ]]></g:description>\n\r";
		$out .= "      <g:link>".$this->get_product_url( $post->ID )."</g:link>\n\r";			
		$product_categories = get_the_terms($post->ID, 'usam-category');
		if ( !empty($product_categories[0]) )					
		{					
			$categories = array( $product_categories[0]->name );			
			$term_ids = usam_get_ancestors( $product_categories[0]->term_id, 'usam-category' );
			foreach((array)$term_ids as $term_id) 
			{			
				$term = get_term_by( 'id', $term_id, 'usam-category' );
				array_unshift($categories, $term->name );
			}		
			$out .= "<g:google_product_category>".implode(" > ",$categories)."</g:google_product_category>\n\r";
		}				
		$image = usam_get_product_thumbnail_src($post->ID, 'single');		
		$brand = usam_get_product_brand_name( $post->ID );		
		$price = usam_get_product_price( $post->ID, $this->rule['type_price'] );	
		$currargs = array(
			'currency_symbol' => false,
			'display_decimal_point'   => true,
			'currency_code'   => true,
			'type_price' => $this->rule['type_price'],
			'decimal_separator'   => '.',
			'thousands_separator'   => '',
		);	
		$price = usam_get_formatted_price( $price, $currargs );					
		$out .= "<g:image_link>$image</g:image_link>\n\r";
		$out .= "<g:price>$price</g:price>\n\r";	
		$out .= "<g:availability>$available</g:availability>\n\r";	
		$out .= "<g:brand>$brand</g:brand>\n\r";	
		$out .= "</item>\n\r";
		return $out;		
	}
		
	protected function get_export_file( $xml_product ) 
	{
		$html  = "<?xml version='1.0' encoding='UTF-8'?>";		
		$html .= "<rss version='2.0' xmlns:g='http://base.google.com/ns/1.0'>";		
		$html .= "  <channel>\n\r";
		$html .= "    <title><![CDATA[" . sprintf( __('%s товары', 'usam'), get_option( 'blogname' ) ) . "]]></title>\n\r";
		$html .= "    <link>".home_url()."</link>\n\r";
		$html .= "    <description>" . __('Список товаров магазина', 'usam') . "</description>\n\r";
		$html .= $xml_product; 
		$html .= "  </channel>\n\r"; 
		$html .= "</rss>\n\r";	
		return $html;	
	}
	
	function get_form( ) 
	{				
		$this->display_form_campaign();
	}	
}
?>