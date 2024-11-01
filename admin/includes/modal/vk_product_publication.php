<div id="vk_product_publication" class="modal fade">
	<div class="modal-header">
		<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
		<div class = "header-title"><?php _e('Публикация товара','usam'); ?></div>
	</div>
	<div class='modal-body'>
	<?php 
	$profile_id = isset($_REQUEST['profile_id'])?absint($_REQUEST['profile_id']):0;
	$options = get_option('usam_vk_autopost', array() );
	$profile = usam_get_social_network_profile( $profile_id );
	if ( !empty($profile['type_social']) && $profile['type_social'] == 'vk_group' )
	{
		require_once( USAM_APPLICATION_PATH . '/social-networks/vkontakte_api.class.php' );
		$vkontakte = new USAM_VKontakte_API( $profile_id );			
		$categories = $vkontakte->get_market_categories(['count' => 100, 'offset' => 0]);
		$market_albums = $vkontakte->get_market_albums( );			
		?>	
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
						</div>						
					</div>			
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label for='category'><?php _e('Категории товаров','usam') ?>:</label></div>	
						<div class='edit_form__item_option'>
							<select id='category' class ='chzn-select'>
								<?php	
								$section = '';
								$out = '';
								if (!empty( $categories['items']) )
								{
									foreach ( $categories['items'] as $category )
									{
										if ( empty($category['name']) )
											continue;
										
										if ( $section !== $category['section']['id'] )
										{
											if ( $section !== '' )
												$out .= '</optgroup>';
											$out .= '<optgroup label="' . $category['section']['name'] . '">';
										}
										$out .= "<option value='".$category['id']."'>".$category['name']."</option>";					
										$section = $category['section']['id'];
									}
								}
								echo $out;
								?>
							</optgroup></select>
						</div>						
					</div>		
					<div class='edit_form__item'>
						<div class='edit_form__item_name'><label for='market_album'><?php _e('Подборки','usam') ?>:</label></div>	
						<div class='edit_form__item_option'>
							<select id='market_album' class ='chzn-select'>
								<option value=''><?php _e( 'Выберите', 'usam') ?></option>				
									<?php	
									if (!empty( $market_albums['items']) )
									{
										foreach ( $market_albums['items'] as $album )
										{					
											?><option value='<?php echo $album['id'] ?>'><?php echo $album['title'] ?></option><?php	
										}
									}
									?>
							</select>
						</div>						
					</div>
				</div>
			</div>	
			<div class='columns_2__column'><div class='title'><?php _e('Выбранные товары','usam') ?></div><div class='selected_items modal-scroll'><div class='all_items_selected'><?php _e('Не выбран товар','usam') ?></div></div></div>
		</div>	
		<div class='modal__buttons'>
			<button id = 'modal_action' type='button' class='button-primary button'><?php _e( 'Сохранить', 'usam') ?></button>
			<button type='button' class='button' data-dismiss='modal' aria-hidden='true'><?php _e( 'Закрыть', 'usam') ?></button>
		</div>	
		<?php
	}
	else
	{
		?><strong><?php _e('Можно публиковать только в группы','usam') ?></strong><?php
	}
	?>
	</div>
</div>