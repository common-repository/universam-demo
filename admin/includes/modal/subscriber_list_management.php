<?php 
$types = array( 'email' => __('Электронная почта', 'usam'), 'phone' => __('Телефон', 'usam') );
require_once( USAM_FILE_PATH .'/includes/feedback/mailing_lists_query.class.php' );
$lists = usam_get_mailing_lists();
?>
<div id="subscriber_list_management" class="modal fade">
	<div class="modal-header">
		<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
		<div class = "header-title"><?php _e('Списки рассылки','usam'); ?></div>
	</div>
	<div class='modal-body'>
		<div class='bulk_actions columns_2'>
			<div class='columns_2__column'>
				<div class='edit_form js-properties'>
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label for='subscriber_operation'><?php _e('Выберете операцию','usam') ?>:</label></div>	
						<div class='edit_form__item_option'>
							<select id='subscriber_operation' name='operation'>
								<option value='copy'><?php _e('Добавить', 'usam');  ?></option>
								<option value='move'><?php _e('Перенести', 'usam');  ?></option>
								<option value='remove'><?php _e('Удалить', 'usam');  ?></option>
							</select>
						</div>							
					</div>	
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label for='subscriber_list'><?php _e('Список','usam') ?>:</label></div>	
						<div class='edit_form__item_option'>
							<select id='subscriber_list' name='list' multiple>";
								<?php
									foreach ( $lists as $list )
									{
										echo "<option value='{$list->id}'>{$list->name}</option>";
									}	
								?>		
							</select>
						</div>	
					</div>						
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label for='subscriber_type'><?php _e('Тип связи','usam') ?>:</label></div>	
						<div class='edit_form__item_option'>
							<select id='subscriber_type' name='type' multiple>
								<?php
								foreach ( $types as $key => $name )
								{
									echo '<option value="'.$key.'">' . $name . "</option>";
								}			
								?>
							</select>	
						</div>	
					</div>	
				</div>
			</div>	
			<div class='columns_2__column'><div class='title'><?php _e('Выбранные','usam') ?></div><div class='selected_items modal-scroll'><div class='all_items_selected'><?php _e('все','usam') ?></div></div></div>
		</div>	
		<div class='modal__buttons'>
			<button id = 'modal_action' type='button' class='button-primary button'><?php _e( 'Сохранить', 'usam') ?></button>
			<button type='button' class='button' data-dismiss='modal' aria-hidden='true'><?php _e( 'Закрыть', 'usam') ?></button>
		</div>	
	</div>
</div>