<?php 
$profile_id = isset($_REQUEST['profile_id'])?absint($_REQUEST['profile_id']):0;

require_once( USAM_APPLICATION_PATH . '/social-networks/vkontakte_api.class.php' );
$vkontakte = new USAM_VKontakte_API( $profile_id );		
$albums = $vkontakte->get_albums( array( 'need_system' => 1, 'need_covers' => 1 ) );	
$errors = $vkontakte->get_errors();		
foreach ( $errors as $error ) 
	$this->set_user_screen_error( $error );	
?>
<div id="vk_image_publication_album" class="modal fade">
	<div class="modal-header">
		<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
		<div class="header-title"><?php _e('Публикация фотографий в альбом','usam'); ?></div>
	</div>
	<div class='modal-body'>
		<div class='bulk_actions columns_2'>
			<input type="hidden" id="profile_id" value="<?php echo esc_attr( $profile_id ); ?>"/>
			<div class='columns_2__column modal-scroll'>
				<div class="usam_checked">
					<?php 
					foreach ( $albums['items'] as $album ) : 		
						?>
						<div class="usam_checked__item js-checked-item usam_checked-<?php echo esc_attr( $album['id'] ); ?>">
							<div class="usam_checked_enable">
								<input type="checkbox" name="codes[]" class="input-checkbox" value="<?php echo esc_attr( $album['id'] ); ?>"/>
								<label><img src="<?php echo $album['thumb_src']; ?>" class="album_image"><span class="album_title"><?php echo esc_html( $album['title'] ); ?></span></label>
							</div>										
						</div>
					<?php endforeach; ?>
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