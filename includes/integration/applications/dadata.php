<?php
/*
	Name: DaData.ru
	Description: Определение города, пола и координат адреса клиента, справочник компаний.
	Group: directories
	Price: free
	Icon: dadata
*/
require_once( USAM_FILE_PATH . '/includes/application.class.php' );		
class USAM_Application_dadata extends USAM_Application
{	
	protected $API_URL = "https://suggestions.dadata.ru/suggestions/api/4_1";
	
	public function find_company_by_inn( $inn, $kpp = 0 )
	{ 		
		if ( $kpp == 0 )
			$params = ['query' => $inn, 'branch_type' => 'MAIN'];
		else
			$params = ['query' => $inn, 'kpp' => $kpp];		
							
		$args = $this->get_args( $params );
		$result = $this->send_request( "rs/findById/party", $args );	
		$company = [];
		if ( !empty($result['suggestions'][0]) )
		{ 
			$result = $result['suggestions'][0]; 		
			if ( !empty($result['data']['state']['registration_date']) )
			{
				$registration_date = substr($result['data']['state']['registration_date'],0,-3);				
				$registration_date = date("d-m-Y", $registration_date );
			}
			else
				$registration_date = '';						
			$location = usam_get_locations(['meta_value' => $result['data']['address']['data']['city_kladr_id'], 'meta_key' => 'KLADR', 'number' => 1, 'code' => 'city']);	
			if ( empty($location) )
				$location = usam_get_locations(['search' => $result['data']['address']['data']['city'], 'number' => 1, 'code' => 'city']);	
				
			$keys = ['street_with_type', 'house_type_full', 'house', 'flat_type_full', 'flat'];
			$legaladdress = [];
			foreach( $keys as $key )
			{
				if ( !empty($result['data']['address']['data'][$key]) )
					$legaladdress[] = $result['data']['address']['data'][$key];
			}	
			$company = [
				'company_name' => $result['value'], 
				'full_company_name' => $result['data']['name']['full_with_opf'], 
				'gm' => $result['data']['management']['name'], 
				'inn' => $result['data']['inn'], 
				'oktmo' => $result['data']['address']['data']['oktmo'], 
				'okpo' => $result['data']['okpo'], 
				'okved' => $result['data']['okved'], 
				'ogrn' => $result['data']['ogrn'], 
				'ppc' => $result['data']['kpp'], 
				'legaladdress' => implode(', ',$legaladdress), 
				'legallocation' => isset($location->id)?$location->id:0, 
				'_name_legallocation' => isset($location->name)?$location->name:0, 
				'legalpostcode' => $result['data']['address']['data']['postal_code'], 
				'email' => $result['data']['emails'], 
				'phone' => $result['data']['phones'], 
				'date_registration'  => $registration_date, 
				'latitude'  => $result['data']['address']['data']['geo_lat'], 
				'longitude'  => $result['data']['address']['data']['geo_lon']
			];	
		}
		return $company;
	}	
	
	public function find_company( $params )
	{ 		
		$args = $this->get_args( $params );
		$results = $this->send_request( "rs/suggest/party", $args );		
		$company = [];		
		if ( !empty($results['suggestions']) )
		{ 		
			foreach( $results['suggestions'] as $result )
			{		
				if ( !empty($result['data']['state']['registration_date']) )
				{
					$registration_date = substr($result['data']['state']['registration_date'],0,-3);				
					$registration_date = date("d-m-Y", $registration_date );
				}
				else
					$registration_date = '';
							
				$location = usam_get_locations(['meta_value' => $result['data']['address']['data']['city_kladr_id'], 'meta_key' => 'KLADR', 'number' => 1, 'code' => 'city']);	
				if ( empty($location) )
					$location = usam_get_locations(['search' => $result['data']['address']['data']['city'], 'number' => 1, 'code' => 'city']);	
				
				$company[] = [
					'company_name' => $result['value'], 
					'full_company_name' => !empty($result['data']['name'])?$result['data']['name']['full_with_opf']:'',
					'gm' => !empty($result['data']['management'])?$result['data']['management']['name']:'',
					'inn' => !empty($result['data']['inn'])?$result['data']['inn']:'',
					'oktmo' => !empty($result['data']['address']['data'])?$result['data']['address']['data']['oktmo']:'',
					'okpo' => !empty($result['data']['okpo'])?$result['data']['okpo']:'',
					'okved' => !empty($result['data']['okved'])?$result['data']['okved']:'',
					'ogrn' => !empty($result['data']['ogrn'])?$result['data']['ogrn']:'',
					'ppc' => !empty($result['data']['kpp'])?$result['data']['kpp']:'',
					'legaladdress' => $result['data']['address']['data']['street_with_type'].', '.$result['data']['address']['data']['house_type_full'].' '.$result['data']['address']['data']['house'].', '.$result['data']['address']['data']['flat_type_full'].' '.$result['data']['address']['data']['flat'], 
					'legallocation' => !empty($location->id)?$location->id:0, 
					'_name_legallocation' => !empty($location->name)?$location->name:'', 
					'legalpostcode' => $result['data']['address']['data']['postal_code'], 
					'email' => !empty($result['data']['emails'])?$result['data']['emails']:'',
					'phone' => !empty($result['data']['phones'])?$result['data']['phones']:'',
					'date_registration' => $registration_date,
					'latitude' => $result['data']['address']['data']['geo_lat'], 
					'longitude' => $result['data']['address']['data']['geo_lon']
				];	
			}
		}
		return $company;
	}		
	
