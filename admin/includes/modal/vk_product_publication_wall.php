<?php 
$profile_id = isset($_REQUEST['profile_id'])?absint($_REQUEST['profile_id']):0;
$options = get_option('usam_vk_autopost', array() );
?>
<div id="vk_product_publication_wall" class="modal fade">
	<div class="modal-header">
		<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
		<div class = "header-title"><?php _e('Публикация товара на стену','usam'); ?></div>
	</div>
	<div class='modal-body'>
		<div class='bulk_actions columns_2'>
			<div class='columns_2__column'>
				<div class='edit_form'>
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label for='profiles'><?php _e('Куда','usam') ?>:</label></div>	
						<div class='edit_form__item_option'>
							<select id='profiles'>
								<option value='vk_group'><?php _e( 'Во все группы', 'usam') ?></option>
								<option value='vk_user'><?php _e( 'Во все анкеты', 'usam') ?></option>				
								<?php
								$profiles = usam_get_social_network_profiles( array( 'type_social' => array( 'vk_group', 'vk_user' ) ) );	
								foreach ( $profiles as $profile ) 
								{															
									?><option value='<?php echo $profile->id; ?>' <?php selected( $profile_id, $profile->id ) ?>><?php echo $profile->name; ?></option><?php
								}						
								?>
								</select>
							</select>
						</div>						
					</div>			
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label for='vk_add_market'><?php _e('Прикрепить товар','usam') ?>:</label></div>	
						<div class='edit_form__item_option'>
							<select id='vk_add_market'>
								<option value='0'><?php _e( 'Нет', 'usam') ?></option>
								<option value='1'><?php _e( 'Да', 'usam') ?></option>
							</select>
						</div>						
					</div>		
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label for='vk_add_link'><?php _e('Прикрепить ссылку','usam') ?>:</label></div>	
						<div class='edit_form__item_option'>
							<select id='vk_add_link'>
								<option value='1'><?php _e( 'Да', 'usam') ?></option>
								<option value='0'><?php _e( 'Нет', 'usam') ?></option>						
							</select>
						</div>						
					</div>			
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label for='subscriber_list'><?php _e('Дата публикации','usam') ?>:</label></div>	
						<div class='edit_form__item_option'><?php echo usam_get_display_datetime_picker( ); ?></div>	
					</div>	
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label for='subscriber_list'><?php _e('Место продажи','usam') ?>:</label></div>	
						<div class='edit_form__item_option'><?php usam_get_storage_dropdown( 0, array('id' => 'place_sale') ) ?></div>	
					</div>			
					<div class='edit_form__item'>
						<textarea rows='10' autocomplete='off' cols='40' id='message_format' ><?php echo $options['product_message']; ?></textarea>
					</div>	
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label for='vk_services'><?php _e('Экспортировать запись','usam') ?>:</label></div>	
						<div class='edit_form__item_option'>
							<select id='vk_services'>
								<option value=''><?php _e( 'Нет', 'usam') ?></option>
								<option value='twitter'>twitter</option>
								<option value='facebook'>facebook</option>
							</select>
						</div>						
					</div>	
				</div>
			</div>	
			<div class='columns_2__column'><div class='title'><strong><?php _e('Выбранные товары','usam') ?></strong></div><div class='selected_items modal-scroll'><div class='all_items_selected'><?php _e('Не выбран товар','usam') ?></div></div></div>
		</div>
		<div class='modal__buttons'>
			<button id = 'modal_action' type='button' class='button-primary button'><?php _e( 'Публиковать', 'usam') ?></button>
			<button type='button' class='button' data-dismiss='modal' aria-hidden='true'><?php _e( 'Закрыть', 'usam') ?></button>
		</div>	
	</div>
</div>