<?php
/*
Theme Name:SELECT
Author:universam
Version:1.1
*/
?>
<div class="variations_select variations__item">
	<?php if ( get_option('usam_show_name_variation') ) { ?>	
		<label class="variations__item_name" for="<?php echo usam_vargrp_form_id(); ?>"><?php echo usam_the_vargrp_name(); ?>: <span class="variations__item_variation_name js-name-selected-variation-<?php echo usam_vargrp_id(); ?>"></span></label>
	<?php } ?>	
	<div class="variations__item_value">
		<select class="usam_select_variation js-product-variation" id="<?php echo usam_vargrp_form_id(); ?>" vargrp_id ="<?php echo usam_vargrp_id(); ?>" variation_id="<?php echo usam_the_variation_id(); ?>" variation_name="<?php echo usam_the_variation_name(); ?>">
			<?php if ( get_option('usam_show_name_variation') ) { ?>	
				<option value="0"><?php _e( '- Вариант -', 'usam') ?></option>
			<?php } else { ?>	
				<option value="0"><?php echo usam_the_vargrp_name(); ?></option>
			<?php } ?>	
			<?php while (usam_have_variations()) : usam_the_variation(); ?>
				<option value="<?php echo usam_the_variation_id(); ?>" <?php echo !usam_the_variation_out_of_stock()?"disabled='disabled'":''; ?>><?php echo usam_the_variation_name(); ?></option>
			<?php endwhile; ?>
		</select>
	</div>
</div> 