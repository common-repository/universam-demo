<?php 
// Name: Вывод плитки брендов

$args = ['taxonomy' => 'usam-brands', 'orderby' => 'sort', 'status' => 'publish', 'meta_query' => [['key' => 'thumbnail', 'value' => 0, 'compare' => '!=']]];
if ( !empty($block['options']['ids']) )
	$args['include'] = $block['options']['ids'];
else
	$args['number'] = 15;
$terms = get_terms( $args );
usam_update_terms_thumbnail_cache( $terms );

if( !empty($terms) )
{
	if( !empty($block['name']) )
	{ 
		?><<?php echo $block['options']['tag_name'] ?> class='html_block__name'><?php echo $block['name'] ?></<?php echo $block['options']['tag_name'] ?>><?php
	}
	if( !empty($block['options']['description']) )
	{ 
		?><div class='html_block__description'><?php echo $block['options']['description'] ?></div><?php
	}
	?>		
	<div class="html_block__brands <?php echo !empty($block['options']['carousel'])? 'slides js-brands' : 'html_block__brands_grid'; ?>" style="gap:<?php echo $block['content_style']['gap'] ?>"> 
		<?php		
		foreach ($terms as $k => $term) 
		{
			?>
			<div class = "html_block__brand">
				<a href="<?php echo get_term_link($term->term_id, 'usam-brands') ?>" class="html_block__brand_image">
					<?php echo usam_term_image($term->term_id, 'full', ['alt' => $term->name] ); ?>
				</a>
			</div>
			<?php
		}
		?>
	</div>
	<?php
}
add_action('wp_footer', function() use($block) {	
	?> 
	<script>			
		if ( jQuery(".js-brands").length )
			jQuery('.js-brands').owlCarousel({autoplay:false, autoWidth:false, loop:false, nav:true, dots:false, responsive:{200:{items:2},530:{items:3},768:{items:4},1200:{items:5},1300:{items:7}} });
	</script>	
	<style>
	#html_block_<?php echo $block['id'] ?> .owl-stage{gap:<?php echo $block['content_style']['gap'] ?>}
	</style>
	<?php
}, 100);
?>


