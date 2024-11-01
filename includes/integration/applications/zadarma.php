<?php
/*
	Name: Задарма
	Description: IP телефония
	Price: paid
	Group: telephony
	Icon: zadarma
*/
require_once(USAM_FILE_PATH."/includes/feedback/telephony.class.php");
class USAM_Application_zadarma extends USAM_Telephony
{	
	protected $API_URL = "https://api.zadarma.com";	
	protected $version = "1";
		
	public function get_balance( )
	{
		$result = $this->prepare_request( "info/balance/" );
		return $result;
	}
	
	public function get_record( $call_id, $pbx_call_id )
	{				
		$result = $this->prepare_request( "pbx/record/request", ['call_id' => $call_id, 'pbx_call_id' => $pbx_call_id] );
		return $result;
	}
	
	public function set_caller_id( $id, $number )
	{
		$result = $this->prepare_request( "/sip/callerid", ['id' => $id, 'number' => $number] );
		return $result;
	}	

//запись звонка готова для стачивания.	
//call_id_with_rec – уникальный id звонка с записью разговора;
//pbx_call_id – постоянный ID внешнего звонка в АТС (не меняется при прохождении сценариев, голосового меню, transfer и т.д., отображается в статистике и уведомлениях).
	protected function rest_api_NOTIFY_RECORD( )
	{
		$call_id = absint($_POST['call_id_with_rec']);		
		$pbx_call_id = $_POST['pbx_call_id'];			 
		
		$result = $this->get_record( $call_id, $pbx_call_id );
		if ( !empty($result['links']) )
		{	
			require_once( USAM_FILE_PATH . '/includes/crm/calls_query.class.php' );		
			$calls = usam_get_calls( array('call_id' => $call_id) );			
			if ( !empty($calls) ) 
			{							
				foreach ( $result['links'] as $link ) 
				{
					$filename = basename($link);
					$file = USAM_UPLOAD_DIR.$filename;
					file_put_contents($file, file_get_contents($link));
					$file_id = usam_add_file_from_files_library( $file, array('object_id' => $calls[0]->id, 'type' => 'telephony' ), $delete );
				}
			}
		}
	}
	
	/*
	Параметры, которые отправляются на ссылку для уведомлений:
	call_start – время начала звонка;
	pbx_call_id – id звонка;
	destination – номер, на который позвонили;
	caller_id – номер, настроенный на внутреннем номере АТС или установленный в правилах набора по направлениям. Если номер не установлен, то передается 0;
	internal – (опциональный) внутренний номер.	
	*/
	protected function rest_api_NOTIFY_OUT_START( )
	{		
	//	$this->set_caller_id( '00001', '74012758741' );	
				
		$signature = base64_encode(hash_hmac('sha1', $_POST['internal'] . $_POST['destination'] . $_POST['call_start'], $this->option['password']));
		
		$phone = sanitize_title($_POST['destination']);		
		$time = strtotime($_POST['call_start']);
		$call_id = absint($_POST['call_id']);	
		
		$result = $this->outgoing_call( $phone, $call_id, $time );	
	}	
	
	public function callback( $from_phone, $to_phone, $sip = null )
	{			
		if ( empty($from_phone) || empty($to_phone) )
			return false;	
						
		$method = "/v{$this->version}/request/callback/";
		$params = array( 'from' => $from_phone, 'to' => $to_phone );	
		if ( !empty($sip) )
			$params['sip'] = $sip;
		
		ksort($params);
		$headers["Authorization"] = $this->get_authorization( $params, $method );		
		$args = array(
			'method' => 'GET',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => $headers,
			//'body' => $params,
			'cookies' => array()
		);				
		$result = $this->send_request( $method."?".http_build_query($params, null, '&', PHP_QUERY_RFC1738), $args );		
		if ( isset($result['status']) && $result['status'] == 'success' )
		{
			return $this->set_call( $to_phone, 'compound' );			
		}
		$this->set_call( $to_phone, 'failed' );
		return false;
	}
		
