<?php
/**
 * Класс доставки
 */
class USAM_Shipping
{
	protected $API_URL;
	protected $version = '';
	protected $error  = '';	
	protected $is_external = false;	
	protected $load_locations = false;		
	protected $deliver = array();
	protected $id = 0;
	protected $xml_data = array();
	protected $xml_type_data = '';
	protected $expiration = 7200;
	
	public function __construct( $id )
	{ 
		$this->id = absint($id);
		$default = $this->get_default_option();
		if( !empty($this->id) )
		{
			$this->deliver = usam_get_delivery_service( $id );	
			$metas = usam_get_delivery_service_metadata( $id );		
			foreach($metas as $meta )
			{				
				if( isset($default[$meta->meta_key]) && is_array($default[$meta->meta_key]) )
					$this->deliver[$meta->meta_key][] = maybe_unserialize( $meta->meta_value );
				else
				{
					$this->deliver[$meta->meta_key] = maybe_unserialize( $meta->meta_value );
					if ( in_array($meta->meta_key, ['price', 'margin', 'weight_from', 'weight_to', 'price_from', 'price_to']) )
						$this->deliver[$meta->meta_key] = (float)$this->deliver[$meta->meta_key];
				}
			}
		}
		else
			$this->deliver = ['period_from' => 0, 'period_to' => 0, 'period_type' => '', 'price' => ''];
		$this->deliver = array_merge($default, $this->deliver);
	}	
	
	protected function get_default_option( )
	{ 
		return ['price' => 0];
	}	
	
	protected function get_token_args( )
	{ 
		return [];
	}
	
	public function set_delivery_warehouses( $paged = 0 )
	{
		return true;
	}	
	
	protected function is_token(  )
	{
		return true;
	}
	
	public function match_locations( $page ) 
	{
		return true;
	}
	
	protected function get_token( $function = '' )
	{ 					
		$access_token = get_transient( 'shipping_access_token_'.$this->id );			
		if ( !empty($access_token) )
			return $access_token;	
						
		$args = $this->get_token_args();
		if ( !empty($args) )
		{
			$result = $this->send_request( $args, $function );		
			if ( isset($result['access_token']) )
			{ 
				$expiration = !empty($results['expires_in'])?$results['expires_in']:$this->expiration;
				set_transient( 'shipping_access_token_'.$this->id, $result['access_token'], $expiration );
				return $result['access_token'];
			}
		}
		return false;
	}
	
	// город отправления груза
	protected function get_location_departure( ) 
	{
		$storage_id = !empty($this->deliver['storage_id'])?$this->deliver['storage_id']:0;
 		$storage = usam_get_storage( $storage_id );		
		if ( !empty($storage['location_id']) )
			$current_location_id = $storage['location_id'];
		else
			$current_location_id = get_option( 'usam_shop_location' );	
		return $current_location_id;
	}
	
	protected function get_handler_location( ) 
	{
		$location_id = $this->get_location_departure();
		if ( $location_id )		
		{
			$from_city_id = $this->get_location( $location_id );			
			if ( !$from_city_id )
			{
				$location = usam_get_location( $location_id );
				$this->set_error( sprintf(__('Не указан код для города %s. Вы можете его указать в местоположениях.','usam'),$location['name']) );
			}
		}
		else
		{
			$from_city_id = false;
			$this->set_error( __('Не указан город отправления в складе отгрузки.','usam') );
		}
		return $from_city_id;
	}
	
	protected function get_location( $location_id ) 
	{
		return usam_get_location_metadata( $location_id, $this->deliver['handler'] );
	}
		
	public function get_url( $function ) 
	{		
		if ( $this->version )
			$url = "{$this->API_URL}v{$this->version}/{$function}";
		else
			$url = "{$this->API_URL}{$function}";
		return $url;
	}
	
	public function handle_request_errors( $error ) 
	{		
		$this->set_error( $error );	
	}
				
	protected function send_request( $params, $function = '' )
	{				
		$url = $this->get_url( $function );
		$data = wp_remote_post( $url, $params );	
		if ( is_wp_error($data) )
		{
			$message = $data->get_error_message();
			$this->set_error( $message );	
			return false;
		} 						
		$resp = json_decode($data['body'],true);
		if ( isset($resp['error'] ) ) 
		{		
			$this->handle_request_errors( $resp['error'] );	
			return false;
		}				
		return $resp;		
	}
	
	protected function xml_start_element_handler( $parser, $name, $attrs ) { }	
	protected function xml_end_element_handler( $parser, $name ){ }
	
	protected function send_request_xml( $params, $function = '' )
	{				
		$data = wp_remote_post( $this->API_URL.$function, $params );			
		if ( is_wp_error($data) )
		{
			$message = $data->get_error_message();
			$this->set_error( $message );	
			return false;
		} 		
		$this->xml_type_data = $function;
		$this->xml_data = array();
		
		$XMLparser = xml_parser_create();
		xml_set_element_handler($XMLparser, array($this, 'xml_start_element_handler'), array($this, 'xml_end_element_handler') );		
		if (!xml_parse($XMLparser, $data['body'])) 
		{
			$this->set_error( __('Ошибка обработки данных.','usam') );
			return array();
		}
		xml_parser_free($XMLparser);	
		return $this->xml_data;
	}
	
