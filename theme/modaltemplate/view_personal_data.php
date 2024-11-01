<div id="view_personal_data" class="modal fade modal-large">
	<div class="modal-header">
		<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
		<div class = "header-title"><?php _e('Согласие на обработку персональных данных','usam'); ?></div>
	</div>
	<div class="modal-body modal-scroll"><div class='agreement_text'><?php echo wpautop( wp_kses_post( get_option( 'usam_consent_processing_personal_data' ) ) ) ?></div></div>
</div>