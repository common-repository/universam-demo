<?php
require_once( USAM_FILE_PATH . '/includes/seo/yandex/yandex.class.php' );
class USAM_Yandex_Direct extends USAM_Yandex
{	
	protected $version = '5';
	protected $url_api = 'https://api.direct.yandex.com/json';	
	
	public function get_campaigns( $args = array() )
	{		
		$args['SelectionCriteria'] = isset($args['SelectionCriteria'])?$args['SelectionCriteria']:array();
		$args['FieldNames'] = isset($args['FieldNames'])?$args['FieldNames']:array('Id', 'Name');		
		$result = $this->send_request( 'campaigns', array('method' => 'get', 'params' => $args) );
		print_r($result);
		return $result;
	}	
	
	protected function get_url( $resource )
	{ 	
		return "{$this->url_api}/v{$this->version}/{$resource}";
	}	
	
	protected function get_headers( )
	{
		$headers["Authorization"] = "Bearer {$this->token}";		
		$headers["Accept-Language"] = 'ru';		
		$headers["Content-Type"] = 'application/json; charset=utf-8';		
		return $headers;
	}
		
	protected function send_request( $resource = '', $params = array() )
	{		
		$headers = $this->get_headers();		
		
		$headers = array();
		$headers[] = "Authorization: Bearer {$this->token}";		
		$headers[] = 'Accept-Language: ru';		
		$headers[] = 'Content-Type: application/json; charset=utf-8';
		$url_api = $this->get_url( $resource );
		$body = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);			
				
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url_api);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $body);		
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		//curl_setopt($curl, CURLOPT_CAINFO, getcwd().'\CA.pem');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, true);
		curl_setopt($curl, CURLINFO_HEADER_OUT, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($curl);

		
		print_r($result);
		
		exit;
		
		
		
		
		$data = wp_remote_get( $url_api, array('sslverify' => true, 'method' => 'POST', 'headers' => $headers, 'body' => json_encode($params) ));			
		if ( is_wp_error($data) )
		{
			$this->set_error( $data->get_error_message() );	
			return false;
		}				
		$resp = json_decode($data['body'], true); 
		print_r($resp);
		if ( isset($resp['error_code'] ) ) 
		{			
			$this->set_error( $resp );	
			return false;
		}		
		return $resp;		
	}
}		
?>