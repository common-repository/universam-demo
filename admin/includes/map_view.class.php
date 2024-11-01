<?php
require_once( USAM_FILE_PATH . '/admin/includes/filter_processing.class.php' );
abstract class USAM_Map_View 
{			
	protected $query_vars = [];		
	
	protected function get_query_vars() 
	{	
		$this->query_vars = ['meta_query' => [] ];			
		
		$min_latitude = !empty($_POST['min_latitude'])?absint($_POST['min_latitude']):0;
		$max_latitude = !empty($_POST['max_latitude'])?absint($_POST['max_latitude']):90;
		$min_longitude = !empty($_POST['min_longitude'])?absint($_POST['min_longitude']):0;
		$max_longitude = !empty($_POST['max_longitude'])?absint($_POST['max_longitude']):180;
		
		$this->query_vars['meta_query'][] = ['key' => 'latitude', 'compare' => 'BETWEEN', 'value' => [$min_latitude, $max_latitude], 'type' => 'NUMERIC'];
		$this->query_vars['meta_query'][] = ['key' => 'longitude', 'compare' => 'BETWEEN', 'value' => [$min_longitude, $max_longitude], 'type' => 'NUMERIC'];			
		if ( !empty($_REQUEST['s']) )
			$this->query_vars['search'] = trim(stripslashes($_REQUEST['s'])); 
		elseif ( !empty($_REQUEST['search']) )
			$this->query_vars['search'] = trim(stripslashes($_REQUEST['search'])); 
		return $this->query_vars;
	}
		
	protected function get_filter_value( $key, $default = false ) 
	{ 	
		$f = new Filter_Processing();
		return $f->get_filter_value( $key, $default );
	}
	
	protected function get_digital_interval_for_query( $columns_search )
	{				
		if ( !isset($this->query_vars['conditions']) )
			$this->query_vars['conditions'] = [];
		
		$f = new Filter_Processing();
		$this->query_vars['conditions'] = array_merge($this->query_vars['conditions'], $f->get_digital_interval_for_query( $columns_search ) );
	}
	
	protected function get_date_interval_for_query( $columns_search )
	{			
		if ( !isset($this->query_vars['date_query']) )
			$this->query_vars['date_query'] = [];
		
		$f = new Filter_Processing();
		$this->query_vars['date_query'] = array_merge($this->query_vars['date_query'], $f->get_date_interval_for_query( $columns_search ) );
	}
	
	protected function get_string_for_query( $columns_search, $key = 'conditions' )
	{			
		if ( !isset($this->query_vars[$key]) )
			$this->query_vars[$key] = [];
		
		$f = new Filter_Processing();
		$this->query_vars[$key] = array_merge($this->query_vars[$key], $f->get_string_for_query( $columns_search ) );
	}
	
	protected function get_meta_for_query( $type, $key = 'meta_query' )
	{			
		if ( !isset($this->query_vars[$key]) )
			$this->query_vars[$key] = [];
		
		$f = new Filter_Processing();
		$this->query_vars[$key] = array_merge($this->query_vars[$key], $f->get_meta_for_query( $type, $this->query_vars[$key] ) );
	}
}
?>