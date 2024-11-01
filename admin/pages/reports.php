<?php
/*
 * Отображение страницы покупатели
 */ 
class USAM_Tab extends USAM_Page_Tab
{		
	protected  $per_page = 0;

	protected function print_file_table()
	{	
		$this->list_table->prepare_items();
		$columns_data = $this->list_table->items;	
		list( $columns, $hidden ) = $this->list_table->get_column_info();
		$displayed_columns = array_diff_key($columns, $hidden);
			
		$title_tab = $this->get_title_tab( );	
				
		include( USAM_FILE_PATH . '/admin/includes/print/print-report.php' );	
		exit;
		$this->redirect = false;
	}		
} 
?>