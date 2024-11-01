<?php
class USAM_Contact_Importer
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
		
		$anonymous_function = function( $check ) { return 0; };	
		add_filter( 'usam_check_contact_database', $anonymous_function );		
				
		usam_update_object_count_status( false );	
		
		$this->add = 0;
		$this->update = 0;
		$records = $this->import();	

		usam_update_object_count_status( true );	
		
		return ['add' => $this->add, 'update' => $this->update, 'records' => $records];
	}

	private function import( ) 
	{
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
		foreach($this->data as $number => $row)		
		{				
			$new_company = [];
			$new_company_acc = [];		
			$groups = [];	
			
			if ( isset($row['group']) )
			{
				$groups = explode($this->rule['splitting_array'], $row['group']);	
				require_once( USAM_FILE_PATH.'/includes/crm/groups_query.class.php' );
				$groups = usam_get_groups(['type' => 'contact', 'fields' => 'id', 'code' => $groups]);		
			}
			elseif ( isset($this->rule['groups']) )
				$groups = array( $this->rule['groups'] ); 			
														
			$new_contact = [];			
			$new_contact['contact_source'] = !empty($row['contact_source'])?$row['contact_source']:'import';				
			foreach(['lastname', 'firstname', 'patronymic', 'post', 'code', 'full_name'] as $name_key ) 
				if ( !empty($row[$name_key]) )
					$new_data[$name_key] = stripslashes(trim($row[$name_key]));	
			if ( isset($row['sex']) )
			{
				if ( $row['sex'] != 'm' && $row['sex'] != 'f' )
				{
					if ( $row['sex'] == 'м' )
						$new_data['sex'] = 'm';
					elseif ( $row['sex'] == 'ж' )
						$new_data['sex'] = 'f';
					else
						$new_data['sex'] = '';					
				}
				else
					$new_data['sex'] = $row['sex'];
			}			
			if ( isset($row['type_price']) && isset($external_code_prices[$row['type_price']]) )
				$new_contact['type_price'] = $external_code_prices[$row['type_price']];	
			$contact_id = $this->insert( $new_contact, $groups );
			$i = $number+1;
			if ( $this->rule['max_time'] < time() - $start_time )
				break;
		}
		return $i;
	}	

	private function insert( $new_data, $groups )
	{	
		global $wpdb;	
		$contact_id = 0;		
		$check_meta_keys = ['email', 'phone', 'mobilephone', 'code'];
		$check_metas = array();
		foreach ( $check_meta_keys as $key ) 
		{
			if ( !empty($new_data[$key]) )
				$check_metas[$key] = "meta_key='$key' AND meta_value='".$new_data[$key]."'";
		}
		if ( !empty($check_metas) )
		{		
			$contact_id = $wpdb->get_var("SELECT contact_id FROM ".USAM_TABLE_CONTACT_META." WHERE ".implode(' OR ',$check_metas) );
			if ( $this->check_wpdb_error() )
				return false;
		}	
		if ( $contact_id )
		{
			usam_update_contact( $contact_id, $new_data );		
			if ( !empty($groups) )
				usam_set_groups_object( $contact_id, 'contact', $groups );
			$this->update++;		
		}
		else
		{				
			$contact_id = usam_insert_contact( $new_data );	
			if ( $contact_id )
			{			
				if ( !empty($groups) )
					$this->set_groups( $contact_id, $groups );			
				$this->add++;	
			}
			else
				return false;		
		}		
		return $contact_id;		
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