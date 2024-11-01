<?php
if (!defined('ABSPATH')) exit;

require_once( USAM_FILE_PATH . '/includes/crm/object_statuses_query.class.php');

abstract class USAM_Document_Handler
{ 
	protected $document = array();
	protected $add = 0;	
	protected $update = 0;
	function start_element_handler($is_full, $names, $depth, $name, $attrs) 
	{
		$this->is_full = $is_full;
		if (( @$names[$depth - 1] == 'КоммерческаяИнформация' || @$names[$depth - 1] == 'Контейнер' ) && $name == 'Документ') {
			$this->document = ['ЗначенияРеквизитов' => []];
		}
		elseif (@$names[$depth - 1] == 'Документ' && $name == 'Контрагенты') {
			$this->document['Контрагенты'] = array();
		}
		elseif (@$names[$depth - 1] == 'Контрагенты' && $name == 'Контрагент') {
			$this->document['Контрагенты'][] = array();
		}
		elseif (@$names[$depth - 1] == 'Документ' && $name == 'Товары')
			$this->document['Товары'] = [];
		elseif (@$names[$depth - 1] == 'Товары' && $name == 'Товар')
			$this->document['Товары'][] = ['СтавкиНалогов' => [], 'Налоги' => [], 'ЗначенияРеквизитов' => [], 'ХарактеристикиТовара'  => []];	
		elseif (@$names[$depth - 2] == 'Товар' && @$names[$depth - 1] == 'ЗначенияРеквизитов' && $name == 'ЗначениеРеквизита') {
			$i = count($this->document['Товары']) - 1;
			$this->document['Товары'][$i]['ЗначенияРеквизитов'][] = array();
		}						
		elseif (@$names[$depth - 2] == 'Товар' && @$names[$depth - 1] == 'СтавкиНалогов' && $name == 'СтавкаНалога') {
			$i = count($this->document['Товары']) - 1;			
			$this->document['Товары'][$i]['СтавкиНалогов'][] = array();
		}	
		elseif (@$names[$depth - 2] == 'Товар' && @$names[$depth - 1] == 'Налоги' && $name == 'Налог') {
			$i = count($this->document['Товары']) - 1;			
			$this->document['Товары'][$i]['Налоги'][] = array();
		}
		elseif (@$names[$depth - 2] == 'Товар' && @$names[$depth - 1] == 'ХарактеристикиТовара' && $name == 'ХарактеристикаТовара') {
			$i = count($this->document['Товары']) - 1;
			$this->document['Товары'][$i]['ХарактеристикиТовара'][] = array();
		}		
		elseif (@$names[$depth - 1] == 'ЗначенияРеквизитов' && $name == 'ЗначениеРеквизита') {
			$this->document['ЗначенияРеквизитов'][] = array();
		}
	}

	function character_data_handler($is_full, $names, $depth, $name, $data) 
	{		
		if (@$names[$depth - 2] == 'Контрагенты' && @$names[$depth - 1] == 'Контрагент') 
		{
			$i = count($this->document['Контрагенты']) - 1;
			@$this->document['Контрагенты'][$i][$name] .= $data;
		}	
		elseif (@$names[$depth - 3] == 'Товар' && (@$names[$depth - 2] == 'СтавкиНалогов' || @$names[$depth - 2] == 'Налоги' || @$names[$depth - 2] == 'ХарактеристикиТовара' || @$names[$depth - 2] == 'ЗначенияРеквизитов') ) 
		{
			$i = count($this->document['Товары']) - 1;
			$j = count($this->document['Товары'][$i][$names[$depth - 2]]) - 1;
			@$this->document['Товары'][$i][$names[$depth - 2]][$j][$name] .= $data;
		}
		elseif ( @$names[$depth - 2] == 'Товары' && @$names[$depth - 1] == 'Товар' && !in_array($name, ['ЗначенияРеквизитов', 'ХарактеристикиТовара', 'СтавкиНалогов', 'Налоги']) )
		{
			$i = count($this->document['Товары']) - 1;
			@$this->document['Товары'][$i][$name] .= $data;
		}
		elseif (@$names[$depth - 3] == 'Документ' && @$names[$depth - 2] == 'ЗначенияРеквизитов' && @$names[$depth - 1] == 'ЗначениеРеквизита') 
		{
			$i = count($this->document['ЗначенияРеквизитов']) - 1;
			@$this->document['ЗначенияРеквизитов'][$i][$name] .= $data;
		}
		elseif ( @$names[$depth - 1] == 'Документ' && !in_array($name, ['Контрагенты', 'Товары', 'ЗначенияРеквизитов']))
			@$this->document[$name] .= $data; 
	}

