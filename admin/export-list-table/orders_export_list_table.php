<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_export_list_table.class.php' );	
class USAM_Export_List_Table_orders extends USAM_Export_List_Table
{		
	public function get_columns()
	{
		return array(
			'id'       => __('Номер заказа', 'usam'),			
			'totalprice' => __('Сумма', 'usam'),			
			'status'   => __('Статус', 'usam'),
			'customer' => __('Клиент', 'usam'),
			'login'    => __('Логин', 'usam'),
			'date'     => __('Дата', 'usam'),		
			'notes'    => __('Заметки', 'usam'),
			'shipping' => __('Доставка', 'usam'),				
			'method'   => __('Способ оплаты', 'usam'),
			'date_paid' => __('Дата оплаты', 'usam'),			
			'manager'   => __('Менеджер', 'usam'),			
		);
	}
	
	public function column_login( $item )
	{			
		if ( !empty($item->user_login) )
			echo $item->user_login;
		else		
			_e('Гость','usam');
	}
	
	public function column_customer( $item )
	{
		if ( usam_is_type_payer_company( $item->type_payer ) )			
		{
			$text = usam_get_order_metadata( $item->id, 'company' );		
		}
		else
		{
			$firstname = usam_get_order_metadata( $item->id, 'billingfirstname' );
			$lastname = usam_get_order_metadata( $item->id, 'billinglastname' );
			$text = trim($firstname." ".$lastname);
		}				
		echo $text;
	}
		
	public function column_address( $item )
	{			
		if ( usam_is_type_payer_company( $item->type_payer ) )	
			echo usam_get_order_metadata( $item->id, 'company_shippingaddress' );
		else
			echo usam_get_order_metadata( $item->id, 'shippingaddress' );
	}

	public function column_date_paid( $item ) 
	{			
		if ( $item->date_paid )
			echo usam_local_date( $item->date_paid, get_option( 'date_format', 'Y/m/d' ) );
	}	

	public function column_totalprice( $item ) 
	{	
		echo usam_get_formatted_price( $item->totalprice, ['type_price' => $item->type_price]);
	}
	
	public function column_status( $item ) 
	{		
		echo usam_get_object_status_name( $item->status, 'order'  );	
	}
	
	public function column_code_status( $item ) 
	{		
		echo $item->status;	
	}
	
	public function column_notes( $item ) 
	{			
		echo usam_get_order_metadata($item->id, 'note');
	}	
	
	public function column_shipping( $item )
	{			
		$shipped_documents = usam_get_shipping_documents_order( $item->id );
		$i = 0;		
		foreach ( $shipped_documents as $document )
		{	
			$i++;
			if ( $i > 1 )
				echo '<hr size="1" width="90%">';			
			echo $document->name;			
		}		
	}
	
	public function column_method( $item )
	{	
		$payment_documents = usam_get_payment_documents_order( $item->id );	
		$i = 0;
		foreach ( $payment_documents as $document )
		{
			$i++;
			if ( $i > 1 )
				echo '<hr size="1" width="90%">';
			echo $document->name;			
		}
	}
	
	public function single_row( $item ) 
	{		
		echo '<tr>';
		$this->single_row_columns( $item );
		echo '</tr>';
	}
}