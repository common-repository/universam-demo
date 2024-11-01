<?php 
// Name: Вывод новостей
// Подключает шаблон из папки темы template-parts/content/content.php 

global $wp_query;
$args = ['posts_per_page' => $block['options']['number'], 'post_status' => 'publish', 'update_post_meta_cache' => true];
if( !empty($block['options']['ids']) )
{
	$term_ids = [];
	foreach( $block['options']['ids'] as $id )
	{
		$termchildren = get_term_children( $id, 'category' );
		$term_ids[] = $id;
		$term_ids = array_merge( $term_ids, $termchildren );	
	}
	$args['category__in'] = $term_ids;	
}	
$wp_query = new WP_Query( $args );	
if( have_posts() )
{	
	update_post_thumbnail_cache( $wp_query );
	if( !empty($block['name']) )
	{ 
		?><<?php echo $block['options']['tag_name'] ?> class='html_block__name'><?php echo $block['name'] ?></<?php echo $block['options']['tag_name'] ?>><?php
	}
	if( !empty($block['options']['description']) )
	{ 
		?><div class='html_block__description'><?php echo $block['options']['description'] ?></div><?php
	}
	?>
	<div class='<?php echo !empty($block['options']['carousel']) ? "slides js-carousel-news" : "grid_columns grid_columns_".$block['options']['columns'] ?>'>
		<?php
		while (have_posts()) :  	
			the_post(); 							
			get_template_part( 'template-parts/content/content', 'excerpt' );			
		endwhile; 
		?>	
	</div>
	<?php 
}
wp_reset_postdata();
wp_reset_query();

add_action('wp_footer', function() use($block) {	
	?> 
	<script>			
		if ( jQuery(".js-carousel-news").length )
			jQuery('.js-carousel-news').owlCarousel({autoplay:false, autoWidth:false, loop:false, nav:true, dots:false, responsive:{200:{items:2},530:{items:3},1024:{items:4}} });
	</script>	
	<style>
	#html_block_<?php echo $block['id'] ?> .owl-stage{gap:<?php echo $block['content_style']['gap'] ?>}
	#html_block_<?php echo $block['id'] ?> .grid_columns{gap:<?php echo $block['content_style']['gap'] ?>}	 
	</style>
	<?php
}, 100);
?>