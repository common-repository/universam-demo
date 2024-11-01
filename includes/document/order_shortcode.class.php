<?php
class USAM_Order_Shortcode
{		
	protected $purchase_log;
	protected $shipping_id;	
	protected $args;	
	
	public function __construct( $order_id, $shipping_id = null ) 
	{	
		if ( is_numeric($order_id) )	
			$this->purchase_log = new USAM_Order( $order_id );
		elseif ( is_object($order_id) )
			$this->purchase_log = $order_id;
		else
			return false;
	
		$order = $this->purchase_log->get_data();		
		if ( empty($order) )		
			return false;			
		
		$this->shipping_id     = $shipping_id;			
		$this->args = $this->common_args();
	}
	
	public function get_order() 
	{
		if ( !empty($this->purchase_log) )
			return $this->purchase_log->get_data();
		else
			return array();
	}
	
	private function common_args() 
	{		
		if ( !is_object($this->purchase_log) )
			return array();
			
		$data = $this->purchase_log->get_calculated_data();
		$order = $this->purchase_log->get_data();		
		if ( $this->shipping_id !== null )
			$shipped_document = usam_get_shipped_document( $this->shipping_id );
		else
		{		
			$shipped_documents = usam_get_shipping_documents_order( $order['id'] );
			if ( empty($shipped_documents) )				
				$shipped_document = array();
			else
				$shipped_document = (array)array_pop($shipped_documents);
		}		
		if ( !empty($shipped_document['method']) )	
		{
			$delivery = usam_get_delivery_service( $shipped_document['method'] );
		}		
		$payment_documents = usam_get_payment_documents_order( $order['id'] );	
		if ( empty($payment_documents) )				
			$payment_document = array();
		else
			$payment_document = (array)array_pop($payment_documents);
					
		$payment = $this->purchase_log->get_payment_status_sum();		
		
		$formatted_price = array(
			'currency_symbol' => false,
			'decimal_point'   => true,
			'currency_code'   => false,
			'type_price'      => $order['type_price'],
		);
		$formatted_price_currency = array(
			'currency_symbol' => true,
			'decimal_point'   => true,
			'currency_code'   => false,
			'type_price'      => $order['type_price'],
		);
		$order_unpaid = $order['totalprice']-$order['paid'];
		
		$total_tax = usam_get_tax_amount_order( $order['id'] );	
		$display_total_tax = $total_tax == '0.00'?__('без НДС', 'usam'): usam_currency_display( $total_tax, $formatted_price_currency );
		$readiness_date = usam_get_shipped_document_metadata( $this->shipping_id, 'readiness_date' );
		
		$date_format = get_option('date_format', "d.m.Y");
		$args = array(			
			'order_dateformat' => usam_local_date( $order['date_insert'], get_option( 'date_format', 'd.m.Y' ) ),
			'order_date'       => usam_local_date( $order['date_insert'], 'd.m.Y' ),
			'order_time'       => usam_local_date( $order['date_insert'], 'H:i' ),
			'current_date'     => date_i18n( $date_format ),			
			'order_id'         => $order['id'],		
			'document_number'     => $order['number'],	
			'order_basket'         => usam_currency_display( $data['order_basket'], $formatted_price ),			
			'order_basket_currency' => usam_get_formatted_price( $data['order_basket'], $formatted_price_currency ),
			'order_final_basket'         => usam_currency_display( $data['order_final_basket'], $formatted_price ),			
			'order_final_basket_currency' => usam_get_formatted_price( $data['order_final_basket'], $formatted_price_currency ),
			'order_final_basket_string' => usam_get_number_word( $data['order_final_basket'] ),
			'order_basket_discount'         => usam_currency_display( $data['order_basket_discount'], $formatted_price ),			
			'order_basket_discount_currency' => usam_get_formatted_price( $data['order_basket_discount'], $formatted_price_currency ),
			'status_name'      => $order['status'],
			'status_title'      => usam_get_object_status_name( $order['status'], 'order' ),
			'status_description' => usam_get_object_status_description( $order['status'], 'order' ),
			'payment_status'   => usam_get_order_payment_status_name( $order['paid'] ),		
			'order_paid'       => $order['paid'],	
			'order_unpaid'     => $order_unpaid,	
			'order_unpaid_currency' => usam_get_formatted_price( $order_unpaid ),	
			'number_products'  => $order['number_products'],	
			'total_tax'        => usam_currency_display( $total_tax,$formatted_price ),
			'total_tax_currency' => $display_total_tax,
			'total_tax_title'   => sprintf( __('Налог: %s', 'usam'), $total_tax )."\r\n",
			'total_shipping'   => usam_get_formatted_price($order['shipping'], $formatted_price ),
			'total_shipping_currency' => usam_get_formatted_price( $order['shipping'], $formatted_price_currency ),
			'total_price'      => $order['totalprice'],
			'total_price_currency' => usam_get_formatted_price( $order['totalprice'], $formatted_price_currency ),
			'total_price_word' => mb_ucfirst( usam_get_number_word($order['totalprice']) ),
			'shop_name'        => get_option( 'blogname' ),						
			'coupon_code'      => (string)usam_get_order_metadata( $order['id'], 'coupon_name'),
			'total_discount'   => $data['order_basket_discount'],
			'total_discount_currency'   => usam_get_formatted_price( $data['order_basket_discount'], $formatted_price_currency ),	
			'order_bonus'      => usam_get_used_bonuses_order( $order['id'] ),				
			'shipping_method'  => isset($shipped_document['method']) ? $shipped_document['method'] : '' ,
			'shipping_method_name' => isset($shipped_document['name']) ? $shipped_document['name'] : '' ,
			'delivery_option_code' => isset($delivery['delivery_option']) ? $delivery['delivery_option'] : '' ,			
			'shipping_readiness_date' => $readiness_date ? usam_local_date($readiness_date, $date_format): '' ,
			'shipping_readiness_time' => $readiness_date ? usam_local_date($readiness_date, 'H:i'): '' ,		
			'track_id'         => isset($shipped_document['track_id']) ? $shipped_document['track_id'] : '',
			'gateway_name' 	   => isset($payment_document['name']) ? $payment_document['name'] : '' ,
			'gateway' 		   => isset($payment_document['gateway']) ? $payment_document['gateway'] : '' ,	
			'total_paid'       => usam_currency_display( isset($payment['total_paid']) ? $payment['total_paid'] : 0, $formatted_price ),		
			'total_paid_currency' => usam_get_formatted_price( isset($payment['total_paid']) ? $payment['total_paid'] : 0, $formatted_price_currency ),	
			'code_type_payer'   => usam_is_type_payer_company( $order['type_payer'] ) ? 'company' : 'contact',			
		);	
		if ( !empty($shipped_document['storage_pickup']) )
		{
			$storage = usam_get_storage( $shipped_document['storage_pickup'] );	
			$args['storage_phone'] = htmlspecialchars(usam_get_storage_metadata( $storage['id'], 'phone'));
			$args['storage_schedule'] = htmlspecialchars(usam_get_storage_metadata( $storage['id'], 'schedule'));
			$args['storage_address'] = htmlspecialchars(usam_get_storage_metadata( $storage['id'], 'address'));
			$args['storage_delivery_period'] = usam_get_storage_delivery_period( $storage['id'] );
			
			$location = usam_get_location( $storage['location_id'] );
			$args['storage_city'] = isset($location['name'])?htmlspecialchars($location['name']):'';
		}
		else
		{
			$args['storage_phone'] = '';
			$args['storage_schedule'] = '';
			$args['storage_address'] = '';
			$args['storage_city'] = '';
		}
		$time = time();
		foreach ( usam_get_printed_forms_document('order') as $printed_form )	
		{	
			$args[$printed_form['id'].'_pdf'] = "<a href='".usam_url_action('printed_form_to_pdf', ['form' => $printed_form['id'], 'id' => $order['id'], 'time' => $time] )."' target='_blank' class='button'>".mb_strtolower($printed_form['title'])."</a>";
		}			
		$requisites = usam_get_company_by_acc_number( $order['bank_account_id'], 'recipient' );
		if ( $requisites )		
		{			
			$requisites['shop_name'] = get_option( 'blogname' );	
			$requisites['shop_logo'] = '<img class="shop_logo" src="'.usam_get_company_logo( $requisites['recipient_id'] ).'" alt ="logo" width="100">';
			$requisites['shop_mail'] = usam_get_shop_mail();
			$requisites['shop_phone'] = usam_get_shop_phone();			
		
			$args += $requisites;
			$sum = $args['total_price']*100;
			if ( $sum > 0 )
			{
				$name = esc_html($args['recipient_company_name']);
				$qr_str = "ST00012|Name={$name}|PersonalAcc={$args['recipient_bank_number']}|BankName={$args['recipient_bank_name']}|BIC={$args['recipient_bank_bic']}|CorrespAcc={$args['recipient_bank_ca']}|PayeeINN={$args['recipient_inn']}|KPP={$args['recipient_ppc']}|Sum={$sum}|Purpose=Оплата заказа {$order['id']}";	
				$args['qr'] = usam_get_qr( $qr_str );	
			}
			else 
				$args['qr'] = '';
		}		
		$args['payment_url'] = "<a href='".usam_get_url_system_page('pay_order').'?id='.$order['id']."'>".__('Оплатить','usam')."</a>";
		$properties = usam_get_properties(['type' => 'order', 'type_payer' => $order['type_payer'], 'active' => 1, 'orderby' => ['group', 'sort']]);
		$address_location = '';			
		$address = '';
		foreach ($properties as $property )	
		{		
			$single = $property->field_type == 'checkbox'?false:true;
			$meta_value = usam_get_order_metadata( $order['id'], $property->code, $single );
			$value = usam_get_formatted_property( $meta_value, $property );
			if ( $property->field_type == 'address' && empty($address) )
				$address = $value;			
			elseif ( $property->field_type == 'location' && !empty($meta_value) )
				$address_location = $value = usam_get_full_locations_name($meta_value, '%country%, %region%, г. %city%');
			elseif ( $property->field_type == 'phone' && empty($args['customer_phone']) )
				$args['customer_phone'] = $value;
			elseif ( $property->field_type == 'email' && empty($args['customer_email']) )
				$args['customer_email'] = $value;	
			$args['customer_'.$property->code] = $value;
		} 
		$args['customer_address'] = $address_location.' '.$address; 	
		$this->args = apply_filters( 'usam_order_notification_common_args', $args, $this );	
		return $this->args;
	}

