<?php
class USAM_Yandex_Postoffice
{
	private $option = array();
	private $site_id;
	private $url_api = "https://postoffice.yandex.ru/api/1.0/";
	private $email;
	private $errors = array();	
	
	public function __construct( $email = '' )
	{
		$this->option = get_option( 'usam_yandex', ['access_token' => ''] );	
		$this->site_id = empty($this->option['postoffice'])?'':$this->option['postoffice'];
		$this->email = $email;
	}
	
	public function check_token( )
	{			
		if ( !empty($this->option['access_token']) )
			return true;
		else
			return false;
	}
	
	private function set_error( $error )
	{			
		if ( is_array($error) )
			$m = $error['error'];
		else
			$m = $error;
		$this->errors[] = sprintf( __('Запрос на yandex вызвал ошибку. Текст ошибки: %s'), $m);
	}
	
	public function get_errors( )
	{	
		$errors = $this->errors;
		$this->errors = array();
		return $errors;
	}
	
	public function get_reg_list( )
	{			
		$result = $this->send_request( 'reg-list' );
		$list = array();		
		if ( !empty($result['list']) )
			$list = $result['list'];
		return $list;
	}	
	
	//Получение статистики
	public function get_statistics( $group, $value )
	{						
		$result = $this->send_request( 'stat-list', array( 'group' => $group ) );	
		$lists = array();		
		if ( !empty($result['list']) )
		{			
			foreach ( $result['list'] as $list ) 
			{
				if ( $list[$group] == $value )
				{
					$lists = $list;					
					break;
				}
			}
		}
		return $lists;
	}	
	
	//Получить подробную статистику по заданной группе сообщений. 
	/*
	domain	Почтовый домен — источник рассылок.
	email   Адрес электронной почты — источник рассылок.
	id	    Числовой идентификатор рассылки. В качестве идентификатора используется значение поля id в ответе метода stat-list.
	listid	Идентификатор рассылки — значение заголовка List-id.
	year	Год рассылки. Значение — четырехзначное число.
	month	Месяц рассылки. Допустимые значения: 1-12.
	day	    День рассылки. Допустимые значения: 1-31.
	*/
	public function get_statistics_group( $value, $group )
	{				
		$result = $this->send_request( 'stat-view', array( $group => $value ) );	
		return $result;
	}	
	
	//Получить количество и список email-адресов пользователей, которые отметили письма из рассылки как спам
	/*
	email	Адрес электронной почты — источник рассылок.
	id		Числовой идентификатор рассылки. В качестве идентификатора используется значение поля id в ответе метода stat-list.	Если параметр id указан, в выборке участвует только рассылка с указанным идентификатором.
	listid	Идентификатор рассылки — значение заголовка List-id.
	year	Год рассылки. Значение — четырехзначное число.	Если параметр year указан, в выборке участвует только рассылки указанного года.
	month	Месяц рассылки. Допустимые значения: 1-12. Если параметр month указан, в выборке участвует только рассылки указанного месяца.
	day		День рассылки. Допустимые значения: 1-31. Если параметр day указан, в выборке участвует только рассылки указанного дня.
	get	    Количество получателей. Допустимое значение: count.	Если параметр get указан, выводится количество email-адресов пользователей, которые отметили письма из рассылки как спам.
	*/
	public function get_mailing_as_spam( $args = array() )
	{				
		$result = $this->send_request( 'stat-bad', $args );			
		if ( !empty($result['rcpts']) )
			return $result['rcpts'];		
		
		return array();
	}		
			
	function send_request( $resource = '', $params = array() )
	{		
		$url = $this->url_api. $resource;
		
		if ( !$this->check_token() )
			return false;
		
		$headers["Accept"] = 'application/json';
		$headers["Content-type"] = 'application/json';
		
		$params['oauth_token'] =  $this->option['access_token'];
		$params['domain'] =  $this->site_id;	
		$params['email'] =  $this->email;
		$data = wp_remote_get( $url, array('sslverify' => true, 'headers' => $headers, 'body' => $params ));	
		
		if ( is_wp_error($data) )
		{
			$this->set_error( $data->get_error_message() );	
			return false;
		}
		$resp = json_decode($data['body'], true);	
		if ( isset($resp['error'] ) ) 
		{			
			$this->set_error( $resp );	
			return false;
		}		
		return $resp;		
	}
}
?>