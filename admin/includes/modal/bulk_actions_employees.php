<div id="bulk_actions_employees" class="modal fade">
	<div class="modal-header">
		<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
		<div class = "header-title"><?php _e('Массовые действия','usam'); ?></div>
	</div>	
	<div class='modal-body'>
		<div class='bulk_actions columns_2'>
			<div class='columns_2__column'>
				<div class='title'><?php _e('Свойства контактов','usam') ?></div>
				<div class='edit_form js-properties'>
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label for='property_appeal'><?php _e('Отображение','usam') ?>:</label></div>	
						<div class='edit_form__item_option'>
							<select id='property_appeal' name='appeal'>
								<option value=''><?php _e( 'Выберите', 'usam') ?></option>			
								<option value="lastname_f_p"><?php _e('Фамилия И. О.','usam'); ?></option>
								<option value="lastname_firstname_patronymic"><?php _e('Фамилия Имя Отчество','usam'); ?></option>
								<option value="firstname_patronymic"><?php _e('Фамилия Имя','usam'); ?></option>
								<option value="firstname"><?php _e('Имя','usam'); ?></option>
								<option value="lastname"><?php _e('Фамилия','usam'); ?></option>
							</select>	
						</div>							
					</div>	
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label for='property_manager'><?php _e('Ответственный менеджер','usam') ?>:</label></div>	
						<div class='edit_form__item_option'>
							<?php echo usam_select_manager('', array('id' => 'property_manager', 'name' => 'manager_id') ); ?>	
						</div>	
					</div>						
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label for='property_status'><?php _e('Статус','usam') ?>:</label></div>	
						<div class='edit_form__item_option'>
							<select id='property_status' name='status'>										
								<option value=''><?php _e( 'Выберите', 'usam') ?></option>									
								<?php
								$statuses = usam_get_object_statuses_by_type( 'employee' );
								foreach ( $statuses as $status ) 
								{
									?><option value='<?php echo $status->internalname; ?>'><?php echo $status->name; ?></option><?php
								}							
								?>
							</select>		
						</div>	
					</div>	
				</div>
			</div>
			<div class='columns_2__column'><div class='title'><?php _e('Выбранные сотрудники','usam') ?></div><div class='selected_items modal-scroll'><div class='all_items_selected'><?php _e('все сотрудники','usam') ?></div></div></div>
		</div>	
		<div class='modal__buttons'>
			<button id = 'modal_action' type='button' class='button-primary button'><?php _e( 'Сохранить', 'usam') ?></button>
			<button type='button' class='button' data-dismiss='modal' aria-hidden='true'><?php _e( 'Закрыть', 'usam') ?></button>
		</div>
	</div>
</div>