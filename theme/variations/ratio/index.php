<?php
/*
Theme Name:Ratio
Author:universam
Version:1.1
*/
?>
<div class="variations_ratio variations__item">
	<?php if ( get_option('usam_show_name_variation') ) { ?>
		<label class="variations__item_name" for="<?php echo usam_vargrp_form_id(); ?>"><?php echo usam_the_vargrp_name(); ?>:</label>
	<?php } ?>	
	<div class="variations__item_values">
		<?php while (usam_have_variations()) : 
			usam_the_variation(); 
			$color = usam_get_term_metadata(usam_the_variation_id(), 'color');
			$color = $color == ''?'ffffff':$color;
			?>
			<div id="product-variation-<?php echo usam_the_variation_id(); ?>" class="variations__item_value <?php echo !usam_the_variation_out_of_stock()?"variations__item_value_disabled":'js-product-variation'; ?>" vargrp_id="<?php echo usam_vargrp_id(); ?>" variation_id="<?php echo usam_the_variation_id(); ?>">
				<div class="variations__color_wrap"><div class="variations__color" style="background-color:#<?php echo $color; ?>;"></div></div>
				<div class="variations__text"><?php echo usam_the_variation_name(); ?></div>
			</div>
		<?php endwhile; ?>	
	</div>
</div> 