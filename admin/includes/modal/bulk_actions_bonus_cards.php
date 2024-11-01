<div id="bulk_actions_bonus_cards" class="modal fade modal-medium">
	<div class="modal-header">
		<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
		<div class = "header-title"><?php _e('Добавление бонусов','usam'); ?></div>
	</div>	
	<div class='modal-body'>
		<div class='bulk_actions columns_2'>
			<div class='columns_2__column'>
				<div class='edit_form js-properties'>
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label for='property_bonus'><?php _e('Количество бонусов','usam') ?>:</label></div>	
						<div class='edit_form__item_option'>
							<input type='text' name='sum' id="property_bonus" value=''>
						</div>							
					</div>	
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label for='property_description'><?php _e('Основание','usam') ?>:</label></div>	
						<div class='edit_form__item_option'>
							<textarea name='description' id="property_description"></textarea>
						</div>	
					</div>				
				</div>
			</div>
			<div class='columns_2__column'><div class='title'><?php _e('Выбранные бонусные карты','usam') ?></div><div class='selected_items modal-scroll'><?php _e('все','usam') ?></div></div>
		</div>	
		<div class='modal__buttons'>
			<button id = 'modal_action' type='button' class='button-primary button'><?php _e( 'Сохранить', 'usam') ?></button>
			<button type='button' class='button' data-dismiss='modal' aria-hidden='true'><?php _e( 'Закрыть', 'usam') ?></button>
		</div>
	</div>
</div>