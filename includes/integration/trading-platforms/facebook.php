<?php 
require_once( USAM_FILE_PATH . '/includes/exchange/trading_platforms.class.php' );
/*
  Name: Facebook Маркет
  Icon: facebook
 */
class USAM_Facebook_Exporter extends USAM_Trading_Platforms_Exporter
{		
	protected function get_export_product( $post ) 
	{	
		$brand = get_the_terms($post->ID, 'usam-brands');	
		$price = usam_get_product_price( $post->ID, $this->rule['type_price'] );	
		if ( empty($price) )					
			return '';	
								
		$old_price = usam_get_product_old_price( $post->ID, $this->rule['type_price'] );				
		$product_description = !empty($this->rule['product_description']) ? usam_get_product_attribute_display( $post->ID, $this->rule['product_description'] ) : $post->post_excerpt; // <g:item_group_id>SKU-123123</g:item_group_id>
		$result = "<item>           
            <g:gtin>".usam_get_product_meta( $post->ID, 'barcode' )."</g:gtin>";
			$google_product_category = usam_get_product_attribute_display( $post->ID, 'google_product_category' );
			if ( $google_product_category )	
				$result .= "<g:google_product_category>$google_product_category</g:google_product_category>";      
            $result .= "<g:id>".usam_get_product_meta( $post->ID, 'sku' )."</g:id>
            <g:title>".$this->get_product_title( $post )."</g:title>            
            <g:description><![CDATA[".$this->text_decode( $product_description )."]]></g:description>
            <g:link>".$this->get_product_url( $post->ID )."</g:link>"; 
			$attachments = usam_get_product_images( $post->ID );	
			foreach ($attachments as $key => $attachment)
			{						
				$image = wp_get_attachment_image_src($attachment->ID, 'full');			
				if ( $key )
					$result .= "<additional_image_link>".$image[0]."</additional_image_link>"."\n";
				else
					$result .= "<g:image_link>".$image[0]."</g:image_link>"."\n";
			}
			$color = usam_get_product_attribute_display( $post->ID, 'color' );
			if ( $color )
				$result .= "<color>".$color."</color>";          
                    
			$product_attributes = usam_get_product_attributes_display( $post->ID, ['show_all' => true] );
			foreach ($product_attributes as $attribute)
			{
				if ( $attribute['parent'] && in_array($attribute['term_id'], $this->rule['product_characteristics']) )
					$result .= "<additional_variant_attribute><label>".$attribute['name']."</label><value>".htmlspecialchars(implode(',',$attribute['value']))."</value></additional_variant_attribute>"."\n";
			}                    
            if ( !empty($brand[0]) )
				$result .= "<g:brand>".$brand[0]->name."</g:brand>";
			
           $result .= " <g:condition>New</g:condition>            
            <g:availability>".($this->get_availability( $post->ID ))."</g:availability>"."\n";          
            
			if ( $old_price )
				$result .= "<g:price>".$old_price." ".$this->rule['currency']."</g:price>\n<g:sale_price>".$price." ".$this->rule['currency']."</g:sale_price>";
			else
				$result .= "<g:price>".$price." ".$this->rule['currency']."</g:price>";
			$result .= "			
        </item>";
		return $result;				
	}
	
	protected function get_export_file( $offers ) 
	{				
		$xml = '<?xml version="1.0" encoding="utf-8"?>
		<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0" xmlns:atom="http://www.w3.org/2005/Atom">
			<channel> 
				<title>'.get_bloginfo('name').'</title>
				<description>Product Feed for Facebook</description> 
				<link>'.home_url().'</link>
				<atom:link href="'.$this->url.'" rel="self" type="application/rss+xml" />			
				'.$offers.'
			</channel>
		</rss>';	
		return $xml;	
	}
	
	protected function get_default_option( ) 
	{
		return [];
	}
	
	function get_form( ) 
	{				
		$this->display_form_campaign();
	}	
	
	function save_form( ) 
	{ 
		return $new_rule;
	}
}
?>