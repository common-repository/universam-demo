<?php
/*
	Name: Амазон
	Group: marketplace
	Icon: amazon
	Price: paid
	Closed: yes
*/

require_once( ABSPATH . 'selling-partner-api-main/vendor/autoload.php' );	
use SellingPartnerApi\Api\SellersV1Api as SellersApi;
use SellingPartnerApi\Configuration;
use SellingPartnerApi\Endpoint;


require_once( USAM_FILE_PATH . '/includes/application.class.php' );		
class USAM_Application_Amazon extends USAM_Application
{	
	private const DATETIME_FMT = 'Ymd\THis\Z';
	private const SERVICE_NAME = 'execute-api';
	private const TERMINATION_STR = 'aws4_request';
	private const SIGNING_ALGO = 'AWS4-HMAC-SHA256';
	protected $API_URL = "https://sellingpartnerapi-na.amazon.com";
	protected $expiration = 432000; //DAY_IN_SECONDS	
		
	protected function get_token_args( )
	{ 		
		$headers["Content-Type"] = 'application/x-www-form-urlencoded;charset=UTF-8';
		return [
			'method' => 'POST',
			'httpversion' => '1.1',
			'headers' => $headers,
			'body' => ['grant_type' => 'refresh_token', 'refresh_token' => $this->option['access_token'], 'client_id' => $this->option['login'], 'client_secret' => $this->option['password']],
		];		
	}
	
	protected function get_token_url( )
	{ 
		return 'https://api.amazon.com/auth/o2/token';
	}			

/*
marketplaceIds

Canada	A2EUQ1WTGCTBG2	CA
United States of America	ATVPDKIKX0DER	US
Spain	A1RKKUPIHCS9HS	ES
United Kingdom	A1F83G8C2ARO7P	UK
France	A13V1IB3VIYZZH	FR
Germany	A1PA6795UKMFR9	DE
Italy	APJ6JRA9NG5V4	IT
Japan	A1VC38T7YXB528	JP
	*/
	public function get_catalog( $params = [] )
	{ 	
		$params['marketplaceIds'] = 'ATVPDKIKX0DER';
		$results = $this->prepare_request( "catalog/2022-04-01/items", $params, 'GET' );				
		if ( isset($results['payments']) )
			return $results;
		return false;
	}	
	
	public function get_sellers( $params = [] )
	{ 	
		$results = $this->prepare_request( "sellers/v1/marketplaceParticipations", $params, 'GET' );				
		if ( isset($results['payments']) )
			return $results;
		return false;
	}
	
	protected function prepare_request( $url, $params, $method = 'POST' )
	{ 
		$token = $this->get_token();
		if ( !$token )
			return false;	
		
		if ( $method != 'GET' )
			$params = json_encode($params);
		
		$headers["accept"] = 'application/json';
		$headers['host'] = str_replace('https://','', $this->API_URL);
		$headers["content-type"] = 'application/json';
		$headers["user-agent"] = 'jlevers/selling-partner-api/5.4.6 (Language=PHP)';
		
		ksort($headers, SORT_STRING);
		
		//SignedHeaders список всех заголовков
		$signature = $this->get_signature( $url, $method, $headers, "" );	
		$headers["Authorization"] = "AWS4-HMAC-SHA256 Credential=".$this->option['credential_key_id']."/".$this->create_credential_scope().", SignedHeaders=".strtolower(implode(';', array_keys($headers))).", Signature={$signature}";
		global $token;
		$headers["x-amz-access-token"] = $token;
		$headers["x-amz-date"] = $this->date();
		
		$args = [
			'method' => $method,
			'timeout' => 45,
			'user-agent' => 'jlevers/selling-partner-api/5.4.6 (Language=PHP)',
			'redirection' => 5,
			'httpversion' => '1.1',
		//	'blocking' => true,
			'headers' => $headers,
		//	'body' => $params,
		];		
echo "<br>";		
		print_r($args);  echo "<br><br>";	
	//	return;
		return $this->send_request( $url, $params );
	}	
	
	private function create_credential_scope(): string
    {
        return $this->date('Ymd')."/".$this->option['region']."/".self::SERVICE_NAME."/".self::TERMINATION_STR;
    }
	
	private function date( $format = '' ): string
    {
		static $datetime = null;
		if ( $datetime === null )
			$datetime = time();
		
		global $tt;
		$tt = new \DateTime('now', new \DateTimeZone('UTC'));
		
		
		$format = $format ? $format : self::DATETIME_FMT;
		return date($format, $datetime);
    }
		
	protected function get_signature( string $url, string $method, array $headers, string $body )
	{		
		$args[] = $method;
		$args[] = '/'.$url;
		$args[] = '';		
		ksort($headers, SORT_STRING);
		foreach ($headers as $key => $value) 
		{
			$args[] = strtolower($key).":".preg_replace('/(\s+)/', ' ', trim($value));
		}
		$args[] = "\n".strtolower(implode(';', array_keys($headers)));
		$args[] = hash('sha256', $body);		
		$signingString = implode("\n", $args);		
		$signingString = hash('sha256', $signingString);
		$credential_scope = $this->create_credential_scope();		
		$signingString = static::SIGNING_ALGO . "\n".$this->date()."\n{$credential_scope}\n{$signingString}";
		
		$kDate = hash_hmac('sha256', $this->date('Ymd'), "AWS4{$this->option['credential_secret_id']}", true);
        $kRegion = hash_hmac('sha256', $this->option['region'], $kDate, true);
        $kService = hash_hmac('sha256', self::SERVICE_NAME, $kRegion, true);
        $kSigning = hash_hmac('sha256', self::TERMINATION_STR, $kService, true);	
        return hash_hmac('sha256', $signingString, $kSigning);
	}
	
