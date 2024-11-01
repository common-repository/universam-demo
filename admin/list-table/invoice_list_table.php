<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/documents_table.php' );
class USAM_List_Table_invoice extends USAM_Documents_Table
{	
	protected $document_type = ['invoice', 'invoice_offer'];	
	protected $manager_id = null;
	
	function get_bulk_actions_display() 
	{	
		$actions = parent::get_bulk_actions_display( );		
		$actions['act'] = __('Сделать акт', 'usam');
		$actions['download'] = __('Скачать счета в pdf', 'usam');
		return $actions;
	}
	
	protected function get_row_actions( $item ) 
    { 		
		$actions = $this->standart_row_actions( $item->id, $item->type, ['act' => __('Акт', 'usam'), 'copy' => __('Копировать', 'usam')] );
		if ( $item->status == 'paid' || !current_user_can('delete_'.$item->type))
			unset($actions['delete']);	
		if ( $item->status == 'notpaid' )
			unset($actions['act']);			
		if ( !current_user_can('add_'.$item->type) )
			unset($actions['copy']);
		if ( !current_user_can('edit_'.$item->type) )
			unset($actions['edit']);
		if ( !current_user_can('add_act') )
			unset($actions['act']);
		return $actions;
	}	
	
	public function extra_tablenav( $which ) 
	{
		if ( 'top' == $which )
		{			
			if ( current_user_can('export_invoice') )
			{
				?>		
				<div class="alignleft actions">
					<div class="table_buttons actions">
						<?php 
						if ( current_user_can('export_invoice') )
							$this->print_button();
						if ( current_user_can('export_invoice') )
							$this->excel_button();	
						?>
					</div>
				</div>
				<?php	
			}
		}
	} 
		
	function get_columns()
	{		
        $columns = array(           
			'cb'             => '<input type="checkbox" />',	
			'name'           => __('Название', 'usam'),		
			'company'        => __('Ваша фирма', 'usam'),				
			'counterparty'   => __('Контрагент', 'usam'),	
			'status'         => __('Статус', 'usam'),				
			'totalprice'     => __('Сумма', 'usam'),			
			'date'           => __('Дата', 'usam'),		
        );		
        return $columns;
    }
}
?>