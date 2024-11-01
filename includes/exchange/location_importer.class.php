<?php
class USAM_Location_Importer
{		
	public function __construct( $id ) 
	{			
		if ( is_array($id) )
			$this->rule = $id;
		else
		{
			require_once( USAM_FILE_PATH . '/includes/exchange/exchange_rule.class.php'  );
			$this->rule = usam_get_exchange_rule( $id );
			$metas = usam_get_exchange_rule_metadata( $id );
			foreach($metas as $metadata )
				$this->rule[$metadata->meta_key] = maybe_unserialize( $metadata->meta_value );
		}
	}
		
	public function start( $data ) 
	{
		global $wpdb;	
		$ids = array();
		usort($data, function($a, $b){  return ($a['parent'] - $b['parent']); });		
		$meta_keys = ['index', 'KLADR', 'FIAS',	'OKATO', 'OKTMO', 'IFNS', 'timezone', 'latitude', 'longitude', 'population'];		
		foreach ( $data as $key => $location )
		{																	
			$id_location = isset($location['id'])?$location['id']:0;		
			$parent = isset($location['parent'])?$location['parent']:0;	
			$location['parent'] = isset($ids[$parent])?$ids[$parent]:0;	
			$metas = [];
			foreach ( $meta_keys as $meta_key )
			{
				if ( isset($location[$meta_key]) )
				{
					$metas[$meta_key] = $location[$meta_key];
					unset($location[$meta_key]);
				}
			}			
			$location_id = 0;
			foreach( ['KLADR','FIAS'] as $meta_key )
			{	
				if ( isset($metas[$meta_key]) )
				{
					$location_id = (int)usam_get_location_id_by_meta($meta_key, $metas[$meta_key]);
					if ( $location_id )
						break;
				}
			}		
			if ( !$location_id )
				$location_id = usam_insert_location( $location );	
			if ( $metas )
			{
				foreach ( $metas as $meta_key => $meta_value )
				{					
					usam_update_location_metadata($location_id, $meta_key, $meta_value);
				}
			}
			$ids[$id_location] = $location_id;									
			$i++;
		}	
		delete_transient( 'usam_location_file' );
		unlink($file_path); 	
		
	}
	
	public function novaposhta_import( ) 
	{
		return false;	
	}				
	
	public function vk_import( ) 
	{
		$count = 1000;	
		foreach( $this->rule['countries'] as $key => $id )
		{						
			$subregion_ids = array();
			$countries = usam_vkontakte_send_request( 'database.getCountriesById', array('country_ids' => $id, 'lang' => $this->rule['lang']));
			$country_id = usam_insert_location( array('name' => $countries[0]['title'], 'code' => 'country', 'parent' => 0) );	
			if ( $country_id )
			{
				usam_update_location_metadata( $country_id, 'vk', $id );
				$offset = 0;
				do 
				{				
					$regions = usam_vkontakte_send_request( 'database.getRegions', array('country_id' => $id, 'count' => $count, 'offset' => $offset, 'lang' => $this->rule['lang']));				
					if ( !empty($regions['items']) )
					{
						foreach( $regions['items'] as $region )
						{
							$region_id = usam_insert_location( array('name' => $region['title'], 'code' => 'region', 'parent' => $country_id) );	
							usam_update_location_metadata( $region_id, 'vk', $region['id'] );				
							$cities_offset = 0;
							do 
							{								
								$cities = usam_vkontakte_send_request('database.getCities', ['need_all' => 0, 'country_id' => $id, 'region_id' => $region['id'], 'count' => $count, 'offset' => $cities_offset, 'lang' => $this->rule['lang']]);	
								if ( !empty($cities['items']) )
								{									
									foreach( $cities['items'] as $city )	
									{
										if ( !empty($city['area']) )
										{
											$subregion_id = 0;
											foreach( $subregion_ids as $key_id => $subregion_name )
											{	
												if ( $subregion_name == $city['area'] )	
												{
													$subregion_id = $key_id;
													break;	
												}
											}
											if ( $subregion_id == 0 )
											{
												$subregion_id = usam_insert_location( array('name' => $city['area'], 'code' => 'subregion', 'parent' => $region_id) );						
												$subregion_ids[$subregion_id] = $city['area'];
											}
										}
										else
											$subregion_id = $region_id;
										$city_id = usam_insert_location( array('name' => $city['title'], 'code' => 'city', 'parent' => $subregion_id) );
										usam_update_location_metadata( $city_id, 'vk', $city['id'] );		
									}
									$cities_offset += $count;
								}
							}				
							while ( count($cities['items']) == $count );
						}
						$offset += $count;
					}
				} 
				while( count($regions['items']) == $count );
			}
			unset($this->rule['countries'][$key]);
			break;
		}
		if ( empty($this->rule['countries']) )
			return false;
		return $this->rule;
	}
}
?>