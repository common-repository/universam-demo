<div id="bulk_actions_coupons" class="modal fade">
	<div class="modal-header">
		<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
		<div class = "header-title"><?php _e('Массовые действия','usam'); ?></div>
	</div>	
	<div class='modal-body'>
		<div class='bulk_actions columns_2'>
			<div class='columns_2__column'>
				<div class='title'><?php _e('Свойства купонов','usam') ?></div>
				<div class='edit_form js-properties'>
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label for='property_interval'><?php _e('Начало','usam') ?>:</label></div>	
						<div class='edit_form__item_option'>
							<?php usam_display_datetime_picker( 'start' ); ?>
						</div>	
					</div>		
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label for='property_interval'><?php _e('Конец','usam') ?>:</label></div>	
						<div class='edit_form__item_option'>
							<?php usam_display_datetime_picker( 'end' ); ?>
						</div>	
					</div>
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label for='property_value'><?php _e('Скидка','usam') ?>:</label></div>	
						<div class='edit_form__item_option'>
							<input type='text' id="property_value" name='value' size='10'/>
						</div>	
					</div>			
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><?php _e('Активность','usam') ?>:</div>	
						<div class='edit_form__item_option'>
							<select name='active'>										
								<option value=''><?php _e( 'Выберите', 'usam') ?></option>					
								<option value='1'><?php _e( 'Активировать', 'usam') ?></option>		
								<option value='0'><?php _e( 'Отключить', 'usam') ?></option>		
							</select>	
						</div>	
					</div>	
				</div>
			</div>
			<div class='columns_2__column'><div class='title'><?php _e('Выбранные купоны','usam') ?></div><div class='selected_items modal-scroll'><div class='all_items_selected'><?php _e('все купоны','usam') ?></div></div></div>
		</div>	
		<div class='modal__buttons'>
			<button id = 'modal_action' type='button' class='button-primary button'><?php _e( 'Сохранить', 'usam') ?></button>
			<button type='button' class='button' data-dismiss='modal' aria-hidden='true'><?php _e( 'Закрыть', 'usam') ?></button>
		</div>	
	</div>
</div>