<?php
// Копировать заказ
function usam_order_copy( $order_id, $args = array(), $recalculate = false ) 
{
	$anonymous_function = function($a) { return false; };	
	add_filter( 'usam_prevent_notification_change_status', $anonymous_function);		
	
	$data = usam_get_order( $order_id );	
	$order_products = usam_get_products_order( $order_id );	
	$shipped_documents = usam_get_shipping_documents_order( $order_id );			
	$customer_data = usam_get_order_metadata( $order_id );
	
	$data = array_merge( $data, $args );		
	$data['status'] = '';	
	$data['paid'] = 0;	
	$data['number'] = '';	
	$data['code'] = '';	
	unset($data['date_insert']);	
	unset($data['date_paid']);	
	unset($data['manager_id']);		
	$order_new = new USAM_Order( $data );
	$result = $order_new->save();	
	if ( !$result )
		return false;
	
	$new_order_id = $order_new->get('id');	

	$new_products = array();
	foreach ( $order_products as $product ) 
	{				
		$new_products[] = (array)$product;
	}
	$order_new->add_products( $new_products );		
	foreach ( $shipped_documents as $document ) 
	{
		$products_shipped = usam_get_products_shipped_document( $document->id );
		$new_document = (array)$document;
		$new_document['order_id']  = $new_order_id;
		$new_document['status']  = 'pending';
		$shipped_document_products = array();
		foreach ( $products_shipped as $product ) 
		{
			$shipped_document_products[] = array( 'product_id' => $product->product_id, 'quantity' => $product->quantity, 'reserve' => $product->quantity );
		}	
		usam_insert_shipped_document( $new_document, $shipped_document_products, ['document_id' => $new_order_id, 'document_type' => 'order']);
	}				
	$new_customer_data = [];
	foreach ( $customer_data as $data ) 
	{
		if ( $data->meta_key == 'cancellation_reason' )
			continue;
		if ( $data->meta_value )
			usam_add_order_metadata( $new_order_id, $data->meta_key, $data->meta_value );
	}			
	$status = isset($args['status'])?$args['status']:'received';
	$order_new->set(['status' => $status]);
	$order_new->save();	
	
	if ( $recalculate )
	{
		$cart = new USAM_CART();
		$cart->set_order( $new_order_id );	
	}
	do_action('usam_document_order_save', $new_order_id);
	return $new_order_id;
}

// Пересчитать доставку заказа
function usam_calculate_amount_shipped_document( $shipped_document_id ) 
{
	$price = 0;
	$products = usam_get_products_shipped_document( $shipped_document_id );	
	$shipped_document = usam_get_shipped_document( $shipped_document_id );		
	$order_data = usam_get_order( $shipped_document['order_id'] );	
	if ( !empty($order_data) )
	{		
		$properties = ['type_price' => $order_data['type_price']];	
		$location = usam_get_order_metadata( $shipped_document['order_id'] , 'shippinglocation' );	
		$parameters['any_balance'] = true;	
		$properties['coupon_name'] = usam_get_order_metadata( $shipped_document['order_id'], 'coupon_name');
		$properties['bonus'] = usam_get_used_bonuses_order( $shipped_document['order_id'] );	
		
		$cart = new USAM_CART( $properties );	
		foreach ( $products as $product ) 
		{
			$parameters['quantity'] = $product->quantity;	
			$parameters['unit_measure'] = $product->unit_measure;	
			$cart->add_product_basket( $product->product_id, $parameters );			
		}
		$cart->set_properties(['location' => $location, 'order_id' => $shipped_document['order_id']]);
		$cart->set_shipping_methods();

		$properties = ['selected_shipping' => $shipped_document['method'], 'storage_pickup' => $shipped_document['storage_pickup']];
		$cart->set_properties( $properties );
		
		$price = $document['price'] = $cart->get_property( 'shipping' );
		usam_update_shipped_document( $shipped_document_id, $document );
	}
	return $price;
}

//Пометить заказ как оплаченный
function usam_change_order_status_paid( $order_id )
{
	if ( is_numeric($order_id) )
		$order = usam_get_order( $order_id );
	else
		$order = $order_id;
	if ( $order['paid'] != 2 )
	{
		$paid = false;
		$payment = ['sum' => $order['totalprice'], 'status' => 3, 'document_id' => $order_id];
		$documents = usam_get_payment_documents_order( $order_id );
		if ( !empty($documents) )
		{	
			foreach( $documents as $document )
			{
				if ( $document->status == 1 )
				{
					usam_update_payment_document( $document->id, $payment );
					$paid = true;
					break;
				}
			}
		}
		if ( !$paid )
		{
			$payment['manager_id'] = get_current_user_id();
			usam_insert_payment_document( $payment, ['document_id' => $order['id'], 'document_type' => 'order']);
		}
	}
}

