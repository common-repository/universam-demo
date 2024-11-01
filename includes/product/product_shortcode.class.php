<?php
class USAM_Product_Shortcode
{		
	protected $product_id;	
	public function __construct( $product_id ) 
	{	
		$this->product_id = $product_id;	
	}
	
	private function get_shortcode_html( $shortcode ) 
	{
		$content = '';
		switch( $shortcode )
		{						
			case 'characteristics': 
				$content = usam_display_product_attributes();
				$product_components = usam_display_product_components();
				if ( $product_components != '' )
				{		
					$content .= $product_components;
				}				
			break;
			case 'description':											
				$content = get_the_excerpt( $this->product_id );				
				if ( $content )
					$content = "<div itemprop='description'>".$content."</div>";
			break;
			case 'brand':					
				$product_id = get_the_ID();
				$brands = get_the_terms( $product_id, 'usam-brands' );
				if ( !empty($brands) )
				{
					$brand = $brands[0];
					if ( !empty($brand->description) )
					{
						$link = usam_get_term_metadata($brand->term_id, 'link');	
						$content = "<div class='brand_header'>
							<h4>".__('Бренд','usam').": <a title ='".__('Посмотреть все товары бренда','usam')."' href='".get_term_link( $brand->term_id, 'usam-brands' )."'>".$brand->name."</a></h4>";
							if ( $link != '' )
							{
								$host = parse_url($link, PHP_URL_HOST);
								$content .= "<p>".__('Сайт производителя','usam').": <a target='_blank' title ='".__('Перейти на официальный сайт','usam')."' href='".$link."'>".$host."</a></p>";
							}	
						$content .= "</div><p>$brand->description</p>";
					}
				}
			break;
			case 'posts':			
				ob_start();	
				usam_include_template_file('product-posts', 'template-parts');
				$content = ob_get_clean();
			break;
			case 'comment':		
				$customer_reviews = new USAM_Customer_Reviews_Theme();
				$content = "<div class='product_reviews'>";
				$content .= $customer_reviews->show_button_reviews_form( 'top' );
				$content .= $customer_reviews->show_reviews_form( 'top' );								
				$content .= $customer_reviews->output_reviews_show(['page_id' => $this->product_id, 'per_page' => 3, 'pagination' => 0]);
				$content .= $customer_reviews->show_button_reviews_form( 'bottom' );
				$content .= $customer_reviews->show_reviews_form( 'bottom' );
				$content .= $customer_reviews->aggregate_footer(); 
				$content .= "</div>";
			break;						
		}			
		return $content;
	}

	// из аргументов собрать строку
	public function get_html( $html ) 
	{
		preg_match_all('/%([\w-]*)%/m', $html, $shortcodes);	
		if( !empty($shortcodes[1]) )
		{
			foreach( $shortcodes[1] as $shortcode ) 
			{
				$shortcode_html = $this->get_shortcode_html( $shortcode );
				if( !$shortcode_html )
				{
					$shortcode_html = usam_get_product_attribute( $this->product_id, $shortcode );					
					if ( !$shortcode_html )
						$shortcode_html = usam_get_product_property( $this->product_id, $shortcode );
				}
				if ( $shortcode_html !== false && strpos($html, "%$shortcode%") !== false) 	
					$html = str_replace("%$shortcode%", $shortcode_html, $html);
			}
		}		
		return $html;
	}	
}

function usam_get_product_shortcode() 
{
	$labels = [
		'characteristics' => __('Показать характеристики', 'usam'), 
		'content' => __('Информация', 'usam'),
		'description' => __('Показать описание', 'usam'), 
		'comment' => __('Включить комментарии', 'usam'), 
		'brand' => __('Показать описание бренда', 'usam')
	];
	return $labels;
}	