	public function get_common_args() 
	{				
		return $this->args;
	}

	private function get_payment_table_args() 
	{		
		$rows   = array();
		$headings = array(
			'document_number' => array( 'name' => __('Номер документа', 'usam'), 'alignment' => 'left'),		
			'name' => array( 'name' => __('Способ оплаты', 'usam'), 'alignment' => 'right'),
			'status_name' => array( 'name' => __('Статус', 'usam'), 'alignment' => 'right'),
			'sum' => array( 'name' => __('Сумма' ,  'usam'), 'alignment' =>  'right'),		
		);				
		$order_id = $this->purchase_log->get( 'id' );
		$payment_documents = usam_get_payment_documents_order( $order_id );	
		foreach( $payment_documents as $document ) 
		{
			$rows[] = ['document_number' => $document->number, 'date' => usam_local_date( $document->date_insert ), 'name' => $document->name, 'status_name' => usam_get_object_status_name( $document->status, 'payment' ), 'sum' => $document->sum];
		}			
		$table_args = array( 'headings' => $headings, 'rows' => $rows );
		return apply_filters( 'usam_order_notification_payment_table_args', $table_args, $this );
	}
	
	private function get_table_args() 
	{
		$order_id = $this->purchase_log->get( 'id' );
		$type_price = $this->purchase_log->get( 'type_price' );
		$rows   = array();
		
		$headings = array(
			'image' => ['name' => '', 'alignment' => 'left', 'style' => 'width:100px;'],
			'name' => array( 'name' => __('Наименование', 'usam'), 'alignment' => 'left', 'style' => ''),
			'price' => array( 'name' => __('Цена', 'usam'), 'alignment' => 'right', 'style' => 'white-space:pre'),
			'quantity' => array( 'name' => __('Количество', 'usam'), 'alignment' => 'right', 'style' => ''),
			'totalprice' => array( 'name' => __('Сумма' ,  'usam'), 'alignment' =>  'right', 'style' => 'white-space:pre'),		
		);
		$products = usam_get_products_order( $order_id );	
		foreach( $products as $item ) 
		{
			$item_total = $item->quantity * $item->price;
			$item_total = usam_get_formatted_price( $item_total, ['type_price' => $type_price] );
			$item_price = usam_get_formatted_price( $item->price, ['type_price' => $type_price]);
			$item_name = apply_filters( 'the_title', $item->name, $item->product_id );
			$rows[] = ['image' => "<img src='".usam_get_product_thumbnail_src($item->product_id, 'small-product-thumbnail')."' width='100' height='100' alt='$item->name'>", 'name' => $item->name, 'price' => $item_price, 'quantity' => $item->quantity, 'product_id' => $item->product_id, 'id' => $item->id, 'totalprice' => $item_total];
		}				
		$table_args = ['headings' => $headings, 'rows' => $rows];
		return apply_filters( 'usam_order_notification_product_table_args', $table_args, $this );
	}
	
