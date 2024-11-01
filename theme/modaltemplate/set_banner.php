<div id="set_banner" class="modal fade">
	<div class="modal-header">
		<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
		<div class = "header-title"><?php _e('Изменить банер','usam'); ?></div>
	</div>
	<div class="modal-body">
		<?php 
		$id = !empty($_REQUEST['id'])?sanitize_title($_REQUEST['id']):''; 
		$banner = usam_get_banner( $id );
		?>
		<div class="modal-body">
			<textarea class="js-html-banner" style="height:200px; width: 100%; font-size: 16px; padding: 4px 10px;" data-id="<?php echo $id; ?>"><?php echo htmlspecialchars($banner['settings']['html']); ?></textarea>	
		</div>
		<div class="modal__buttons">
			<button type="button" class="button main-button js-save-banner" data-dismiss="modal"><?php _e( 'Сохранить', 'usam'); ?></button>
		</div>	
	</div>
</div>