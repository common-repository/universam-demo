<?php
/*
Theme Name:Фотографии
Author:universam
Version:1.1
*/
?>
<div class="variations_fotos variations__item">
	<?php if ( get_option('usam_show_name_variation') ) { ?>
		<label class="variations__item_name" for="<?php echo usam_vargrp_form_id(); ?>"><?php echo usam_the_vargrp_name(); ?>: <span class="variations__item_variation_name js-name-selected-variation-<?php echo usam_vargrp_id(); ?>"></span></label>
	<?php } ?>	
	<div class="variations__item_values">
		<?php 
		while (usam_have_variations()) : 
			usam_the_variation(); 
			$term_id = usam_the_variation_id();
			$attachment_id = (int)get_term_meta($term_id, 'thumbnail', true);
			$variation_img = wp_get_attachment_image_url($attachment_id, array(50, 50));
			if(empty($variation_img)){
				$variation_img = USAM_CORE_IMAGES_URL . '/no-image-uploaded-100x100.png';	
			}			
			if(usam_the_variation_id() === 0) { continue; }
			?>
			<div id="product-variation-<?php echo usam_the_variation_id(); ?>" class="variations__item_value <?php echo !usam_the_variation_out_of_stock()?"variations__item_value_disabled":'js-product-variation'; ?>" vargrp_id ="<?php echo usam_vargrp_id(); ?>" variation_id="<?php echo usam_the_variation_id(); ?>" variation_name="<?php echo usam_the_variation_name(); ?>">
				<img src="<?php echo $variation_img; ?>" alt="<?php echo usam_the_vargrp_name(); ?>">
			</div>
		<?php endwhile; ?>	
	</div>
</div> 