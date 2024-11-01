<?php
new USAM_Posts_Page();
class USAM_Posts_Page
{
	function __construct( ) 
	{	
		add_filter( 'views_edit-post', array( $this, 'display_views' ) );
		add_filter( 'views_edit-page', array( $this, 'display_views' ) );	
		add_filter( 'views_edit-usam-product', array( $this, 'display_views' ) );
	}		
	
	public function display_views( $views ) 
	{
		global $wp_query;		
		if ( current_user_can( 'edit_others_pages' ) ) 
		{ 
			$class            = ( isset($wp_query->query['orderby']) && 'menu_order title' === $wp_query->query['orderby'] && isset($_GET['orderby'])) ? 'current' : '';
			$query_string     = remove_query_arg( array( 'orderby', 'order' ) );
			$query_string     = add_query_arg( 'orderby', rawurlencode( 'menu_order title' ), $query_string );
			$query_string     = add_query_arg( 'order', rawurlencode( 'ASC' ), $query_string );
			$views['byorder'] = '<a href="' . esc_url( $query_string ) . '" class="' . esc_attr( $class ) . '">' . __('Сортировка', 'usam') . '</a>';
		}
		return $views;
	}
} 
?>