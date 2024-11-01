<?php
class USAM_Showcase_API
{		
	private $showcase;
	function __construct( $showcase ) 
	{
		if( is_numeric($showcase) )
		{
			require_once( USAM_FILE_PATH .'/includes/exchange/showcase.class.php' );
			$this->showcase = (object)usam_get_showcase( $showcase );
		}
		else
			$this->showcase = $showcase;
	}
	
	public function get_type_prices()
	{
		$result = $this->send_request( 'type_prices', 'POST', ['fields' => 'code=>data']);
		return $result;
	}	
	
	public function save_products( $products )
	{
		$uuids = [];
		if( $products )
		{
			$uuids = $this->send_request( 'products', 'PUT', ['items' => $products]);
			if( $uuids )
				foreach( $uuids as $product_id => $uuid )			
				{		
					if( is_string($uuid) || is_numeric($uuid) )
					{
						usam_update_product_meta( $product_id, 'code_showcase_'.$this->showcase->id, $uuid);
						usam_update_product_meta( $product_id, 'date_update_showcase_'.$this->showcase->id, date("Y-m-d H:i:s") );
					}
				}
		}
		return $uuids;
	}	
	
	public function insert_product( $product )
	{
		$uuid = $this->send_request( 'product', 'POST', $product );		
		return $uuid;
	}
	
	public function update_product( $uuid, $product )
	{
		return $this->send_request( 'product/'.$uuid, 'POST', $product );
	}
	
	public function save_product_prices( $product_id, $prices )
	{
		$uuid = usam_get_product_meta( $product_id, 'code_showcase_'.$this->showcase->id );
		if( $uuid )
			return $this->send_request( '/product/price/'.$uuid, 'POST', ['prices' => $prices] );
		else
			return false;
	}
	
	private function get_headers()
	{
		return ['Content-Type' => 'application/json', 'Authorization' => 'Basic ' . base64_encode( $this->showcase->login.':'.$this->showcase->access_token )];
	}
	
	public function send_request( $function, $method, $body = [] )
	{				
		if ( !$this->showcase->access_token )
			return false;			
		if ( !$function )
		{	
			$this->set_error( 'Функция запроса не указана' );	
			return false;
		}		
		if( $body && $method !== 'GET' )
			$body = json_encode($body);
		
		$headers = $this->get_headers();		
		add_filter( 'http_request_args', function( $params, $url )
		{
			add_filter( 'https_ssl_verify', '__return_false' );
			return $params;
		}, 10, 2 );		
		$data = wp_remote_post( 'https://'.$this->showcase->domain.'/wp-json/usam/v1/'.$function, ['headers' => $headers, 'body' => $body, 'timeout' => 5000, 'method' => $method]);					
		if ( is_wp_error($data) )
		{
			$this->set_error( $data->get_error_message(), $function );	
			return false;
		} 
		$resp = json_decode($data['body'], true); 	
		if( isset($resp['code']) ) 
		{	
			$this->set_error( $resp['message'], $function );	
			return false;
		}
		if( isset($resp['error']) ) 
		{	
			$this->set_error( $resp['error'], $function );	
			return false;
		}	
		return $resp;		
	}
	
	protected function set_error( $error, $function = '' )
	{			
		if ( $function )
			$error = sprintf( __('Приложение вызвало ошибку в запросе %s. Текст ошибки: %s'), $function, $error );
		else
			$error = sprintf( __('Приложение вызвало ошибку. Текст ошибки: %s'), $error );
		usam_log_file( __('API','usam').' '.$error );
	}
}
?>