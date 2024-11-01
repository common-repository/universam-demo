<?php require_once( 'walker-variation-checklist.php' ); ?>
<div id="product_variations">
	<h4><a href="#usam_variation_metabox" class="add_variation_set_action"><?php esc_html_e( '+ Добавить вариацию', 'usam') ?></a></h4>
	<div id="add-new-variation-set" class="edit_form">
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><input type="text" class="text-field" id="new-variation-set-name" placeholder="<?php esc_html_e( "Введите имя вариации", 'usam'); ?>"/></div>
			<div class ="edit_form__item_option"><?php esc_html_e( "Пример: цвет. Если вы хотите добавить варианты для существующего набора, вы можете ввести имя этого набора.", 'usam'); ?></div>
		</div>
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><input type="text" class="text-field" id="new-variants" placeholder="<?php esc_html_e( "Введите новые варианты", 'usam'); ?>"/></div>
			<div class ="edit_form__item_option"><?php esc_html_e( "Пример: красный, зеленый, синий. Отдельные варианты нужно разделить запятыми.", 'usam'); ?></div>
		</div>
		<div class="edit_form__buttons">
			<a class="button" href="#"><?php esc_html_e( 'Добавить новую вариацию', 'usam'); ?></a>
		</div>
	</div>
	<p><a name='variation_control'>&nbsp;</a><?php _e( 'Выберите наборы вариации и затем соответствующие варианты, которые вы хотите добавить к этому товару.', 'usam') ?></p>
	<form action="" method="post">
		<ul class="variation_checkboxes">
			<?php wp_terms_checklist( $this->parent_id, ['taxonomy' => 'usam-variation', 'walker' => new USAM_Walker_Variation_Checklist(), 'checked_ontop' => false]);	?>
		</ul>
		<p>
		<input type="hidden" name="action2" value="generate" />
		<input type="hidden" name="product_id" value="<?php echo $this->parent_id; ?>" />
		<?php wp_nonce_field( 'usam_generate_product_variations', '_usam_generate_product_variations_nonce' ); ?>
		<?php submit_button( __('Создавать вариацию', 'usam') ); ?>
		</p>
	</form>	
</div>