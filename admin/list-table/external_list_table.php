<?php
require_once( USAM_FILE_PATH . '/includes/seo/yandex/webmaster.class.php' );	
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_external extends USAM_List_Table
{	
	function column_source_url( $item )
	{
		echo "<a href='".$item['source_url']."' target='_blank'>".$item['source_url']."</a>"; 
	}
	
	function column_destination_url( $item )
	{
		echo "<a href='".$item['destination_url']."' target='_blank'>".$item['destination_url']."</a>";
	}
	
	function column_discovery_date( $item )
	{
		$date_format = get_option( 'date_format', 'Y/m/j' );
		echo date( $date_format, strtotime($item['discovery_date']) );
	}
	
	function get_columns()
	{
        $columns = array(   			
			'source_url'      => __('Откуда', 'usam'),	
			'destination_url' => __('Куда', 'usam'),	
			'discovery_date'  => __('Дата обнаружения', 'usam'),	
        );
        return $columns;
    }
	
	function prepare_items() 
	{		
		$offset =($this->get_pagenum() - 1)*$this->per_page;
		
		$webmaster = new USAM_Yandex_Webmaster();		
		$external = $webmaster->get_external( $offset, $this->per_page );
		
		$this->items = $external['links'];
		$this->total_items = $external['count'];				
		
		$this->_column_headers = $this->get_column_info();
		$this->set_pagination_args( array(	'total_items' => $this->total_items, 'per_page' => $this->per_page ) );
	}
}