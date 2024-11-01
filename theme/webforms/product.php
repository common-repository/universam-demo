<?php
// Name: Форма обратной связи о продукте
?>
<div class = "webform webform_product">
	<?php
	if ( !empty($post_id) )
		$post = get_post($post_id);
	if ( !empty($post) && $post->post_type == 'usam-product' )
	{
		?>
		<div class = "webform__header">
			<div class = "webform__image"><?php echo usam_get_product_thumbnail( $post_id, 'product-thumbnails' ); ?></div>		
			<div class="webform__text">
				<div class="webform__title"><?php echo get_the_title( $post_id ); ?></div>		
				<div class="webform__price"><?php echo usam_get_product_price_currency( $post_id ); ?></div>
				<div class="webform__description"><?php echo $description; ?></div>		
			</div>
		</div>	
		<?php
	}	
	?>
	<div v-if="!message_result">
		<div class ="view_form">	
			<div class ="view_form__row" v-for="(property, k) in properties">				
				<div :class ="'view_form__'+property.field_type" v-if="property.field_type=='click_show'" @click="property.show=property.show?0:1"><a>{{property.name}}</a></div>
				<div class ="view_form__name" v-else-if="property.field_type!='none' && property.field_type!='one_checkbox' && property.field_type!='personal_data' && property.field_type!='agreement'">{{property.name}} <span v-if="property.mandatory">*</span></div>
				<div class ="view_form__option">
					<?php usam_include_template_file('property', 'template-parts'); ?>
				</div>
			</div>			
		</div>	
		<div class="modal__buttons">
			<button id = "modal_action" type="button" class="button main-button" @click="send_form" :disabled="send" v-html="data.button_name"></button>	
		</div>
	</div>
	<div class="message_result" v-html="message_result" v-else></div>
</div>	
<?php	