	public function set_log_file()
	{
		usam_log_file( $this->error );
	}
	
	protected function set_error( $errors ) 
	{		
		if ( is_array($errors) )
		{
			foreach ( $errors as $error ) 
			{
				$error = isset($error['text'])?$error['text']:$error;
				$this->error = sprintf( __('Модуль доставки %s(%s). Описание ошибки: %s'), $this->deliver['handler'], $this->deliver['id'], $error );				
			}
		}
		else
			$this->error = sprintf( __('Модуль доставки %s(%s). Описание ошибки: %s'), $this->deliver['handler'], $this->deliver['id'], $errors );
		
		$this->set_log_file();
	}
	
	public function create_order( $document_id )
	{
		return false;
	}
	
	public function get_errors() 
	{
		return $this->error;
	}
	
	public function notifications() { }	
	
	public function get_shipping_cost( $args ) 
	{		
		return ['price' => (float)$this->deliver['price']];
	}	
	
	public function get_final_delivery( $args ) 
	{				
		if ( $this->deliver['delivery_option'] && $this->deliver['price'] > 0 )
			$result = ['price' => (float)$this->deliver['price']];
		else
			$result = $this->get_shipping_cost( $args );
		if ( $result === false )
			return false;
		
		if ( !is_array($result) )
			$result = ['price' => $result];
				
		if ( is_numeric($result['price']) )
		{			
			if ( !empty($this->deliver['margin']) )
			{
				if ( $this->deliver['margin_type'] == 'f' )								
					$margin = $this->deliver['margin'];				
				else															
					$margin = round($result['price']*$this->deliver['margin']/100, 2);					
				$result['price'] += $margin;		
				if ( $result['price'] < 0 )		
					$result['price'] = 0;
			}
		} 
		return $result;
	}		
	
	public function get_delivery_history( $barcode ) 
	{		
		return array();
	}	
	
	public function get_address_order( $args ) 
	{	
		$properties = usam_get_properties(['type' => 'order', 'active' => 1, 'type_payer' => $args['type_payer'], 'field_type' => 'address', 'fields' => 'code']);
		$property_address = end($properties);
		if ( $args['order_id'] )				
			$address = usam_get_order_metadata( $args['order_id'], $property_address ); 
		else		
			$address = usam_get_customer_checkout( $property_address ); 				
		$location = usam_get_location( $args['location'] );	
		return "г. {$location['name']}, ул. {$address}";		
	}	
	
	protected function insert_storage( $args ) 
	{		
		if ( !empty($args['code']) )
		{			
			$default = ['active' => 1, 'shipping' => 0, 'issuing' => 1, 'owner' => $this->deliver['handler']];
			$args = array_merge( $default, $args );		
			return usam_insert_storage( $args );	
		}
		return 0;
	}	
	
	public function get_options() 
	{
		return [
			['field_type' => 'text', 'name' => __('Стоимость доставки', 'usam'), 'code' => 'price', 'default' => 0],
		];
	}
	
	public function load_data( ) 
	{ 
		if ( $this->load_locations && $this->deliver['active'] && $this->deliver['handler'] )
		{
			$name = usam_get_name_service($this->deliver['handler'], 'shipping');
			if ( !usam_check_process_is_running( 'match_locations_'.$this->deliver['handler'] ) )
			{
				$locations = usam_get_locations(['meta_query' => ['relation' => 'OR', ['key' => $this->deliver['handler'], 'value' => '', 'compare' => '='], ['key' => $this->deliver['handler'], 'compare' => 'NOT EXISTS']], 'code' => 'city', 'number' => 1]);
				if ( $locations )
					usam_create_system_process(sprintf(__("Загрузка кодов местоположений для %s","usam"), $name), ['id' => $this->id], 'match_locations', 1000, 'match_locations_'.$this->deliver['handler']);
			}			
			if ( !usam_check_process_is_running( 'delivery_warehouses_'.$this->deliver['handler'] ) && $this->deliver['delivery_option'] )
				usam_create_system_process(sprintf(__("Загрузка точек получения товара для %s","usam"), $name), ['id' => $this->id], 'delivery_warehouses', 1000, 'delivery_warehouses_'.$this->deliver['handler']);
		}
	}
}

// Подключить класс
function usam_get_shipping_class( $id )
{	
	$delivery = usam_get_delivery_service( $id );
	$shipping_class = 'USAM_Shipping';	
	if ( !empty($delivery['handler']) )
	{
		$handler = $delivery['handler'];	
		$shipping_class = 'USAM_Shipping_'.$handler;	
		if ( !class_exists($shipping_class) )
		{
			$file =  USAM_APPLICATION_PATH . "/shipping/{$handler}.php";
			if ( file_exists( $file ) )			
				require_once( $file );			
			else
				$shipping_class = 'USAM_Shipping';
		}
	}
	$shipping_instance = new $shipping_class( $id );
	return $shipping_instance;
}
?>