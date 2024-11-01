<?php
require_once( USAM_FILE_PATH .'/admin/includes/list_table/orders_table.php' );
class USAM_List_Table_Orders_form extends USAM_Table_Orders 
{			
	protected $views = false;
	protected $status = 'all';

	public function get_columns()
	{ 
		$columns = [
			'id'       => __('Номер заказа', 'usam'),		
			'status'   => __('Статус', 'usam'),							
			'shipping' => __('Доставка', 'usam'),				
			'method'   => __('Способ оплаты', 'usam')		
		];
		if ( !usam_check_type_product_sold( 'product' ) )
			unset($columns['shipping']);
		return $columns;
	}
	
	public function extra_tablenav( $which ) 
	{
		?><div class="alignleft actions"><?php
		if ( 'top' == $which ) 
		{
			global $current_screen;					
			$customer = isset($_REQUEST['tab']) && $_REQUEST['tab'] == 'contacts'?'contact':'company';
			?>
			<div class ="table_buttons actions">				
				<a href="#" class="js-action-item button table_buttons__button" data-action="new_<?php echo $customer; ?>" data-group="orders" data-id="<?php echo $this->id; ?>"><?php _e('Новый заказ','usam'); ?></a>
			</div>
			<?php	
		}
		?></div><?php
	}
}