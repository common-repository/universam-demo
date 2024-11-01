<?php 
// Name: Вывод плитки категорий

$args = ['taxonomy' => $block['options']['tax'], 'orderby' => 'sort', 'status' => 'publish', 'meta_query' => [['key' => 'thumbnail', 'compare' => 'EXISTS'], ['key' => 'thumbnail', 'value' => 0, 'compare' => '!=']]];
if ( !empty($block['options']['ids']) )
{
	$args['include'] = $block['options']['ids'];
	$args['meta_query'] = [];
}
elseif ( $block['options']['variant'] == 'v1' )
	$args['number'] = 5;
elseif ( $block['options']['variant'] == 'v2' )
	$args['number'] = 3;
elseif ( $block['options']['variant'] == 'v3' )
	$args['number'] = 4;
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
	<div class="html_block__categories_<?php echo $block['options']['variant'] ?> <?php echo !empty($block['options']['carousel']) ? "html_block__carousel slides js-carousel-terms" : "html_block__categories" ?>" style="gap:<?php echo $block['content_style']['gap'] ?>"> 
		<?php
		$style = "";
		if( !empty($block['content_style']['font-size']) )
			$style .= 'font-size:'.$block['content_style']['font-size'].';';
		if( !empty($block['content_style']['font-weight']) )
			$style .= 'font-weight:'.$block['content_style']['font-weight'].';';
		if( !empty($block['content_style']['color']) )
			$style .= 'color:'.$block['content_style']['color'].';';
		if( !empty($block['content_style']['text-transform']) )
			$style .= 'text-transform:'.$block['content_style']['text-transform'].';';	
		if( !empty($block['content_style']['text-align']) )
			$style .= 'text-align:'.$block['content_style']['text-align'].';';	

		$class_item = "";
		if( !empty($block['content_style']['effect']) )
			$class_item .= $block['content_style']['effect'].'_effect';			
		foreach ($terms as $k => $term) 
		{ 
			?>
			<div class = "html_block__category <?php echo $block['options']['variant'] == 'v5' ? 'change_photo' : ''; ?> <?php echo $class_item; ?>">
				<a href="<?php echo get_term_link($term->term_id, $term->taxonomy) ?>" class="html_block__category_image <?php echo $block['options']['variant'] == 'v5' ? 'change_photo__container' : ''; ?>">
					<?php 
					if ( $block['options']['variant'] == 'v5' )
					{
						$attachments = usam_term_images($term->term_id, "full");
						foreach ( $attachments as $k => $attachment ) 
						{
							?><img loading='lazy' src="<?php echo $attachment->url; ?>" alt="<?php echo $term->name; ?>"><?php
						}
					}
					else
						echo usam_term_image($term->term_id, 'full', ['alt' => $term->name] );
					?>
				</a>
				<a class="html_block__category_title" href="<?php echo get_term_link($term->term_id, $term->taxonomy) ?>" style="<?php echo $style; ?>"><?php echo $term->name; ?></a>
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
		if ( jQuery(".js-carousel-terms").length )
			jQuery('.js-carousel-terms').owlCarousel({autoplay:false, autoWidth:false, loop:false, nav:true, dots:false, responsive:{200:{items:2},530:{items:3},768:{items:<?php echo $block['options']['columns']>3?$block['options']['columns']-1:4; ?>},1200:{items:<?php echo $block['options']['columns']>6?$block['options']['columns']-2:5; ?>},1300:{items:<?php echo $block['options']['columns'] ?>}} });
	</script>	
	<style>
	#html_block_<?php echo $block['id'] ?> .owl-stage{gap:<?php echo $block['content_style']['gap'] ?>}
	</style>
	<?php
}, 100);
?>