	private function create_plaintext_payment_list() 
	{
		$table_args = $this->get_payment_table_args();		
		$output = usam_get_plaintext_table( $table_args['headings'], $table_args['rows'] );	
		return $output;
	}

	private function create_plaintext_product_list() 
	{
		$table_args = $this->get_table_args();		
		$output = usam_get_plaintext_table( $table_args['headings'], $table_args['rows'] );
		return $output;
	}
		
	private function create_plaintext_downloadable_links() 
	{
		$output = '';				
		$order_id = $this->purchase_log->get('id');		
		if ( $order_id )
		{			
			$files = usam_get_files(['order_id' => $order_id]);
			foreach ( $files as $file ) 
				$output .= $file->name ."\r\n". '  '.home_url('file/'.$file->code)."\r\n";
		}
		return $output;
	}
	
	private function create_html_downloadable_links() 
	{
		$output = '';
		$order_id = $this->purchase_log->get('id');		
		if ( $order_id )
		{			
			$files = usam_get_files(['order_id' => $order_id]);
			$output .= '<table border="0" width="600" cellspacing="0" cellpadding="0"><tbody>';	
			foreach ( $files as $file ) 
				$output .= '<tr><td  width="560"><a href="'.home_url('file/'.$file->code).'" style="text-decoration:none;">'.$file->name.'</a></td><td width="40"><a href="/file/'.$file->code.'" style="text-decoration:none;">'.__('Скачать', 'usam').'</a></td></tr>';	
			$output .= '</tbody></table>';
		}
		return $output;
	}
	