	protected function send_request( $function, $params )
	{				
		if ( !$this->is_token() )
			return false;	
		$url = $this->get_url( $function );		
	/*	
		$headers = [];
		foreach ($args['headers'] as $key => $value) 
		{
			$headers[] = "{$key}:{$value}";
		}
		
		$ch = curl_init();
curl_setopt($ch,CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
//curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch,CURLOPT_HTTPGET,1);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, '');

$return = curl_exec($ch);

$resp = json_decode($data['body'],true); 	

print_r($url); echo "<br>";
print_r($params);  echo "<br>";
print_r($resp);



$return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

return $resp;	



*/


			
		$data = wp_remote_post( $url, $params );	
		if ( is_wp_error($data) )
		{
			$message = $data->get_error_message();
			$this->set_error( $message );	
			return false;
		} 
		$resp = json_decode($data['body'],true); 	
echo "<br>";
print_r($url); echo "<br>";
print_r($params);  echo "<br>";
print_r($resp);

		if ( $this->log_errors( $resp, $function ) ) 
			return false;
		else
			return $resp;		
		return $resp;		
	}	
	
	
	
	
	
	
		
	function display_form( ) 
	{			
		$this->date('Ymd');
		
//https://developer-docs.amazon.com/sp-api-blog/docs/troubleshooting-selling-partner-api-authorization-errors#missingauthenticationtoken

		$config = new SellingPartnerApi\Configuration([
   "version" => "beta",	
   "lwaClientId" => $this->option['login'],
    "lwaClientSecret" => $this->option['password'],
    "lwaRefreshToken" => $this->option['access_token'],
    "awsAccessKeyId" => $this->option['credential_key_id'],	
    "awsSecretAccessKey" => $this->option['credential_secret_id'],
    "endpoint" => SellingPartnerApi\Endpoint::NA,
	"roleArn" => "arn:aws:iam::217321158735:role/api_kniga",
]);


$api = new SellersApi($config);
try {
    $result = $api->getMarketplaceParticipations();
 //   print_r($result);
} catch (Exception $e) {
   

	//$this->get_sellers();
	//	$this->get_catalog();

   echo 'Exception when calling SellersApi->getMarketplaceParticipations: ', $e->getMessage(), PHP_EOL;
}



//print_r($config->searchCatalogItemsRequest(['ATVPDKIKX0DER']) );

		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_login'>Client identifier:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id="option_login" name="login" value="<?php echo $this->option['login']; ?>">
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='application_password'>Client secret:</label></div>
				<div class ="edit_form__item_option"><?php usam_get_password_input( $this->option['password'], ['name' => 'password', 'id' => 'application_password']); ?></div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='application_secret_key'><?php esc_html_e( 'Токен', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php usam_get_password_input( $this->option['access_token'], ['name' => 'access_token', 'id' => 'application_secret_key']); ?>
				</div>
			</div>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='credential_key_id'>Access key ID:</label></div>
				<div class ="edit_form__item_option">
					<?php usam_get_password_input( $this->option['credential_key_id'], ['name' => 'credential_key_id', 'id' => 'credential_key_id']); ?>
				</div>				
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='credential_secret_id'>Secret access key:</label></div>
				<div class ="edit_form__item_option">
					<?php usam_get_password_input( $this->option['credential_secret_id'], ['name' => 'credential_secret_id', 'id' => 'credential_secret_id']); ?>
				</div>				
			</div>							
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Регион', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select name='region'>
						<option value='us-east-1' <?php selected( 'us-east-1', $this->option['region'] ); ?>>North America (Canada, US, Mexico, and Brazil marketplaces)</option>
						<option value='eu-west-1' <?php selected( 'eu-west-1', $this->option['region'] ); ?>>Europe (Spain, UK, France, Belgium, Netherlands, Germany, Italy, Sweden, Poland, Saudi Arabia, Egypt, Turkey, United Arab Emirates, and India marketplaces)</option>
						<option value='us-west-2' <?php selected( 'us-west-2', $this->option['region'] ); ?>>Far East (Singapore, Australia, and Japan marketplaces)</option>
					</select>
				</div>				
			</div>			
		</div>
		<?php
	}
	
	public function save_form( ) 
	{	
	//	$this->remove_hook( 'documents' );	
		if ( $this->is_token() )
		{
			if ( $this->option['active'] )
			{
		//		$this->add_hook_ten_minutes('documents');		
			}
		}
		if ( isset($_POST['credential_key_id']) && $_POST['credential_key_id'] !== '***' )
			$metas['credential_key_id'] = sanitize_text_field($_POST['credential_key_id']);
		if ( isset($_POST['credential_secret_id']) && $_POST['credential_secret_id'] !== '***' )
			$metas['credential_secret_id'] = sanitize_text_field($_POST['credential_secret_id']);
		$metas['region'] = sanitize_text_field($_POST['region']);		
		foreach( $metas as $meta_key => $meta_value)
		{			
			usam_update_application_metadata($this->id, $meta_key, $meta_value);
		}
	}	
	
	public function service_load( ) 
	{	
		//	add_action('usam_application_documents_schedule_'.$this->option['service_code'],  [$this, 'upload_bank_payments']);	
	}
}
?>