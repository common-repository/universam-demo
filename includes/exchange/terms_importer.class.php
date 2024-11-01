<?php
class USAM_Terms_Importer
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

		$this->data = apply_filters( 'usam_terms_importer_data', $data, $this->rule );	
						
		$this->add = 0;
		$this->update = 0;
		$records = $this->import();			
		
		return array( 'add' => $this->add, 'update' => $this->update, 'records' => $records );
	}

	private function import( ) 
	{				
		wp_suspend_cache_invalidation( true );	
		
		$taxonomy = 'usam-'.$this->rule['type'];
		
		$column_id = false;		
		$column_parent_id = false;		
		$columns = $this->data[0];		
		foreach($columns as $column => $value)
		{
			if ( $column == 'id' )
				$column_id = true;	
			elseif ( $column == 'parent_id' )
				$column_parent_id = true;	
		}		
		$i = 0;
		$start_time = time();
		if ( $column_id && $column_parent_id )
		{		
			$args = array();			
			foreach ( $this->data as $number => $rows )
			{	
				$term_id = usam_term_id_by_meta('external_code', $rows['id'], $taxonomy);		
				if ( $term_id )
				{
					$args[$rows['id']] = array('term_id' => $term_id, 'parent' => $rows['parent_id'], 'name' => $rows['name']);
				}
				else
				{
					$term = get_term_by('name', $rows['name'], $taxonomy);	
					if ( !empty($term) )
					{									
						$args[$rows['id']] = array('term_id' => $term->term_id, 'parent' => $rows['parent_id']);
						usam_update_term_metadata( $term->term_id, 'external_code', $rows['id'] );
						wp_cache_set("usam_term_external_code-".$rows['id'], $term->term_id);
					}
					else
					{
						$insert_data = wp_insert_term( $rows['name'], $taxonomy );		
						if( !is_wp_error($insert_data) )
						{
							$args[$rows['id']] = ['term_id' => $insert_data['term_id'], 'parent' => $rows['parent_id']];
							usam_update_term_metadata( $insert_data['term_id'], 'external_code', $rows['id'] );
							wp_cache_set("usam_term_external_code-".$rows['id'], $insert_data['term_id']);
							$this->add++;
						}
					}					
				}	
				$i = $number+1;
				if ( $this->rule['max_time'] < time() - $start_time )
					break;					
			}					
			foreach ( $this->data as $number => $rows )			
			{														
				if ( isset($args[$rows['id']]) )
				{		
					$update_term_args = array();
					if ( !empty($rows['parent_id']) && isset($args[$rows['parent_id']]['term_id']) )
						$update_term_args['parent'] = $args[$rows['parent_id']]['term_id'];
					if ( isset($args[$rows['id']]['name']) )
						$update_term_args['name'] = $args[$rows['id']]['name'];
					if ( !empty($update_term_args) )
					{
						if ( wp_update_term( $args[$rows['id']]['term_id'], $taxonomy, $update_term_args ) )
							$this->update++;		
					}
				}
			}
		}
		else
		{					
			foreach ( $this->data as $number => $rows ) 
			{
				$parent = 0;
				foreach ( $rows as $column => $term_name ) 
				{							
					if ( $column == 'name' )
					{						
						$term = get_term_by( 'name', $term_name, $taxonomy );
						if ( !empty($term) )
						{
							if ( $term->parent != $parent )
							{
								wp_update_term( (int)$term->term_id, $taxonomy, ['parent' => $parent] );
								$this->update++;
							}
							$parent = $term->term_id;		
						}
						else
						{
							$insert_data = wp_insert_term( $term_name, $taxonomy, ['parent' => $parent] );
							if( ! is_wp_error($insert_data) )
							{
								$parent = $insert_data['term_id'];
								$this->add++;
							}									
						}
					}
				}
				$i = $number+1;
				if ( $this->rule['max_time'] < time() - $start_time )
					break;	
			}
		}				
		wp_suspend_cache_invalidation( false );	
		wp_cache_flush();
		clean_taxonomy_cache( $taxonomy );
		return $i;
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