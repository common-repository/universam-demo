<div id="bulk_actions_terms" class="modal fade">
	<div class="modal-header">
		<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
		<div class = "header-title"><?php _e('Изменить термин у товаров','usam'); ?></div>
	</div>
	<div class='modal-body'>
		<div class='operation_box'>
			<label><?php _e('Выберете операцию', 'usam'); ?>: </label>
			<select id='operation' name='operation'>
				<option value='0'><?php _e('Перенести', 'usam'); ?></option>
				<option value='1'><?php _e('Добавить', 'usam'); ?></option>		
				<option value='del'><?php _e('Удалить', 'usam'); ?></option>
			</select>
		</div>
		<div class='product_bulk_actions'>
			<div class='colum1'>
				<div class='title'><?php _e('Термины для изменения','usam') ?></div>
				<div class='edit_form'>
					<?php 
					foreach ( ['category', 'brands', 'category_sale', 'catalog', 'selection'] as $tax_slug )
					{		
						$tax_obj = get_taxonomy( 'usam-'.$tax_slug );
						?>
						<div class='edit_form__item'>
							<div class='edit_form__item_name'><label><?php echo $tax_obj->labels->menu_name; ?>:</label></div>
							<div class='edit_form__item_option'>
								<select id='<?php echo $tax_slug; ?>' class ='chzn-select'>
									<option value=''><?php _e('Выберите', 'usam'); ?></option>
									<?php echo wp_terms_checklist( 0, ['descendants_and_self' => 0, 'walker' => new Walker_Category_Select(), 'taxonomy' => 'usam-'.$tax_slug, 'checked_ontop' => false, 'echo' => false]); ?>
								</select>
							</div>
						</div>
						<?php
					}
					?>
				</div>
			</div>
			<div class='colum2'><div class='title'><?php _e('Выбранные товары','usam'); ?></div><div class='products modal-scroll'></div></div>
		</div>
		<div class='modal__buttons'>
			<button id = 'modal_action' type='button' class='button-primary button'><?php _e('Выполнить', 'usam'); ?></button>
			<button type='button' class='button' data-dismiss='modal' aria-hidden='true'><?php _e('Закрыть', 'usam'); ?></button>
		</div>
	</div>
</div>