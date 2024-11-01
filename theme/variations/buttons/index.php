<?php
/*
Theme Name:Кнопки
Author:universam
Version:1.1
*/
?>

<div class="variations_buttons variations__item">
	<?php if ( get_option('usam_show_name_variation') ) { ?>
		<label class="variations__item_name" for="<?php echo usam_vargrp_form_id(); ?>"><?php echo usam_the_vargrp_name(); ?>: <span class="variations__item_variation_name js-name-selected-variation-<?php echo usam_vargrp_id(); ?>"></span></label>
	<?php } ?>	
	<div class="variations__item_values">
		<?php while (usam_have_variations()) : usam_the_variation(); ?>
			<div id="product-variation-<?php echo usam_the_variation_id(); ?>" class="variations__item_value <?php echo !usam_the_variation_out_of_stock()?"variations__item_value_disabled":'js-product-variation'; ?>" vargrp_id="<?php echo usam_vargrp_id(); ?>" variation_id="<?php echo usam_the_variation_id(); ?>" variation_name="<?php echo usam_the_variation_name(); ?>"><?php echo usam_the_variation_name(); ?></div>
		<?php endwhile; ?>	
	</div>
</div> 