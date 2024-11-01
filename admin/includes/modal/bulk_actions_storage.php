<div id="bulk_actions_storage" class="modal fade">
	<div class="modal-header">
		<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
		<div class = "header-title"><?php _e('Массовые действия','usam'); ?></div>
	</div>	
	<div class='modal-body'>
		<div class='bulk_actions columns_2'>
			<div class='columns_2__column'>
				<div class='title'><?php _e('Свойства складов','usam') ?></div>
				<div class='edit_form js-properties'>							
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label><?php _e('Чей склад','usam') ?>:</label></div>	
						<div class='edit_form__item_option'>
							<select name='owner'>										
								<option value=''><?php _e( 'Выберите', 'usam') ?></option>				
								<option value='0'><?php _e( 'Ваш склад', 'usam') ?></option>
							</select>		
						</div>	
					</div>			
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label><?php _e('Пункт отгрузки','usam') ?>:</label></div>	
						<div class='edit_form__item_option'>
							<select name='shipping'>										
								<option value=''><?php _e( 'Выберите', 'usam') ?></option>								
								<option value='1'><?php _e( 'Да', 'usam') ?></option>	
								<option value='0'><?php _e( 'Нет', 'usam') ?></option>
							</select>		
						</div>	
					</div>	
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label><?php _e('Пункт выдачи','usam') ?>:</label></div>	
						<div class='edit_form__item_option'>
							<select name='issuing'>										
								<option value=''><?php _e( 'Выберите', 'usam') ?></option>								
								<option value='1'><?php _e( 'Да', 'usam') ?></option>	
								<option value='0'><?php _e( 'Нет', 'usam') ?></option>
							</select>		
						</div>	
					</div>	
				</div>		
			</div>
			<div class='columns_2__column'><div class='title'><?php _e('Выбранные склады','usam') ?></div><div class='selected_items modal-scroll'><div class='all_items_selected'><?php _e('все склады','usam') ?></div></div></div>
		</div>	
		<div class='modal__buttons'>
			<button id = 'modal_action' type='button' class='button-primary button'><?php _e( 'Сохранить', 'usam') ?></button>
			<button type='button' class='button' data-dismiss='modal' aria-hidden='true'><?php _e( 'Закрыть', 'usam') ?></button>
		</div>
	</div>
</div>	