	public function find_okved( $okved )
	{ 
		$args = $this->get_args(['query' => $okved]);
		$result = $this->send_request( "rs/suggest/okved2", $args );
		return $result;
	}
	
	public function find_bank( $query )
	{ 
		$args = $this->get_args( array( 'query' => $query ) );
		$result = $this->send_request( "rs/suggest/bank", $args );
		return $result;
	}
	
	public function clean_name( $query )
	{ 
		$this->API_URL = 'https://cleaner.dadata.ru/api/v1/';		
		$args = $this->get_args( array( $query ) ); 
		$result = $this->send_request( "clean/name", $args );		
		return $result;
	}
	
	//Определяет координаты адреса (дома, улицы, города)
	public function get_coordinates( $query )
	{ 
		$this->API_URL = 'https://cleaner.dadata.ru/api/v1/';		
		$args = $this->get_args( array( $query ) ); 
		$result = $this->send_request( "clean/address", $args );		
		return $result;		
	}
	
	public function get_location_by_ip( $ip )
	{ 	
		$args = $this->get_args(['ip' => $ip]); 
		$result = $this->send_request( "rs/iplocate/address", $args );					
		if ( !empty($result['location']) )
		{
			$location_id = usam_get_locations(['fields' => 'id', 'search' => $result['location']['data']['city'], 'code' => 'city', 'number' => 1]);
			$result['data']['location_id'] = $location_id;
			return $result['data'];
		}
		return false;			
	}

	protected function get_args( $params )
	{ 		
		$headers["Content-type"] = 'application/json; charset=UTF-8';	
		$headers["Accept"] = 'application/json';	
		$headers["Authorization"] = 'Token '.$this->option['access_token'];
		$headers["X-Secret"] = $this->option['password'];	
		$args = [
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => $headers,
			'body' => json_encode($params),
		];	
		return $args;
	}
	
	public function filter_find_company( $results, $args )
	{	
		if ( empty($results) && !empty($args['search']) ) 
		{				
			$args['search_type'] = empty($args['search_type'])?'inn':$args['search_type'];			
			if ( $args['search_type'] == 'inn' ) 
			{
				$args['ppc'] = !empty($args['ppc'])?$args['ppc']:0;					
				$results = $this->find_company_by_inn( $args['search'], $args['ppc'] );
			}
			else
				$results = $this->find_company(['query' => $args['search']]);
		}
		return $results;
	}
	
	public function filter_location_by_ip( $current_location, $ip )
	{
		if ( !$current_location && $ip ) 
		{
			$location_by_ip = $this->get_location_by_ip( $ip );
			if ( !empty($location_by_ip['location_id']) )
				$current_location = $location_by_ip['location_id'];
		}
		return $current_location;
	}
	
	public function filter_upload_coordinates( $coordinates, $address )
	{
		if ( !$coordinates && $address ) 
		{
			$coordinates = $this->get_coordinates( $address );	
			return isset($coordinates[0])?$coordinates[0]:[];
		}
		return $coordinates;
	}
	
	public function filter_clean_name( $results, $name )
	{
		if ( !$results && $name ) 
		{
			$results = $this->clean_name( $name );	
			return $results[0];
		}
		return $results;
	}
	
	public function service_load()
	{ 
		if ( !usam_is_license_type('FREE') )
		{
			add_filter('usam_find_company_in_directory', [$this,'filter_find_company'], 10, 2);
			add_filter('usam_location_by_ip', [$this,'filter_location_by_ip'], 10, 2);	
		}
		if ( usam_is_license_type('ENTERPRISE') )
		{	
			add_filter('usam_upload_coordinates', [$this,'filter_upload_coordinates'], 10, 2);	
			add_filter('usam_clean_name', [$this,'filter_clean_name'], 10, 2);	
		}
	}
	
	protected function get_default_option( ) 
	{
		return ['access_token' => '', 'password' => ''];
	}
	
	public function display_form() 
	{			
		?>							
		<div class="edit_form">			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='access_token'><?php esc_html_e( 'API-ключ', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php usam_get_password_input( $this->option['access_token'], ['name' => 'access_token', 'id' => 'access_token']); ?>
				</div>
			</div>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='password'><?php esc_html_e( 'Секретный ключ', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php usam_get_password_input( $this->option['password'], ['name' => 'password', 'id' => 'password']); ?>
				</div>
			</div>				
		</div>	
		<?php
	}
	
	public function save_form( ) 
	{		
		
	}
}
?>