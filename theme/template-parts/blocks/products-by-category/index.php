<?php 
// Name: Вывод товаров с разбивкой по категориям

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
	<div class = 'by_categories_list usam_products'>
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
		<div class='usam_tabs'>
			<?php 
			$products_ids = array();
			while (have_posts()) :  	
				the_post(); 
				$products_ids[] = get_the_ID();				
			endwhile; 
			$terms = wp_get_object_terms( $products_ids, 'usam-category', ['fields' => 'all_with_object_id', 'orderby' => 'name', 'update_term_meta_cache' => false]);
			$categories = [];	
			?>
			<div class="header_tab by_categories_list__categories">	
				<?php
				$object_ids = array();
				$i = 0;
				foreach ( $terms as $term ) 
				{			
					if ( !in_array( $term->term_id, $categories) && $i != 5 )
					{
						?><a href='#by_categories_list__category_tab-<?php echo $term->term_id; ?>'  class="tab by_categories_list__category"><?php echo $term->name; ?></a><?php
						$i++;					
					}
					$categories[] = $term->term_id;
					$object_ids[$term->term_id][] = $term->object_id;
				}
				?>
			</div>
			<div class = "countent_tabs">		
				<?php
				foreach ( $object_ids as $term_id => $products_ids ) 
				{	
					?>
					<div id = "by_categories_list__category_tab-<?php echo $term_id; ?>" class = "tab by_categories_list__category_tab">
						<div class = "products_grid">
							<?php
							while (have_posts()) : usam_the_product(); 
								$id = get_the_ID();							
								if ( in_array($id, $products_ids) )
								{
									include( usam_get_template_file_path( 'grid_product' ) );
								}
							endwhile; 					
							?>
						</div>
					</div>
					<?php 
				}
				?>
			</div>
		</div>	
	</div>	
	<?php 
}
wp_reset_postdata();
wp_reset_query();
?>