<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/documents_table.php' );
class USAM_suggestion_Table extends USAM_Documents_Table
{	
	protected $document_type = ['suggestion'];			
	
	function column_number( $item ) 
    {		
		echo '<span class="js-object-value">'.$item->number.'</span>';
	}	
	
	function column_select( $item ) 
    {		
		echo "<a href=''>".__('добавить', 'usam')."</a>";
	}
	
	function get_columns()
	{		
        $columns = array(           
			'cb'             => '<input type="checkbox" />',	
			'name'           => __('Предложение', 'usam'),		
			'totalprice'     => __('Сумма', 'usam'),		
			'date'           => __('Дата', 'usam'),		
			'select'         => __('Выбрать', 'usam'),				
        );		
        return $columns;
    }
}
?>