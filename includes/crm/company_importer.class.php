<?php
class USAM_Company_Importer
{	
	private $rule;
	private $data;
	private $add = 0;
	private $update = 0;
	
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
		if ( empty($data) )
			return false;
		$this->data = $data;
		
		add_filter( 'block_local_requests', '__return_false' );
		add_filter( 'https_ssl_verify', '__return_false' );
	
		$anonymous_function = function($is, $host, $url) { return true; };	
		add_filter( 'http_request_host_is_external', $anonymous_function, 10, 3 );	
				
		usam_update_object_count_status( false );	
		
		$this->add = 0;
		$this->update = 0;
		$records = $this->import();		

		usam_update_object_count_status( true );			
		
		return ['add' => $this->add, 'update' => $this->update, 'records' => $records];
	}

	private function import( ) 
	{				
		$properties = usam_get_properties(['type' => 'company', 'active' => 1, 'fields' => 'code=>data']);		
		$columns = $this->data[0];
		
		$prices = usam_get_prices(['type' => 'all']);
		$external_code_prices = [];
		if ( isset($columns['type_price'])  )
		{
			foreach($prices as $price)
			{
				$external_code_prices[$price['external_code']] = $price['code'];
			}			
		}	
		$i = 0;
		$start_time = time();
		foreach( $this->data as $number => $row )		
		{	
			$new_data = [];
			$metas = [];
			$new_company_acc = [];				
			$groups = [];						
			if ( isset($row['group']) )
			{
				$groups = explode($this->rule['splitting_array'], $row['group']);	
				require_once( USAM_FILE_PATH.'/includes/crm/groups_query.class.php' );
				$groups = usam_get_groups(['type' => 'company', 'fields' => 'id', 'code' => $groups]);		
			}
			elseif ( isset($this->rule['groups']) )
				$groups = array( $this->rule['groups'] ); 
			
			if ( isset($row['type']) )
				$new_data['type'] = $row['type'];
		
			if ( !empty($row['name']) )
				$new_data['name'] = stripslashes(trim($row['name']));	
			elseif ( !empty($row['company_name']) )
				$new_data['name'] = stripslashes(trim($row['company_name']));				
			elseif ( !empty($row['full_company_name']) )
				$new_data['name'] = stripslashes(trim($row['full_company_name']));
				
			if ( empty($row['company_name']) && isset($row['name']) )
				$new_data['company_name'] = stripslashes(trim($row['name']));		
			
			if ( empty($row['full_company_name']) && isset($row['name']) )
				$new_data['full_company_name'] = stripslashes(trim($row['name']));
								
			if ( isset($row['manager']) && is_numeric($row['manager']) )
				$new_data['manager_id'] = $row['manager'];
			
			foreach( $properties as $code => $property )		
			{
				if ( !empty($row[$code]) )
				{
					if ( $property->field_type == 'date' )
						$metas[$code] = date( $date_format, strtotime( $row[$code] ));
					else
						$metas[$code] = $row[$code];
				}
			}				
			if ( isset($row['code']) )
				$metas['code'] = $row['code'];
			if ( isset($row['description']) )
				$metas['description'] = $row['description'];
			if ( isset($row['employees']) )
				$metas['employees'] = $row['employees'];
			if ( isset($row['type_price']) && isset($external_code_prices[$row['type_price']]) )
				$metas['type_price'] = $external_code_prices[$row['type_price']];
			if ( isset($row['revenue']) )
				$metas['revenue'] = $row['revenue'];
			
			if ( isset($row['bank_name']) )
				$new_company_acc[0]['name'] = $row['bank_name'];
			
			if ( isset($row['bank_bic']) )
				$new_company_acc[0]['bic'] = $row['bank_bic'];
			
			if ( isset($row['bank_number']) )
				$new_company_acc[0]['number'] = $row['bank_number'];
			
			if ( isset($row['bank_ca']) )
				$new_company_acc[0]['bank_ca'] = $row['bank_ca'];
			
			if ( isset($row['bank_currency']) )
				$new_company_acc[0]['currency'] = $row['bank_currency'];
			
			if ( isset($row['bank_address']) )
				$new_company_acc[0]['address'] = $row['bank_address'];
			
			if ( isset($row['bank_swift']) )
				$new_company_acc[0]['swift'] = $row['bank_swift'];	
				
			$this->insert( $new_data, $metas, $new_company_acc, $groups );
			$i = $number+1;
			if ( $this->rule['max_time'] < time() - $start_time )			
				break;	
		}
		return $i;
	}	

	private function insert( $new_data, $metas, $new_company_acc, $groups )
	{	
		global $wpdb;
		
		$company_id = 0;	
		$check_meta_keys = ['inn', 'code'];
		$check_metas = array();
		foreach ( $check_meta_keys as $key ) 
		{
			if ( !empty($metas[$key]) )
				$check_metas[$key] = "meta_key='$key' AND meta_value='".$metas[$key]."'";
		}
		if ( !empty($check_metas) )
		{
			$company_id = $wpdb->get_var("SELECT company_id FROM ".USAM_TABLE_COMPANY_META." WHERE ".implode(' OR ',$check_metas) );
			if ( $this->check_wpdb_error() )
				return false;
		}
		if ( $company_id )
		{
			usam_update_company( $company_id, $new_data );
			foreach( $metas as $meta_key => $meta_value ) 
			{	
				$meta_value = trim( wp_unslash($meta_value) );			
				$update = usam_update_company_metadata( $company_id, $meta_key, $meta_value );
			}	
			if ( !empty($groups) )
				usam_set_groups_object( $company_id, 'company', $groups );
			$this->update++;
			
		}
		else
		{	
			$company_id = usam_insert_company( $new_data );	
			if ( $company_id )
			{	
				if ( !empty($metas) ) 
				{ 
					foreach ( $metas as $meta_key => $meta_value ) 
					{			
						$meta_value = trim( wp_unslash( $meta_value ) );				
						if ( !empty($meta_value) )	
							usam_add_company_metadata( $company_id, $meta_key, $meta_value );
					}
				}	
				if ( !empty($groups) )
					$this->set_groups( $company_id, $groups );
				$this->add++;	
			}			
		} 
		if ( !$company_id ) 
			return 0;				
		
		if ( !empty($new_company_acc) ) 
		{ 
			$new_company_acc['company_id'] = $company_id;
			usam_insert_bank_account( $new_company_acc );
		}
		return $company_id;		 
	}	
	
	function set_groups( $object_id, $group_ids ) 
	{
		global $wpdb;
		foreach ( $group_ids as $key => $id ) 
		{ 
			$wpdb->insert( USAM_TABLE_GROUP_RELATIONSHIPS, ['group_id' => $id, 'object_id' => $object_id], ['%d', '%d']);
		}
	}

	function check_wpdb_error() 
	{
		global $wpdb;
		if ( !$wpdb->last_error ) 
			return false;
		return true;
	}		
}
?>