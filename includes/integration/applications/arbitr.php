<?php
/*
	Name: Федеральные арбитражные суды РФ
	Description: Возможность проверки контрагента
	Price: free
	Group: counterparty-justice
	Close: 1
*/
//https://github.com/newpointer/autokad/blob/master/docs/autokad.md
require_once( USAM_FILE_PATH . '/includes/application.class.php' );		
class USAM_Application_arbitr extends USAM_Application
{	
	protected $API_URL = "http://kad.arbitr.ru";
	protected $expiration = 432000; //DAY_IN_SECONDS
	public function check_company( $id )
	{ 		
		$string = usam_get_company_metadata( $id, 'company_name' );		
		if ( $string )
		{					
			$data = get_transient( 'arbitr_check_company_'.$string );
			if ( empty($data) )
			{
				$data = $this->send_request( "Kad/SearchInstances", ['Sides' => ['Name' => $string, "Type" => '-1', "ExactMatch" => false], "Page" => 1, "Count" => 25, "Courts" => [], "Judges" => [], "CaseNumbers" => [], "WithVKSInstances" => false, "DateFrom" => "2000-12-31T00:00:00", "DateTo" => date('c')], "POST" );
				set_transient( 'arbitr_check_company_'.$string, $data, $this->expiration );								
			}			
			return $data;
		}
		return false;
	}		
	
	protected function send_request( $function, $params, $method = "GET" )
	{		
		$url = $this->get_url( $function );			
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_POSTFIELDS => json_encode( $params ),
			CURLOPT_HTTPHEADER => [
				'Accept: */*',
				'Accept-Encoding: gzip, deflate',
				'Content-Type: application/json',
				'Cookie:__utma=14300007.1560979077.1419681550.1419681550.1419681550.1; __utmz=14300007.1419681550.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); ASP.NET_SessionId=o33wghgnfxi041kx3nsruwki; userId=2bcd95e5-4511-4ecb-8b12-519cbce24a86:s0hOMqJTdq/CE7opFdCBvw==; __utmt=1; __utma=228081543.1213039978.1419665530.1421072267.1421072267.10; __utmb=228081543.2.10.1421072267; __utmc=228081543; __utmz=228081543.1421072267.9.5.utmcsr=localhost:8180|utmccn=(referral)|utmcmd=referral|utmcct=/autokad/src/nkb-app/; aUserId=832fe384-d14c-4bd9-83fb-61aa7182954c:NGEVLG4bQfvBFguC16bdVQ==',
				'x-date-format: iso',
				'X-Requested-With: XMLHttpRequest'
			],
		]
		);				
		$data = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);			
		$resp = json_decode($data, true);	
		if ( isset($resp['error']) ) 
		{		
			if ( isset($resp['error'][0]) )
				$this->set_error( $resp['error'][0] );	
			else
				$this->set_error( $resp['error'] );	
			return false;
		}				
		return $resp['response'];	
	}
	
	public function filter_check_company_justice( $results, $id )
	{
		if ( !$results && $id ) 
			return $this->check_company( $id );
		return $results;
	}
	
	public function service_load()
	{ 	
		add_filter('usam_check_company_justice', [$this,'filter_check_company_justice'], 10, 2);
	}
	
	function display_form( ) 
	{
		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='messenger_secret_key'><?php esc_html_e( 'Токен', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php usam_get_password_input( $this->option['access_token'], array('name' => 'access_token', 'id' => 'messenger_secret_key') ); ?>
				</div>
			</div>	
		</div>
		<?php
	}
}
?>