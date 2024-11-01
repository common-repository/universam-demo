<?php 
require_once( USAM_FILE_PATH . '/includes/exchange/trading_platforms.class.php' );
/*
  Name: Rozetka Маркетплейс
 */
class USAM_rozetka_Exporter extends USAM_Trading_Platforms_Exporter
{			
	protected function get_export_product( $post ) 
	{	
		$result = $this->get_xml_product( $post );
		return $result;				
	}
	
	protected function get_xml_product( $post ) 
	{	
		$brand = get_the_terms($post->ID, 'usam-brands');	
		$price = usam_get_product_price( $post->ID, $this->rule['type_price'] );	
		if ( empty($price) )					
			return '';		
		
		if ( empty($this->rule['product_characteristics']) )					
			return '';	
		
		$product_categories = get_the_terms($post->ID, 'usam-category');
		if ( empty($product_categories[0]) )					
			return '';			
				
		$stock = usam_get_product_stock($post->ID, 'stock');
		$old_price = usam_get_product_old_price( $post->ID, $this->rule['type_price'] );
		$available = $stock > 0?'true':'false';		
		$content = empty($post->post_excerpt)?$post->post_content:$post->post_excerpt;
		$result = "<offer id='$post->ID' available='$available'>
						<url>".$this->get_product_url($post->ID)."</url>
						<stock_quantity>$stock</stock_quantity>";
		if ( $old_price )
			$result .= "<price>$price</price>\n<price_old>$old_price</price_old>";
		else
			$result .= "<price>$price</price>";	

		$result .= "<currencyId>".$this->rule['currency']."</currencyId>";	

		$attachments = usam_get_product_images( $post->ID );
		foreach ($attachments as $attachment)
		{						
			$image = wp_get_attachment_image_src($attachment->ID, 'full');
			if ( !empty($image[0]) )
				$result .= "<picture>".$image[0]."</picture>"."\n";;
		}		
		$product_attributes = usam_get_product_attributes_display( $post->ID, ['show_all' => true] );
		foreach ($product_attributes as $attribute)
		{
			if ( $attribute['parent'] && in_array($attribute['term_id'], $this->rule['product_characteristics']) )
				$result .= "<param name='".$attribute['name']."' valueid='".$attribute['term_id']."'><![CDATA[".implode('<br/>',$attribute['value'])."]]></param>";
		}			
		if ( !empty($brand[0]) )
			$result .= "<vendor>".$this->text_decode($brand[0]->name)."</vendor>";
		$result .= "<categoryId>".$product_categories[0]->term_id."</categoryId>
					<article>".usam_get_product_meta( $post->ID, 'sku' )."</article>						
					<name>".$this->get_product_title( $post )."</name>
					<description><![CDATA[".$content."]]></description>							
				</offer>\n";
		return $result;				
	}

	protected function list_categories( $category_id = 0 )
	{	
		$output = '';
		$category_list = get_terms( array('hide_empty' => 0, "taxonomy" => "usam-category", 'child_of' => $category_id ) );	
		if($category_list != null) 
		{
			foreach((array)$category_list as $category) 
			{
				$ancestors = usam_get_ancestors( $category->term_id );	
				if ( $ancestors )
					$parentId = 'rz_id="'.end($ancestors).'"';
				else
					$parentId = '';
				$output .= '<category id="'.$category->term_id.'" '.$parentId.'>'.$category->name.'</category>'."\n";
			}
		}
		return $output;
	}
	
	protected function get_export_file( $offers ) 
	{				
		if ( !empty($this->rule['category']) )
		{
			$categories = '';
			$ids = [];
			foreach($this->rule['category'] as $category_id)
			{
				$ancestors = usam_get_ancestors( $category_id );				
				$ids[] = $ancestors?current($ancestors):$category_id; 
			}	
			$ids = array_unique($ids);			
			$category_list = get_terms( array('hide_empty' => 0, "taxonomy" => "usam-category", 'include' => $ids ) );
			foreach($category_list as $category)
			{
				$categories .= '<category id="'.$category->term_id.'">'.$category->name.'</category>';
				$categories .= $this->list_categories( $category->term_id );		
			}
		}
		else
			$categories = $this->list_categories();	
		
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
			<!DOCTYPE yml_catalog SYSTEM "shops.dtd">
			<yml_catalog date="'.date_i18n( "Y-m-d H:i" ).'">
			<shop>
				<name>'.get_bloginfo('name').'</name>			
				<url>'.home_url().'</url>
				<currencies>
					<currency id="'.$this->rule['currency'].'" rate="1"/>
				</currencies>
				<categories>'.$categories.'</categories>"
				<offers>'.$offers.'
				</offers>
			</shop>
		</yml_catalog>';	
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
		return [];
	}
}
?>