	private function create_html_payment_list() 
	{
		$table_args = $this->get_payment_table_args();		
		$headings = $table_args['headings'];
		$rows = $table_args['rows'];
		ob_start();	
		include usam_get_template_file_path( 'table-payment', 'template-parts' );		
		$output = ob_get_clean();
		return $output;
	}

//Строит таблицу купленных товаров. Отображается на странице Результаты покупки и в письме клиенту
	private function create_html_product_list() 
	{		
		$table_args = $this->get_table_args();
		$headings = $table_args['headings'];
		$rows = $table_args['rows'];
		ob_start();	
		include usam_get_template_file_path( 'table-order', 'template-parts' );		
		return ob_get_clean();
	}	

	public function get_plaintext_args() 
	{		
		$plaintext_args = array(
			'product_list'      => $this->create_plaintext_product_list(),
			'download_links'    => $this->create_plaintext_downloadable_links(),
			'payment_list'      => $this->create_plaintext_payment_list(),			
		);
		$plaintext_args = apply_filters( 'usam_order_notification_plaintext_args', $plaintext_args, $this );
		return array_merge( $this->args, $plaintext_args );
	}

	public function get_html_args() 
	{	
		$html_args = array(
			'product_list'         => $this->create_html_product_list(),
			'download_links'       => $this->create_html_downloadable_links(),
			'payment_list'         => $this->create_html_payment_list(),				
		);				
		$html_args = apply_filters( 'usam_order_notification_html_args', $html_args, $this );
		return array_merge( $this->args, $html_args );
	}	
	
	public function get_plaintext( $text ) 
	{	
		$shortcode = new USAM_Shortcode();		
		return $shortcode->process_args( $this->get_plaintext_args(), $text );
	}
	
	// из аргументов собрать строку
	public function get_html( $html ) 
	{		
	/*	
		$shortcodes = array( 'product_list' => 'product_list' );
		foreach ( $shortcodes as $shortcode ) 
		{ 
			
				preg_match_all( '@\[([^<>&/\[\]\x00-\x20=]++)@', $html, $matches );
				$tagnames = array_intersect( array_keys( $shortcodes ), $matches[1] );
				$pattern = get_shortcode_regex( $tagnames );
				$attr = shortcode_parse_atts( $pattern );
			
		}			
		exit; */
		$shortcode = new USAM_Shortcode();		
		$html = $shortcode->process_args( $this->get_html_args(), $html );
	
		$html = wpautop( $html );			
		return $html;
	}	
}

