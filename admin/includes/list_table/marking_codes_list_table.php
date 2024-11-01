<?php
require_once(USAM_FILE_PATH.'/includes/product/marking_code.class.php');
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );	
require_once( USAM_FILE_PATH . '/admin/includes/manage_columns_products.php' );		
class USAM_Table_marking_codes extends USAM_List_Table 
{		
	protected $storages = array( );
	function __construct( $args = array() )
	{	
		parent::__construct( $args );		
		$storages = usam_get_storages( array( 'active' => 'all' ) );		
		foreach ( $storages as $storage )		
			$this->storages[$storage->id] = $storage;
    }
	
	function get_sortable_columns()
	{
		$sortable = array(
			'product_id' => array('product_id', false),
			'code'      => array('code', false),
			);
		return $sortable;
	}
	
	function column_code( $item )
	{	
		$this->row_actions_table( $item->code, $this->standart_row_actions( $item->id, 'marking_code' ) );
	}
	
	function column_status( $item )
	{	
		echo usam_get_marking_code_status_name( $item->status );
	}
	
	function column_store( $item )
	{	
		if ( !empty($this->storages[$item->storage_id]) )
			echo $this->storages[$item->storage_id]->title;		
	}
	
	function column_product_title( $item )
	{
		if ( $item->product_id )
			USAM_Manage_Columns_Products::column_display( 'product_title', $item->product_id );
	}
}