<?php 
class USAM_reports_API extends USAM_API
{	
	public static function load_graph_data( WP_REST_Request $request ) 
	{
		$parameters = $request->get_json_params();		
		if ( !$parameters )
			$parameters = $request->get_body_params();	
		
		$type = sanitize_title($parameters['type']);		
		require_once( USAM_FILE_PATH . '/admin/includes/load_graph_data.class.php' );	
		$list = new USAM_Load_Graph_Data();
		return $list->load( $type );
	}	
}
?>