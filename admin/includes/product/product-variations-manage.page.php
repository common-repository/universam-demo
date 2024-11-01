<form method='POST' action='' id="save_variation">	
	<?php
		require_once( USAM_FILE_PATH . '/admin/list-table/product_variations_list_table.php' );
		$list_table = new USAM_List_Table_product_variations( );		
		$list_table->display_table()
	?>
</form>