<?php		
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_products_document extends USAM_List_Table
{		
	function get_columns()
	{
        $columns = [
			'cb'          => '<input type="checkbox" />',
			'title'       => __('Товары (работы, услуги)', 'usam'),								
        ];
		$document = usam_get_document( $this->id );		
		$table_columns = get_user_option( 'usam_columns_document' );	
		if ( !empty($table_columns[$document['type']]) )
		{
			$columns_product_table = usam_get_columns_product_table();
			foreach ( $table_columns[$document['type']] as $column ) 
			{
				$columns[$column] = $columns_product_table[$column];
			}			
		}
		$columns += [			
			'oldprice'  => __('Цена', 'usam'),	
			'discount'  => __('Скидка', 'usam'),
			'price'     => __('Цена со скидкой', 'usam'),		
			'quantity'  => __('Количество', 'usam'),
			'total'     => __('Сумма', 'usam'),							
        ];	
        return $columns;	
    }		
	
	function column_title( $item )
	{		
		echo "<a href='".get_the_permalink( $item->product_id )."'>".esc_html($item->name)."</a>";	
	}
	
	function column_discount( $item )
	{		
		$document = usam_get_document( $this->id );
		$discount = $item->old_price - $item->price;
		echo usam_get_formatted_price( $discount, ['type_price' => $document['type_price']]);
	}
	
	function column_total( $item )
	{	
		$document = usam_get_document( $this->id );
		$totalprice = $item->price*$item->quantity;
		echo usam_get_formatted_price( $totalprice, ['type_price' => $document['type_price']]);
	}
	
	function column_price( $item )
	{	
		$document = usam_get_document( $this->id );
		echo usam_get_formatted_price( $item->price, ['type_price' => $document['type_price']]);
	}
	
	function column_oldprice( $item )
	{	
		$document = usam_get_document( $this->id );
		echo usam_get_formatted_price( $item->old_price, ['type_price' => $document['type_price']]);
	}
	
	function column_quantity( $item )
	{	
		echo usam_get_formatted_quantity_product_unit_measure( $item->quantity, $item->unit_measure );
	}
	
	protected function column_default( $item, $column_name ) 
	{		
		$columns_product_table = usam_get_columns_product_table();
		if ( isset($columns_product_table[$column_name]) )
			return usam_get_product_property($item->product_id, $column_name);
		else
			return $item->$column_name;
	}
	
	function prepare_items() 
	{		
		global $wpdb;	
								
		if ( !empty( $this->records ) )
			$this->where[] = "id IN (".implode( ",", $this->records) . ")";
		else
			$this->where = array("1 = '1'");	
		
		$this->where[] = "document_id = $this->id";
	
		$where = implode( ' AND ', $this->where );
		
		$this->items = $wpdb->get_results( "SELECT * FROM ". USAM_TABLE_DOCUMENT_PRODUCTS ." WHERE {$where} ORDER BY {$this->orderby} {$this->order}");
		$this->total_items = count($this->items);		
		$this->_column_headers = $this->get_column_info();			
	}
}