function usam_get_order_shortcode() 
{
	$tag = '%';
	$labels = array(
		'%qr%'                      => esc_html__('QR код для оплаты', 'usam'),	
		'%order_id%'                => esc_html__('Номер заказа', 'usam'),
		'%order_date%'              => esc_html__('Дата заказа', 'usam'),
		'%order_time%'              => esc_html__('Время заказа', 'usam'),		
		'%status_name%'             => esc_html__('Код статуса заказа', 'usam'),
		'%status_title%'            => esc_html__('Имя статуса заказа', 'usam'),
		'%status_description%'      => esc_html__('Описание статуса заказа', 'usam'),
		'%payment_status%'          => esc_html__('Имя статуса оплаты', 'usam'),		
		'%product_list%'            => esc_html__('Купленные товары', 'usam'),
		'%download_links%'          => esc_html__('Файлы для загрузки', 'usam'),
		'%current_date%'            => esc_html__('Текущая дата', 'usam'),	
		'%order_basket%'            => esc_html__('Сумма корзины заказа', 'usam'),
		'%order_basket_currency%'   => esc_html__('Сумма корзины заказа в валюте', 'usam'),	
		'%order_final_basket%'    => esc_html__('Сумма корзины заказа с учетом скидок', 'usam'),
		'%order_final_basket_currency%' => esc_html__('Сумма корзины заказа с учетом скидок в валюте', 'usam'),			
		'%order_basket_discount%'    => esc_html__('Скидка на товары', 'usam'),
		'%order_basket_discount_currency%' => esc_html__('Скидка на товары в валюте', 'usam'),				
		'%total_tax%'               => esc_html__('Налог заказа', 'usam'),
		'%total_tax_currency%'      => esc_html__('Налог заказа в валюте', 'usam'),
		'%total_shipping%'          => esc_html__('Доставка', 'usam'),		
		'%total_shipping_currency%' => esc_html__('Доставка в валюте', 'usam'),
		'%total_price%'             => esc_html__('Итог заказа', 'usam'),
		'%total_price_currency%'    => esc_html__('Итог заказа в валюте', 'usam'),
		'%shop_name%'               => esc_html__('Название магазина', 'usam'),
		'%discount%'                => esc_html__('Скидка (с названием)', 'usam'),
		'%coupon_code%'             => esc_html__('Код купона', 'usam'),	
		'%order_unpaid%'            => esc_html__('Сумма к оплате', 'usam'),		
		'%order_unpaid_currency%'   => esc_html__('Сумма к оплате в валюте', 'usam'),		
		'%shipping_method%'         => esc_html__('Способ доставки', 'usam'),
		'%shipping_method_name%'    => esc_html__('Названия метода доставки', 'usam'),
		'%delivery_option_code%'    => esc_html__('Вариант доставки Курьер или Самовывоз', 'usam'),
		'%track_id%'                => esc_html__('Трек-номер', 'usam'),		
		'%storage_city%'            => esc_html__('Город склада самовывоза', 'usam'),
		'%storage_address%'         => esc_html__('Адрес склада самовывоза', 'usam'),
		'%storage_phone%'           => esc_html__('Телефон склада самовывоза', 'usam'),			
		'%shipping_readiness_date%' => esc_html__('Предполагаемая дата сборки', 'usam'),	
		'%shipping_readiness_time%' => esc_html__('Предполагаемое время сборки', 'usam'),			
		'%storage_schedule%'        => esc_html__('График работы склада самовывоза', 'usam'),
		'%gateway_name%' 	        => esc_html__('Название способа оплаты', 'usam'),
		'%gateway%' 		        => esc_html__('Способ оплаты', 'usam'),
		'%total_discount%'          => esc_html__('Общая скидка', 'usam'),	
		'%order_bonus%'             => esc_html__('Потраченные бонусы', 'usam'),	
		'%date_paid%'               => esc_html__('Дата оплаты заказа', 'usam'),	
		'%order_paid%'              => esc_html__('Код статуса оплаты', 'usam'),	
		'%total_paid%'              => esc_html__('Всего оплачено', 'usam'),	
		'%total_paid_currency%'     => esc_html__('Всего оплачено в валюте', 'usam'),	
		'%payment_url%'             => esc_html__('Ссылка на оплату', 'usam'),	
		'%shop_logo%'               => esc_html__('Логотип магазина', 'usam'),		
		'%customer_email%'          => esc_html__('Электронная почта покупателя', 'usam'),
		'%customer_phone%'          => esc_html__('Телефон покупателя', 'usam'),			
	);	
	$list_properties = usam_get_properties(['type' => 'order','fields' => 'code=>data', 'active' => 'all']);	
	foreach ($list_properties as $code => $data )	
	{		
		$labels[$tag.'customer_'.$code.$tag] = $data->name;
	}
	foreach (usam_get_printed_forms_document('order') as $printed_form )	
	{	
		$labels['%'.$printed_form['id'].'_pdf%'] = $printed_form['title'];
	}	
	return $labels;
}	