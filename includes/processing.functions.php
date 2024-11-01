<?php
function usam_get_mimetype($file, $check_reliability = false) 
{
  // Sometimes we need to know how useless the result from this is, hence the "check_reliability" parameter
	if(file_exists($file))
	{
		$mimetype_data = wp_check_filetype( $file );
		$mimetype = $mimetype_data['type'];
		$is_reliable = true;
	} 
	else 
	{
		$mimetype = false;
		$is_reliable = false;
	}
	if($check_reliability == true)
		return array('mime_type' => $mimetype, 'is_reliable' => $is_reliable );
	else 
		return $mimetype;
}


function usam_ping_services( $post_id )
{
	wp_schedule_single_event( time(), 'do_usam_pings' );
}
add_action( 'publish_usam-product', 'usam_ping_services' );

function usam_ping()
{
	$services = get_option('ping_sites');
	$services = explode("\n", $services);
	foreach ( (array) $services as $service ) 
	{
		$service = trim($service);
		if($service != '' )
			usam_send_ping( $service );		
	}
}
add_action( 'do_usam_pings', 'usam_ping' );

function usam_send_ping( $server ) 
{
	global $wp_version;
	$path = "";
	include_once(ABSPATH . WPINC . '/class-IXR.php');
	
	$client = new IXR_Client($server, ((!strlen(trim($path)) || ('/' == $path)) ? false : $path));
	$client->timeout = 3;
	$client->useragent .= ' -- WordPress/'.$wp_version;
	
	$client->debug = false;
	$home = trailingslashit( usam_get_url_system_page('products-list') );
	$rss_url = home_url('/')."?xmlformat=rss&action=feed";
	if ( !$client->query('weblogUpdates.extendedPing', get_bloginfo('blogname'), $home, $rss_url ) ) 
	{
		$client->query('weblogUpdates.ping', get_bloginfo('blogname'), $home);
	}
}

function usam_product_list_rss_feed() 
{
	$rss_url = usam_get_url_system_page('products-list', 'feed');
	$rss_url = esc_url( $rss_url ); 
	echo "<link rel='alternate' type='application/rss+xml' title='" . get_option( 'blogname' ) .' '.__('Список товаров','usam')."' href='{$rss_url}'/>";
}
add_action( 'wp_head', 'usam_product_list_rss_feed' );


function usam_validate_rule( $rule ) 
{			
	$current_time = time();	
	if ( $rule['active'] && (empty($rule['start_date']) || $rule['start_date'] == '0000-00-00 00:00:00' || strtotime($rule['start_date']) <= $current_time ) && ( empty($rule['end_date']) || $rule['end_date'] == '0000-00-00 00:00:00' || strtotime($rule['end_date']) >= $current_time) )
		return true;
	return false;
}	

/**
 * Сравнение данных
 */
class USAM_Comparison_Array
{
	private $orderby = '';
	private $order = 'ASC';
	
	public function __construct( $orderby, $order = 'ASC' ) 
	{
		$this->orderby = $orderby;
		$this->order = $order;
	}
	/**
	* сравнить данные
	*/
	public function compare( $a, $b ) 
	{	 
		$a = (array) $a;
		$b = (array) $b;

		$val_a = isset($a[$this->orderby] ) ? $a[$this->orderby] : 0;
		$val_b = isset($b[$this->orderby] ) ? $b[$this->orderby] : 0;
				
		if ( is_numeric($val_a) && is_numeric($val_b) )
			$diff = $val_a - $val_b;
		elseif ( is_string($val_a) && is_string($val_b) )	
			$diff = strcasecmp($val_a, $val_b);
		else
			$diff = 0;
		
		if ( $this->order != 'DESC' )
		   $diff = $diff * -1;
		return $diff;
	}
}

/**
 * Сравнение данных
 */
class USAM_Comparison_Object
{
	private $orderby = '';
	private $order = 'ASC';
	
	public function __construct( $orderby, $order = 'ASC' ) 
	{
		$this->orderby = $orderby;
		$this->order = $order;
	}
	/**
	* сравнить данные
	*/
	public function compare( $a, $b ) 
	{	 
		$a = (object) $a;
		$b = (object) $b;

		$key = $this->orderby;

		$val_a = isset($a->$key ) ? $a->$key : 0;
		$val_b = isset($b->$key ) ? $b->$key : 0;

		$diff = $val_b - $val_a;

		if ( $this->order != 'DESC' )
		   $diff = $diff * -1;
		return $diff;
	}
}


class USAM_Encryption
{	
	private $salt = '';
	private $cipher = 'AES-128-CBC';
	function __construct( $salt = '', $cipher = '' ) 
	{		
		$this->salt = $salt == ''?SECURE_AUTH_KEY:$salt;
		$this->cipher = $cipher == ''?"AES-128-CBC":$cipher;
	}
  
	public function data_encrypt( $text )
	{ 
	 	if ( !defined("OPENSSL_RAW_DATA"))
			return false;
		
		if ( empty($text) )
			return false;
		
		$ivlen = openssl_cipher_iv_length( $this->cipher );
		$iv = openssl_random_pseudo_bytes($ivlen);
		$ciphertext_raw = openssl_encrypt($text, $this->cipher, $this->salt, OPENSSL_RAW_DATA, $iv);
		$hmac = hash_hmac('sha256', $ciphertext_raw, $this->salt, $as_binary=true);
		return base64_encode( $iv.$hmac.$ciphertext_raw );
	}
 
	public function data_decrypt( $text )
	{ 
	 	if ( !defined("OPENSSL_RAW_DATA"))
			return false;
		
		if ( empty($text) )
			return false;
		
		$c = base64_decode($text);
		$ivlen = openssl_cipher_iv_length( $this->cipher );
		$iv = substr($c, 0, $ivlen);
		$hmac = substr($c, $ivlen, $sha2len=32);
		$ciphertext_raw = substr($c, $ivlen+$sha2len);
		return openssl_decrypt($ciphertext_raw, $this->cipher, $this->salt, OPENSSL_RAW_DATA, $iv);
	}
}
?>