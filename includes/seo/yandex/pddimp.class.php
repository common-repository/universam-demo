<?php
require_once( USAM_FILE_PATH . '/includes/seo/yandex/yandex.class.php' );
class USAM_Yandex_pddimp extends USAM_Yandex
{
	private $user_id;
	protected $version = '';
	protected $type_user = 'admin';
	protected $url_api = 'https://pddimp.yandex.ru';
	
	protected function get_url( $resource )
	{
		return "{$this->url_api}/api2/$this->type_user/{$resource}";
	}
		
	protected function get_headers( )
	{ 
		$yandex = get_option('usam_yandex');
		$password = !empty($yandex['pdd']['password'])?$yandex['pdd']['password']:'';
		$headers["PddToken"] = $password;
		return $headers;
	}
	
	public function add_mailbox( $args )
	{			
		$domain = explode('@',$args['email']);
		$args['login'] = $domain[0];
		$args['domain'] = $domain[1];		
		$result = $this->send_request( 'email/add', $args );
		if ( $result['success'] == 'ok' )
			return true;
		return false;
	}	
	
	public function edit_mailbox( $args )
	{			
		$domain = explode('@',$args['email']);
		$args['login'] = $domain[0];
		$args['domain'] = $domain[1];	
		$result = $this->send_request( 'email/edit', $args );
		if ( $result['success'] == 'ok' )
			return true;
		return false;
	}
	
	public function add_mailing_list( $args )
	{			//domain=domain.com&maillist=newmaillist
		$result = $this->send_request( 'email/ml/add', $args );
		if ( $result['success'] == 'ok' )
			return true;
		return false;
	}
	
	public function unsubscribe( $args )
	{		/*	domain=<имя домена>
&(maillist=<email-адрес или логин рассылки>|maillist_uid=<идентификатор рассылки>)
&(subscriber=<email-адрес подписчика>|subscriber_uid=<идентификатор подписчика>)*/

		$result = $this->send_request( 'email/ml/unsubscribe', $args );
		if ( $result['success'] == 'ok' )
			return true;
		return false;
	}	
	
	public function subscribe( $args )
	{		/*	domain=<имя домена>
&(maillist=<email-адрес или логин рассылки>|maillist_uid=<идентификатор рассылки>)
&(subscriber=<email-адрес подписчика>|subscriber_uid=<идентификатор подписчика>)
[&can_send_on_behalf=<статус подписчика>]*/

		$result = $this->send_request( 'email/ml/subscribe', $args );
		if ( $result['success'] == 'ok' )
			return true;
		return false;
	}	
	
	//Получить список подписчиков
	public function get_subscribers( $args )
	{		/*	domain=<имя домена>
&(maillist=<email-адрес или логин рассылки>|maillist_uid=<идентификатор рассылки>)*/

		$result = $this->send_request( 'email/ml/subscribers', $args );
		if ( $result['success'] == 'ok' )
			return true;
		return false;
	}
	
	
	function send_request( $resource = '', $params = array() )
	{		
		$headers = $this->get_headers();		
		$url_api = $this->get_url( $resource );
		$data = wp_remote_post( $url_api, array('sslverify' => true, 'headers' => $headers, 'body' => $params ));	
		if ( is_wp_error($data) )
		{
			$this->set_error( $data->get_error_message() );	
			return false;
		}		
		$resp = json_decode($data['body'], true); 		
		if ( isset($resp['error_code'] ) ) 
		{			
			$this->set_error( $resp );	
			return false;
		}		
		return $resp;		
	}
}
?>