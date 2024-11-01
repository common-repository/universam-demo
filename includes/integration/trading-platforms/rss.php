<?php 
require_once( USAM_FILE_PATH . '/includes/exchange/trading_platforms.class.php' );
/*
  Name: RSS канал
 */
class USAM_RSS_Exporter extends USAM_Trading_Platforms_Exporter
{		
	protected function get_export_product( $post ) 
	{	
		$out  = "    <item>\n\r";
		$out .= "      <title><![CDATA[".$post->ID.' '.$this->get_product_title( $post )."]]></title>\n\r";
		$out .= "      <link>".$this->get_product_url( $post->ID )."</link>\n\r";
		$out .= "      <description><![CDATA[ ".$this->text_decode( $post->post_content )." ]]></description>\n\r";
		$out .= "      <pubDate>".$post->post_modified_gmt."</pubDate>\n\r";		
		$product_categories = get_the_terms($post->ID, 'usam-category');
		if ( !empty($product_categories[0]) )					
		{					
			$out .= "<category>".$product_categories[0]->name."</category>\n\r";
		}				
		$image = usam_get_product_thumbnail_src($post->ID, 'single');				
		
		$price = usam_get_product_price( $post->ID, $this->rule['type_price'] );	
		$currargs = ['currency_symbol' => false, 'display_decimal_point' => true, 'currency_code' => false, 'type_price' => $this->rule['type_price']];
		$price = usam_get_formatted_price( $price, $currargs );
		$out .= "<product:price>".$price."</product:price>\n\r";
		$out .= "<enclosure url='$image' />\n\r";	
		$out .= "</item>\n\r";
		return $out;		
	}	
	
	protected function get_export_file( $xml_product ) 
	{	
		$html  = "<?xml version='1.0' encoding='UTF-8' ?>\n\r";		
		$html .= "<rss version='2.0' xmlns:product='http://www.buy.com/rss/module/productV2/'>\n\r";
		$html .= "  <channel>\n\r";
		$html .= "    <title><![CDATA[" . sprintf( __('%s товары', 'usam'), get_option( 'blogname' ) ) . "]]></title>\n\r";
		$html .= "    <link>".home_url()."</link>\n\r";
		$html .= "    <description>" . __('Это список товаров магазина для RSS ленты', 'usam') . "</description>\n\r";
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