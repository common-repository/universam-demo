<?php 
// Name: Вывод товаров плиткой
// Подключает шаблон из папки плагина universam\theme\grid_product.php 
// Можно скопировать в папку с темой в папку magazine, чтобы было wp-content\themes\cuba\magazine\grid_product.php 

global $product_limit;
if( !empty($block['options']['columns']) )
	$product_limit = $block['options']['columns']; //Установить количество товаров в строке из настроек блока

global $post;
if( empty($product_id) )
{
	$product_id = 0;
	if( is_single() && $post->post_type == "usam-product" )
		$product_id = $post->ID;
}
require_once( USAM_FILE_PATH . '/includes/product/products_query.class.php' );
global $wp_query;
$query = new USAM_Products_Query(['compilation' => $block['options']['compilation'], 'product_id' => $product_id, 'posts_per_page' => $block['options']['number']]);	
$wp_query = $query->query();
if( have_posts() )
{	
	?>
	<div class = 'usam_products'>
		<?php
		if( !empty($block['name']) )
		{ 
			?><<?php echo $block['options']['tag_name'] ?> class='html_block__name'><?php echo $block['name'] ?></<?php echo $block['options']['tag_name'] ?>><?php
		}
		if( !empty($block['options']['description']) )
		{ 
			?><div class='html_block__description'><?php echo $block['options']['description'] ?></div><?php
		}
		?>
		<div class='<?php echo !empty($block['options']['carousel']) ? "slides js-carousel-products" : "products_grid" ?>'>
			<?php
			while (usam_have_products()) :  	
				usam_the_product(); 			
				include( usam_get_template_file_path( 'grid_product' ) );
			endwhile; 
			?>	
		</div>
	</div>	
	<?php 
}
wp_reset_postdata();
wp_reset_query();
?>