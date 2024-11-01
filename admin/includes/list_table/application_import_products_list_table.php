<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_Application_Import_Products_Table extends USAM_List_Table
{		
	function column_product_title( $item ) 
    { 
		if ( $item['photo'] )
			echo "<span class='image_container product_image'><img loading='lazy' src='".$item['photo']."'></span>";	
		ob_start();
		echo $item['product_title']
		?><div class="product_sku"><?php _e( 'Артикул', 'usam'); ?>: <span class="js-copy-clipboard"><?php echo esc_html( $item['sku'] ) ?></span></div><?php
		$this->row_actions_table( ob_get_clean(), $this->standart_row_actions( ['download' => __('Скачать', 'usam')] ) );		
	}		
	
	protected function column_cb( $item ) 
	{			
		$checked = in_array($item['sku'], $this->records )?"checked='checked'":"";
		echo "<input id='checkbox-".$item['sku']."' type='checkbox' name='cb[]' value='".$item['sku']."' ".$checked."/>";
    }	
	
	function get_bulk_actions_display() 
	{	
		$actions = [
			'download'  => __('Скачать', 'usam'),		
		];
		return $actions;
	}
	
	function get_columns()
	{		
       return apply_filters( 'usam_application_import_products_columns', [] );
    }
	
	function prepare_items() 
	{
		$this->get_query_vars();			
		$data = apply_filters( 'usam_application_import_products_data', null, $this->query_vars );
		if ( $data )
		{
			$this->items = $data['items'];
			$this->total_items = $data['total_items'];				
			$this->set_pagination_args(['total_items' => $this->total_items, 'per_page' => $this->per_page]);
		}
	}
}