	function end_element_handler($is_full, $names, $depth, $name) 
	{  		
		if ( ( @$names[$depth - 1] == 'КоммерческаяИнформация' || @$names[$depth - 1] == 'Контейнер' ) && $name == 'Документ' ) 
			$this->replace_document();
	}
	
	//заменить документ
	protected function replace_document( ) 
	{
		switch( $this->document['ХозОперация'] ) 
		{					
			case 'Заказ товара':
				$this->document_order();
			break;
		}		
	}
	
	protected function get_contact( $data, $key ) 
	{
		$customer = [];
		if (  strpos($data, 'Наименование не указано') === false ) 
		{	
			$data = str_replace(["[", "]"], '',  $data);
			$data = trim($data);
			if ( strpos($data, ' ') !== false ) 
			{
				list($first_name, $last_name) = explode(' ', $data, 2);
				$customer = [$key.'firstname' => $first_name, $key.'lastname' => $last_name];				
			}
		}	
		return $customer;
	}
	
	//Обработка заказов
	protected function document_order() 
	{
		static $delivery_service = null, $type_price = null, $statuses = null;	
		
		$setting = get_option('usam_1c', ['order' => ['upload_1c' => 0]]);			
		if ( empty($setting['order']['upload_1c']) )
			return;		
		if ( $this->document['Роль'] != "Продавец" ) 
			return;
		
		if ( $statuses === null )
			$statuses = usam_get_object_statuses(['type' => 'order', 'fields' => 'code=>data', 'cache_results' => true]);	

		$oder_product_ids = [];			
		$is_deleted = false;
		$order_staus = '';
		foreach ($this->document['ЗначенияРеквизитов'] as $requisite) 
		{
			$requisite['Значение'] = trim($requisite['Значение']);
			if ( $requisite['Наименование'] == 'ПометкаУдаления' ) 	
			{
				if ( $requisite['Значение'] == 'true' ) 
				{
					$is_deleted = true;
					break;		
				}
			}
			elseif ( $requisite['Наименование'] == 'Статус заказа' ) 	
			{
				foreach ($statuses as $status) 
				{
					if ( $status->name == $requisite['Наименование'] )
					{
						$order_staus = $status->internalname;
						break;
					}
				}				
			}	
			elseif ( $requisite['Наименование'] == 'Статуса заказа ИД' ) 	
			{
				if ( isset($statuses[$requisite['Значение']]) )
					$order_staus = $statuses[$requisite['Значение']]->internalname;
			}					
		}	
		if ( $is_deleted ) 
		{			
			usam_delete_orders(['code' => $this->document['Ид']]);		
			return;
		}							
		$order = usam_get_order( $this->document['Ид'], 'code' ); 
		if ( empty($order) ) 
			$order = usam_get_order( $this->document['Номер'] ); 				
			
		$document_products = [];
		$document_services = [];
		$code = [];
		if (isset($this->document['Товары']))
		{
			foreach ($this->document['Товары'] as $i => $document_product) 
			{
				foreach ($document_product['ЗначенияРеквизитов'] as $document_product_requisite) 
				{
					if ( $document_product_requisite['Наименование'] != 'ТипНоменклатуры' ) 
						continue;
					if ($document_product_requisite['Значение'] == 'Услуга')
						$document_services[] = $document_product; // Возможна доставка
					else 
						$document_products[] = $document_product;
						$code[] = $document_product['Ид'];
					break;
				}
			}
		}		
		$products = [];			
		$product_codes = usam_get_product_ids_by_code( $code );		
		foreach ($document_products as $document_product) 
		{
			if ( isset($product_codes[$document_product['Ид']]) ) 
			{
				$product = ['product_id' => $product_codes[$document_product['Ид']]];
				$quantity = isset($document_product['Количество']) ? usam_string_to_float($document_product['Количество']) : 1;
				if ( isset($document_product['Единица']) )
				{ 
					$unit_measure = usam_get_unit_measure($document_product['Единица'], 'short');
					$product['unit_measure'] = isset($unit_measure['code']) ? $unit_measure['code'] : '';
				}
				$coefficient = isset($document_product['Коэффициент']) ? usam_string_to_float($document_product['Коэффициент']) : 1;
				$product['quantity'] = $quantity * $coefficient;	
				 
				$product['name'] = isset($document_product['Наименование']) ? stripslashes($document_product['Наименование']) : get_post_field( 'post_title', $product_codes[$document_product['Ид']] );			
				if ( isset($document_product['ЦенаЗаЕдиницу']) )
					$product['price'] = usam_string_to_float($document_product['ЦенаЗаЕдиницу']);
				elseif ( isset($document_product['Цена']) )
					$product['price'] = usam_string_to_float($document_product['Цена']);
				$products[] = $product;
			}		
		}	
		$metas = ['exchange' => 1, 'date_exchange' => date("Y-m-d H:i:s")];
		if ( !empty($this->document['Комментарий']) )
			$metas['note'] = stripcslashes($this->document['Комментарий']);
		foreach ($this->document['ЗначенияРеквизитов'] as $requisite) 
		{		
			if ( $requisite['Наименование'] == 'Номер по 1С' ) 	
				$metas['1c_document_number'] = trim($requisite['Значение']);
			elseif ( $requisite['Наименование'] == 'Дата по 1С' ) 	
				$metas['1c_document_date'] = date("Y-m-d H:i:s", strtotime(trim($requisite['Значение'])));
		}	
		if ( empty($order) ) 
		{			
			if ( $type_price === null )
			{
				$type_prices = usam_get_prices(['available' => 1, 'type' => 'R']); 
				$type_price = current($type_prices);		
			}
			if ( !empty($this->document['Контрагенты']) )
			{							
				$payers = [];
				foreach ($this->document['Контрагенты'] as $contragent) 
				{
					$customer_data = [];
					if ( !empty($contragent['ИНН']) || !empty($contragent['ОфициальноеНаименование']) ) 
					{						
						if ( !empty($contragent['ОфициальноеНаименование']) ) 
							$customer_data['company'] = trim($contragent['ОфициальноеНаименование']);
						elseif ( !empty($contragent['Наименование']) ) 
							$customer_data['company'] = trim($contragent['Наименование']);	
						if ( !empty($contragent['ИНН']) ) 
							$customer_data['inn'] = trim($contragent['ИНН']);			
						if ( !empty($contragent['КПП']) ) 
							$customer_data['ppc'] = trim($contragent['КПП']);	
						$payers = usam_get_group_payers(['type' => 'company']);
					}
					else
					{
						$key = 'billing';
						if ( !empty($contragent['Роль']) && $contragent['Роль'] != 'Покупатель') 
							$key = 'shipping';						
						if ( !empty($contragent['Наименование']) ) 
							$customer_data = $this->get_contact($contragent['Наименование'], $key);
						if ( empty($customer_data) && !empty($contragent['ПолноеНаименование']) ) 
							$customer_data = $this->get_contact($contragent['ПолноеНаименование'], $key);	
						$payers = usam_get_group_payers(['type' => 'contact']);						
					}
				}
			}		
			$properties_1с = ['firstname' => 'Имя', 'lastname' => 'Фамилия', 'E-mail' => 'Электронная почта', 'phone' => 'Телефон'];
			$args = ['status' => 'received', 'source' => '1c', 'code' => $this->document['Ид'], 'type_price' => $type_price ? $type_price['code'] : '', 'type_payer' => $payers ? $payers[0]['id'] : ''];
			if ( !empty($customer_data['inn']) )
			{
				$company_ids = usam_get_companies(['meta_key' => 'inn', 'meta_value' => $customer_data['inn'], 'fields' => 'id']);
				if ( $company_ids )
					$args['company_id'] = $company_ids[0];
			}	
			elseif ( !empty($customer_data['email']) )
			{
				$contact_ids = usam_get_contact_ids_by_field('email', $customer_data['email']);
				if ( $contact_ids )
					$args['contact_id'] = $contact_ids[0];
			}
			elseif ( !empty($customer_data['phone']) )
			{
				$contact_ids = usam_get_contact_ids_by_field(['mobile_phone', 'phone'], $customer_data['phone']);
				if ( $contact_ids )
					$args['contact_id'] = $contact_ids[0];
			}
			$date = 0;
			if ( isset($this->document['Дата']) )
			{
				$date = $this->document['Дата'];
				if ($date && !empty($this->document['Время']) && $this->document['Время'] != $this->document['Дата'] ) 
					$date .= " {$this->document['Время']}";								
			}
			if ( strtotime($date) <= 0 && !empty($this->document['Дата1С']) )
				$date = $this->document['Дата1С'];	
			$args['date_insert'] = date("Y-m-d H:i:s", strtotime($date));	
				
			if ( $order_staus )
				$args['status'] = $order_staus;		
				
			$order_id = usam_insert_order( $args, $products, $metas );		
			if ( !$order_id )
				return false;		
			
			if ( $customer_data )
				usam_add_order_customerdata( $order_id, $customer_data );
			$this->add++;			
		}
		else 
		{
			$order_id = $order['id'];
			$args = [];
			if ( $order_staus )
				$args['status'] = $order_staus;
			elseif ( $order['status'] == 'delete' )
				$args['status'] = 'received';				
			usam_update_order( $order_id, $args, $products, $metas );			
			$this->update++;
		}			
		$shipped = [];		
		$shipping_documents = usam_get_shipping_documents_order( $order_id );
		foreach ($this->document['ЗначенияРеквизитов'] as $requisite) 
		{
		//	if ($requisite['Наименование'] != 'Проведен' || $requisite['Значение'] != 'true') 
		//		continue;			
			if ( $requisite['Наименование'] == 'Отгружен' ) 	
			{				
				if ( $requisite['Значение'] == 'true' ) 
					$shipped['status'] = 'shipped';
			}
			elseif ( $requisite['Наименование'] == 'Метод доставки ИД' ) 	
				$shipped['method'] = absint($requisite['Значение']);
			elseif ( $requisite['Наименование'] == 'Оплачен' ) 	
			{
				if ( $requisite['Значение'] == 'true' ) 
					usam_change_order_status_paid( $order_id );
			}
			elseif ( $requisite['Наименование'] == 'Дата оплаты по 1С' ) 	
			{
				
			}
			elseif ( $requisite['Наименование'] == 'Дата отгрузки по 1С' ) 	
			{
				foreach ($shipping_documents as $document )	
					usam_update_shipped_document_metadata($document->id, '1c_shipping_date', date("Y-m-d H:i:s", strtotime($requisite['Значение'])) );				
			}
		}			
		if ( $delivery_service === null ) 
			$delivery_service = usam_get_delivery_services(['active' => 'all']);	

		$shipped_document_products = [];
		foreach ( $products as $product ) 
			$shipped_document_products[] = ['product_id' => $product['product_id'], 'quantity' => $product['quantity'], 'reserve' => $product['quantity']];
		if ( !empty($document_services) )
		{
			foreach ($document_services as $key => $document_service) 
			{		
				$price = usam_string_to_float( $document_service['Сумма'] );	
				$add = true;			
				foreach ($shipping_documents as $document )
				{
					if ( $document_service['Наименование'] == $document->name ) 
					{
						$add = false;			
						usam_update_shipped_document( $document->id, ['price' => $price], $shipped_document_products );
						break;
					}			
				}   
				if ( $add )
				{
					foreach ($delivery_service as $service )
					{
						if ( $document_service['Наименование'] == $service->name ) 
						{					
							usam_insert_shipped_document(['method' => $service->id, 'price' => $price, 'order_id' => $order_id], $shipped_document_products, ['document_id' => $order_id, 'document_type' => 'order'] );
							break;
						}
					}
				}
			}
		}		
		else
		{
			if ( !empty($shipping_documents) )
			{
				foreach ($shipping_documents as $document )		
					usam_update_shipped_document( $document->id, $shipped, $shipped_document_products);
			}
			else		
			{
				$shipped['order_id'] = $order_id;
				usam_insert_shipped_document( $shipped, $shipped_document_products, ['document_id' => $order_id, 'document_type' => 'order']);
			}
		}		
		do_action('usam_document_order_save', $order_id);
		wp_cache_delete( $order_id, 'usam_products_order' );
		wp_cache_delete( $order_id, 'usam_shipped_documents_order' );
		wp_cache_delete( $order_id, 'usam_payment_order' );
		wp_cache_delete( $order_id, 'usam_properties_order' );
		wp_cache_delete( $order_id, 'usam_order_meta' );	
	}	
	
	public function get_results( ) 
	{
		return ['add' => $this->add, 'update' => $this->update];
	}
}