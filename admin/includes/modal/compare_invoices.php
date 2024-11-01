<div id="compare_invoices" class="modal fade modal-medium">
	<div class="modal-header">
		<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
		<div class = "header-title"><?php _e('Сравнить с накладными','usam'); ?></div>
	</div>	
	<div class='modal-body modal-scroll'>
		<div class='edit_form'>
			<?php
			include( USAM_FILE_PATH . '/admin/includes/compare_invoices_importer.php' );	
			$progress_form = new USAM_Compare_Invoices_Rule_Importer( );
			$progress_form->display();	
			?>
		</div>
	</div>
</div>