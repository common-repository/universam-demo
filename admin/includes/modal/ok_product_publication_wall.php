<?php 
$profile_id = isset($_REQUEST['profile_id'])?absint($_REQUEST['profile_id']):0;
$options = get_option('usam_ok_autopost', array() );
?>
<div id="ok_product_publication_wall" class="modal fade">
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
								<option value='ok_group'><?php _e( 'Во все группы', 'usam') ?></option>
								<option value='ok_user'><?php _e( 'Во все анкеты', 'usam') ?></option>				
								<?php
								$profiles = usam_get_social_network_profiles( array( 'type_social' => array( 'ok_group', 'ok_user' ) ) );	
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
						<div class='edit_form__item_name'><label for='add_link'><?php _e('Прикрепить ссылку','usam') ?>:</label></div>	
						<div class='edit_form__item_option'>
							<select id='add_link'>
								<option value='1'><?php _e( 'Да', 'usam') ?></option>
								<option value='0'><?php _e( 'Нет', 'usam') ?></option>						
							</select>
						</div>						
					</div>	
					<div class='edit_form__item'>
						<textarea rows='10' autocomplete='off' cols='40' id='message_format' ><?php echo $options['product_message']; ?></textarea>
					</div>	
				</div>
			</div>	
			<div class='columns_2__column'><div class='title'><?php _e('Выбранные товары','usam') ?></div><div class='selected_items modal-scroll'><div class='all_items_selected'><?php _e('Не выбран товар','usam') ?></div></div></div>
		</div>	
		<div class='modal__buttons'>
			<button id = 'modal_action' type='button' class='button-primary button'><?php _e( 'Публиковать', 'usam') ?></button>
			<button type='button' class='button' data-dismiss='modal' aria-hidden='true'><?php _e( 'Закрыть', 'usam') ?></button>
		</div>	
	</div>
</div>