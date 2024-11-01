<?php
include( USAM_FILE_PATH . '/admin/includes/product/product_discount_importer.php' );		
?>
<div id="import_product_discount_rule" class="modal fade modal-medium">
	<div class="modal-header">
		<span class="close" data-dismiss="modal" aria-hidden="true">×</span>
		<div class = "header-title"><?php _e('Импорт товаров','usam'); ?></div>
	</div>	
	<div class='modal-body modal-scroll'>		
		<?php	
			$progress_form = new USAM_Product_Discount_Importer( );
			$progress_form->display();
		?>
	</div>
</div>