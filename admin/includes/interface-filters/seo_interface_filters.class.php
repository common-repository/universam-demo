<?php
require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
class SEO_Interface_Filters extends USAM_Interface_Filters
{	
	protected $search_box = false;
		
	protected function get_filters( ) 
	{				
		return [
			'se' => ['title' => __('Поисковые системы', 'usam'), 'type' => 'select'], 
			'region' => ['title' => __('Регион', 'usam'), 'type' => 'select'], 
			'site' => ['title' => __('Сайты', 'usam'), 'type' => 'select']
		];
	}
	
	public function get_region_options( ) 
	{		
		$location_ids = usam_get_search_engine_regions(['fields' => 'location_id']);	
		$results = [];
		if ( !empty($location_ids) )
		{
			$locations = usam_get_locations(['fields' => 'id', 'include' => $location_ids, 'orderby' => 'include']);
			$results = array();
			foreach ( $locations as $location_id )
				$results[] = ['id' => $location_id, 'name' => usam_get_full_locations_name( $location_id )];
		} 
		return $results;
	}
	
	public function get_keywords_options(  ) 
	{	
		$keywords = usam_get_keywords(['check' => 1]);		
		$results = array();
		foreach ( $keywords as $keyword )
		{
			$results[] =  ['id' => $keyword->id, 'name' => $keyword->keyword];
		}
		return $results;
	}
	
	public function get_site_options(  ) 
	{	
		require_once(USAM_FILE_PATH.'/includes/seo/sites_query.class.php');
		$sites = usam_get_sites(['fields' => ['domain', 'id'],'type' => 'C', 'date_query_statistic' => ['after' => $this->start_date_interval, 'before' => $this->end_date_interval, 'inclusive' => true]]);		
		$results = [['id' => 0, 'name' => __('ВАШ САЙТ','usam').' - '.parse_url( get_site_url(), PHP_URL_HOST)]];		
		foreach ( $sites as $site )
		{
			$results[] = ['id' => $site->id, 'name' => $site->domain];
		}
		return $results;
	}
	
	public function get_se_options() 
	{	
		return [['id' => 'y', 'name' => __('Яндекс', 'usam')], ['id' => 'g', 'name' => __('Гугол', 'usam')]];
	}
}
?>