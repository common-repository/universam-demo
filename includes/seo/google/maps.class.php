<?php
require_once( USAM_FILE_PATH . '/includes/seo/google/google.class.php' );
/**
 * Google Maps
 */ 
class USAM_Google_Maps extends USAM_Google_Cloud_Platform
{ 
	protected $url_api = 'https://maps.googleapis.com';	
	
	protected function get_url( $resource )
	{
		return "$this->url_api/{$resource}";
	}
	
	public function get_distance_matrix( $from, $to )
	{	
		$from = urlencode($from);
		$to = urlencode($to);
				
		$args = array( 'origins' => $from, 'destinations' => $to, 'language' => 'ru-RU', 'sensor' => false );	
		$results = $this->send_request( 'maps/api/distancematrix/json', $args );	
		if ( !$results )
			return false;
		if ( !empty($results['error_message']) )
		{
			$this->set_error( $results['error_message'] );
			return false;
		}
		if ( !empty($results['rows'][0]['elements'][0]['distance']['value']) )
		{				
			$result = $results['rows'][0]['elements'][0]['distance']['value'];		
			return $result;	
		}
		return false;
	}	
}
?>