<?php 
// Описание: Виджет корзины
$output = '';
if ( count( $products ) > 0 ) 
{
	$output .= '<div class="widget_products">';		
	foreach ( $products as $product ) 
	{
		$output .= '<div class="widget_products__product">';		
		if ( $show_image )
		{
			$output .= '<div class="widget_products__product_image">';
			$output .= '<a href="' . usam_product_url( $product->ID ) . '">';
			$output .= usam_get_product_thumbnail($product->ID, array($width, $height ), stripslashes( $product->post_title ) ); 
			$output .= '</a>';
			$output .= '</div>';
		}
		$output .= '<div class="widget_products__text">';
		$output .= '<div class ="widget_products__product_title"><a href="' . usam_product_url( $product->ID ) . '">'.stripslashes( $product->post_title ).'</a></div>';				
		if ( $show_price )
		{
			$output .= '<div class ="prices">';			
			$output .= '<span class = "old_price">'.usam_get_product_price_currency( $product->ID, true ).'</span>';
			$output .= '<span class = "price">'.usam_get_product_price_currency( $product->ID ).'</span>';			
			$output .= '</div>';
		}
		$output .= '</div></div>';
	}
	$output .= "</div>";
}
echo $output;