<div id="bulk_actions_orders" class="modal fade">
	<div class="modal-header">
		<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
		<div class = "header-title"><?php _e('Массовые действия','usam'); ?></div>
	</div>	
	<div class='modal-body'>
		<div class='bulk_actions columns_2'>
			<div class='columns_2__column'>
				<div class='title'><?php _e('Свойства документа','usam') ?></div>
				<div class='edit_form js-properties modal-scroll'>						
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><?php _e('Ответственный менеджер','usam') ?>:</div>	
						<div class='edit_form__item_option'>
							<select id='property_manager' name='manager_id'>										
								<option value=''><?php _e( 'Выберите', 'usam') ?></option>		
								<option value='0'><?php _e( 'Нет', 'usam') ?></option>								
								<?php				
								$users = get_users(['fields' => array('ID','display_name'), 'role__in' => ['editor','shop_manager','administrator', 'shop_crm'], 'orderby' => 'nicename']);
								foreach ( $users as $user )
								{
									?><option value='<?php echo $user->ID; ?>'><?php echo $user->display_name; ?></option><?php
								}								
								?>
							</select>	
						</div>	
					</div>
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><?php _e('Статус','usam') ?>:</div>	
						<div class='edit_form__item_option'>
							<select id='property_status' name='status'>										
								<option value=''><?php _e( 'Выберите', 'usam') ?></option>									
								<?php
								$statuses = usam_get_object_statuses_by_type( 'order' );
								foreach ( $statuses as $status ) 
								{
									?><option value='<?php echo $status->internalname; ?>'><?php echo $status->name; ?></option><?php
								}							
								?>
							</select>		
						</div>	
					</div>						
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><?php _e('Статус выгрузки','usam') ?>:</div>	
						<div class='edit_form__item_option'>
							<select name='exchange'>										
								<option value=''><?php _e( 'Выберите', 'usam') ?></option>
								<option value='1'><?php _e( 'Да', 'usam') ?></option>
								<option value='0'><?php _e( 'Нет', 'usam') ?></option>											
							</select>		
						</div>	
					</div>									
				</div>
			</div>
			<div class='columns_2__column'><div class='title'><?php _e('Выбранные документы','usam') ?></div><div class='selected_items modal-scroll'><div class='all_items_selected'><?php _e('все','usam') ?></div></div></div>
		</div>	
		<div class='modal__buttons'>
			<button id = 'modal_action' type='button' class='button-primary button'><?php _e( 'Сохранить', 'usam') ?></button>
			<button type='button' class='button' data-dismiss='modal' aria-hidden='true'><?php _e( 'Закрыть', 'usam') ?></button>
		</div>	
	</div>
</div>