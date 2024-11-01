<div id="add_folder_window" class="modal fade modal-medium">
	<div class="modal-header">
		<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
		<div class = "header-title"><?php _e('Добавить папку','usam'); ?></div>
	</div>	
	<div class='modal-body'>
		<div class='edit_form'>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='folder_name'><?php esc_html_e( 'Название папки', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option"><input type="text" id="folder_name" value =""></div>
			</div>
		</div>					
		<div class="modal__buttons">
			<button id = "save_action" type="button" class="button-primary button"><?php _e( 'Добавить', 'usam'); ?></button>				
			<button type="button" class="button" data-dismiss="modal" aria-hidden="true"><?php _e( 'Отменить', 'usam'); ?></button>
		</div>
	</div>
</div>