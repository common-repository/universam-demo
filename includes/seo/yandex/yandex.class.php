<?php
class USAM_Yandex
{
	protected $option = [];
	protected $client_id;
	protected $errors = array();
	protected $format = 'json';
	protected $url_api = '';
	protected $version = '';
	
	public function __construct()
	{
		$this->option = get_option( 'usam_yandex' );		
	}
	
	public function is_token( )
	{
		if ( empty($this->option['access_token']) )
			return false;
		return true;
	}
			
	public function get_token( )
	{
		if ( !empty($this->option['expires_in']) && $this->option['expires_in'] > time() )
			return $this->option['access_token'];	
		if ( empty($this->option['refresh_token']) )
		{
			$this->set_error('Токен от yandex не указан');
			return false;
		}		
		$headers["Authorization"] = "Basic ".base64_encode($this->option['client_id'].':'.$this->option['client_secret']);
		$headers["Content-Type"] = "application/x-www-form-urlencoded";		
		$data = wp_remote_post( 'https://oauth.yandex.ru/token', ['method' => 'POST', 'sslverify' => true, 'body' => ['grant_type' => 'refresh_token', 'refresh_token' => $this->option['refresh_token']], 'headers' => $headers]); 		
		if ( is_wp_error($data) )
		{
			$message = $data->get_error_message();
			$this->set_error( $message );	
			return false;
		} 
		$result = json_decode($data['body'], true);	
		if ( isset($result['access_token']) )
		{ 
			$this->option['access_token'] = $result['access_token'];
			$this->option['refresh_token'] = $result['refresh_token'];
			$this->option['expires_in'] = $result['expires_in']+time();
			update_option('usam_yandex', $this->option);
			return $result['access_token'];
		}
		return false;
	}
	
	protected function set_error( $error )
	{	
		if ( is_string($error)  )		
			$this->errors[] = sprintf( __('Запрос на yandex вызвал ошибку. Текст ошибки: %s'), $error );	
		else		
			$this->errors[] = sprintf( __('Запрос на yandex вызвал ошибку №%s. Текст ошибки: %s'), $error['error_code'], $error['error_message']);
	}
	
	public function get_errors( )
	{	
		return $this->errors;
	}
	
	public function set_log_file( )
	{	
		if ( $this->errors )
		{
			usam_log_file( $this->errors );
			$this->errors = [];
		}
	}
	
	protected function get_url( $resource )
	{
		if ( $this->format )
			return "{$this->url_api}/v{$this->version}/{$resource}.{$this->format}";
		else
			return "{$this->url_api}/v{$this->version}/{$resource}";
	}
	
	protected function get_headers( )
	{				
		$headers["Authorization"] = "OAuth ".$this->get_token();
		$headers["Content-Type"] = "application/x-yametrika+json";
		return $headers;
	}
	
	protected function dataToString($data)
    {
        $queryString = array();
        foreach ($data as $param => $value) 
		{
            if (is_string($value) || is_int($value) || is_float($value))
                $queryString[] = urlencode($param) . '=' . urlencode($value);
            elseif (is_array($value))
			{
                foreach ($value as $valueItem) 
				{
                    $queryString[] = urlencode($param) . '=' . urlencode($valueItem);
                }
            } 
			else 
			{
                $this->errors[] = "Bad type of key {$param}. Value must be string or array";
                continue;
            }
        }

        return implode('&', $queryString);
    }
	
	protected function send_request( $resource, $params = [] )
	{			
		if ( !$this->get_token() )
			return false;
		
		$headers = $this->get_headers();		
		$url_api = $this->get_url( $resource );
		$data = wp_remote_get( $url_api, ['sslverify' => true, 'timeout' => 30, 'method' => 'GET', 'user-agent' => 'UNIVERSAM', 'body' => $params, 'headers' => $headers]);					 
		if ( is_wp_error($data) )
		{
			$this->set_error( $data->get_error_message() );	
			return false;
		}
		$resp = json_decode($data['body'], true);		
		if ( isset($resp['errors'] ) ) 
		{			
			$errors = is_array($resp['errors'])?$resp['errors']:[$resp['errors']];			
			foreach ($errors as $error) 
			{
				if ( isset($error['message']) )
					$this->set_error( $error['message'] );	
			}
			return false;
		}		
		return $resp;		
	}
}		
?>