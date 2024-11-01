<div id="bulk_actions_companies" class="modal fade">
	<div class="modal-header">
		<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
		<div class = "header-title"><?php _e('Массовые действия','usam'); ?></div>
	</div>	
	<div class='modal-body'>
		<div class='bulk_actions columns_2'>
			<div class='columns_2__column'>
				<div class='title'><?php _e('Свойства компаний','usam') ?></div>
				<div class='edit_form js-properties modal-scroll'>					
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label for='property_manager'><?php _e('Ответственный менеджер','usam') ?>:</label></div>	
						<div class='edit_form__item_option'>
							<select id='property_manager' name='manager_id'>										
								<option value=''><?php _e( 'Выберите', 'usam') ?></option>									
								<option value='0'><?php _e( 'Нет', 'usam') ?></option>	
								<?php				
								$users = get_users( array('fields' => array('ID','display_name'), 'role__in' => array('editor','shop_manager','administrator', 'shop_crm'), 'orderby' => 'nicename') );
								foreach ( $users as $user )
								{
									?><option value='<?php echo $user->ID; ?>'><?php echo $user->display_name; ?></option><?php
								}								
								?>
							</select>	
						</div>	
					</div>						
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label for='property_status'><?php _e('Статус','usam') ?>:</label></div>	
						<div class='edit_form__item_option'>
							<select id='property_status' name='status'>										
								<option value=''><?php _e( 'Выберите', 'usam') ?></option>									
								<?php
								$statuses = usam_get_object_statuses_by_type( 'company' );
								foreach ( $statuses as $status ) 
								{
									?><option value='<?php echo $status->internalname; ?>'><?php echo $status->name; ?></option><?php
								}								
								?>
							</select>		
						</div>	
					</div>	
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label for='property_type'><?php _e('Типы','usam') ?>:</label></div>	
						<div class='edit_form__item_option'>
							<select id='property_type' name='type'>										
								<option value=''><?php _e( 'Выберите', 'usam') ?></option>									
								<?php
								$types = usam_get_companies_types();	
								foreach ( $types as $key => $name )
								{
									?><option value='<?php echo $key; ?>'><?php echo $name; ?></option>	<?php
								}								
								?>
							</select>		
						</div>	
					</div>	
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label for='property_group'><?php _e('Группы','usam') ?>:</label></div>	
						<div class='edit_form__item_option'>
							<select id='property_group' name='group'>										
								<option value=''><?php _e( 'Выберите', 'usam') ?></option>									
								<?php
								$groups = usam_get_groups( array('type' => 'company') );	
								foreach ( $groups as $group )
								{
									?><option value='<?php echo $group->id; ?>'><?php echo $group->name; ?></option><?php
								}								
								?>
							</select>		
						</div>	
					</div>	
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label for='property_open'><?php _e('Доступность','usam') ?>:</label></div>	
						<div class='edit_form__item_option'>
							<select id='property_open' name='open'>										
								<option value=''><?php _e( 'Выберите', 'usam') ?></option>								
								<option value='1'><?php _e( 'Доступен всем', 'usam') ?></option>	
								<option value='0'><?php _e( 'Не доступен', 'usam') ?></option>
							</select>		
						</div>	
					</div>	
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label for='property_parent'><?php _e('Главное подразделение','usam') ?>:</label></div>	
						<div class='edit_form__item_option'>
							<?php
							$autocomplete = new USAM_Autocomplete_Forms( );
							$autocomplete->get_form_company( null, array( 'name' => 'parent_id' ) );
							?>		
						</div>	
					</div>	
				</div>
			</div>
			<div class='columns_2__column'><div class='title'><?php _e('Выбранные компании','usam') ?></div><div class='selected_items modal-scroll'><div class='all_items_selected'><?php _e('все компании','usam') ?></div></div></div>
		</div>	
		<div class='modal__buttons'>
			<button id = 'modal_action' type='button' class='button-primary button'><?php _e( 'Сохранить', 'usam') ?></button>
			<button type='button' class='button' data-dismiss='modal' aria-hidden='true'><?php _e( 'Закрыть', 'usam') ?></button>
		</div>
	</div>
</div>