function usam_get_plaintext_table( $headings, $rows ) 
{ 
	$colwidths = array();
	$output = array();
	$alignment = array_values( $headings );
	
	foreach ( $headings as $key => $heading ) 	
		$colwidths[$key] = strlen( $heading['name'] );
	
	foreach ( $rows as $row ) 
	{
		foreach ( $row as $key => $col ) 
		{
			if ( isset($colwidths[$key]) )
				$colwidths[$key] = max( strlen( $col ), $colwidths[$key] );
		}
	}		
	foreach ( $rows as $row )
	{
		foreach ( $headings as $key => $heading ) 	
		{
			$align = ( $heading['alignment'] == 'left' ) ? STR_PAD_RIGHT : STR_PAD_LEFT;
			$row[$key] = str_pad( $row[$key], $colwidths[$key], ' ', $align );	
		}
		$output[] = implode( '  ', $row );
	}	
	$line = array();
	$h = array();
	$i = 0;	
	foreach ( $colwidths as $key => $width ) 
	{
		$line[] = str_repeat( '-', $width );
		$h[$i] = str_pad( $headings[$key]['name'], $width );
		$i ++;
	}
	$line = implode( '--', $line );
	array_unshift( $output, $line );
	if ( !empty( $h ) ) 
	{
		array_unshift( $output, implode( '  ', $h ) );
		array_unshift( $output, $line );
	}	
	$output[] = $line;	
	return implode( "\r\n", $output ) . "\r\n";
}

//Получить ссылку на заказ
function usam_get_url_order( $id ) 
{
	$details_documents = usam_get_details_documents( );		
	return add_query_arg(['form' => 'view', 'form_name' => 'order', 'id' => $id], $details_documents['order']['url'] );
}

//Получить ссылку на заказ
function usam_get_link_order( $id ) 
{
	return '<a class="order-link" href="'.esc_url( usam_get_url_order( $id ) ).'" title="'.esc_attr__('Посмотреть детали заказа', 'usam').'">'.$id.'</a>';
}

function usam_get_order_source( ) 
{
	$source = ['call' => __('Звонок','usam'), 'order' => __('Обычный заказ','usam'), 'webform' => __('Веб-форма','usam'), 'manager' => __('Создан менеджером','usam'), 'import' => __('Импортированный','usam'), 'vk' => __('вКонтакте','usam'), 'moysklad' => __('Мой склад','usam'), '1c' => '1C', 'offline' => __('Офлайн','usam'), 'repeat' => __('Скопирован клиентом','usam')];	
	return $source;
}

function usam_get_order_source_name( $type ) 
{
	$source = usam_get_order_source();
	if ( isset($source[$type]) )
		$return = $source[$type];
	else
		$return = __('Тип неизвестен');
	return $return;
}

function usam_set_invoice_to_pdf( $id ) 
{		
	$file_path = USAM_UPLOAD_DIR.'order_invoices/invoice-'.$id.'.pdf';	
	if ( file_exists( $file_path ) )	
		unlink( $file_path );
	$html = usam_get_printing_forms( 'payment_invoice', $id );	
	
	$file = usam_export_to_pdf( $html );
		
	file_put_contents($file_path, $file ); 
	return $file_path;
}

function usam_get_order_view_group( $id )
{
	$option = get_site_option('usam_order_view_grouping');
	$view_grouping = maybe_unserialize( $option );	
	$result = array();
	if ( !empty($view_grouping) ) 
	{
		foreach ( $view_grouping as $value )		
		{
			if ( $value['id'] == $id )
			{
				$result = $value;
				break;
			}
		}
	}
	return $result;
}


function usam_get_user_order_view_group( $user_id = null )
{
	if ( $user_id == null )
		$user_id = get_current_user_id();

	$view_grouping = get_user_meta($user_id, 'usam_order_view_grouping', true);	
	$view_group = usam_get_order_view_group( $view_grouping );	
	return $view_group;
}

function usam_created_new_order( $t ) 
{
	$id = $t->get('id');
	$day = get_option( 'usam_number_days_delay_payment', 3 );
	$pay_up = date( "Y-m-d H:i:s", mktime(date('H'), date('i'), 0, date('m'), date('d') + $day, date('Y')));		
	usam_update_order_metadata($id, 'date_pay_up', $pay_up);	
}
add_action( 'usam_order_insert', 'usam_created_new_order' );