<div class ="settings_form">
	<div class ="edit_form">
		<div class ="edit_form__item" v-for="(property, k) in properties">
			<div class ="edit_form__item_name" v-html="property.name+':'"></div>
			<div class ="edit_form__item_option">
				<?php include( USAM_FILE_PATH . '/admin/templates/template-parts/data-field.php' ); ?>
			</div>
		</div>	
	</div>
	<div class="tab_buttons">
		<button id="action-submit" class="button button-primary" @click="save"><?php _e("Сохранить","usam"); ?></button>
	</div>
</div>