	public function prepare_request( $method, $params = [] )
	{						
		$params['format'] = !empty($params['format'])?$params['format']:'json';
		ksort($params);
		$headers["Authorization"] = $this->get_authorization( $params, $method );		
		$args = array(
			'method' => 'GET',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => $headers,
			'body' => $params,
			'cookies' => []
		);		
		return $this->send_request( $method, $args );
	}
	
	public function get_authorization( $params, $method )
	{ 		
		$paramsStr = http_build_query($params, null, '&', PHP_QUERY_RFC1738); 	
		$signature = base64_encode(hash_hmac('sha1', $method . $paramsStr . md5($paramsStr), $this->option['password']));
		return $this->option['login'] . ':' . $signature;
	}	
	
	public function rest_api( $request ) 
	{		
		if ( isset($_GET['zd_echo']) ) 
			exit($_GET['zd_echo']); 
		
		$params = $_REQUEST;	
		if ( !empty($params['events']) )
		{
			$action = sanitize_title($params['event']);
			$method = "rest_api_$action";
			if ( method_exists($this, $method) )			
				$this->$method( );
		}
	}
	
	public function register_routes( ) 
	{ 
		register_rest_route( $this->namespace, $this->route, array(		
			array(
				'permission_callback' => false,
				'methods'  => 'POST',
				'callback' => 'usam_application_rest_api',	
				'args' => array()
			),			
		));	
	}
	
	public function filter_phone_call( $result, $phone, $links )
	{
		if ( !$result && $phone ) 
		{	
			$from_phone = usam_get_manager_phone();
			
			$user_id = get_current_user_id();	
			$sip = get_the_author_meta('usam_sip', $user_id);	
		
			$id = $this->callback( $from_phone, $phone, $sip );
			return ['id' => $id, 'gateway' => $this->option['service_code'], 'phone' => $phone];			
		}
		return $result;
	}
	
	public function filter_cancel_phone_call( $result, $gateway, $id )
	{
		if ( $gateway == $this->option['service_code'] ) 
			return $this->save_complete_call( $id );
		return $result;
	}
	
	public function filter_download_call_data( $call )
	{
		require_once( USAM_FILE_PATH . '/includes/crm/calls_query.class.php' );
		$user_id = get_current_user_id();
		$call = usam_get_calls(['status' => ['compound', 'answered'], 'manager_id' => $user_id, 'number' => 1]);
		if ( !empty($call) )
		{ 
			$statuses = usam_get_statuses_telephony();
			$call['message'] = !empty($statuses[$call['status']])? $statuses[$call['status']]:'';
		}
		return $call;
	}
	
	public function filter_telephony_balance( $balance )
	{
		$result = $this->get_balance();
		return isset($result['balance'])?$result['balance'].' '.$result['currency']:0;
	}
		
	public function possibility_to_call( $result )
	{		
		if( $this->option['login'] && $this->option['password'] )
			return true;
		return $result;
	}
	
	public function service_load()
	{ 	
		add_filter('usam_possibility_to_call', [$this,'possibility_to_call']);	
		add_filter('usam_phone_call', [$this,'filter_phone_call'], 10, 3);			
		add_filter('usam_cancel_phone_call', [$this,'filter_cancel_phone_call'], 10, 3);
		add_filter('usam_download_call_data', [$this,'filter_download_call_data']);
		add_filter('usam_telephony_balance', [$this,'filter_telephony_balance']);
		
		//$url = get_rest_url(null,$this->namespace.'/zadarma/'.$this->id);			
		add_action('rest_api_init', [$this,'register_routes'] );		
	}
	
	function display_form( ) 
	{
		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_login'><?php esc_html_e( 'Логин', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id="option_login" name="login" value="<?php echo $this->option['login']; ?>">
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='messenger_password'><?php esc_html_e( 'Пароль', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option"><?php usam_get_password_input( $this->option['password'], ['name' => 'password', 'id' => 'messenger_password']); ?></div>
			</div>	
		</div>
		<?php
	}
}
?>