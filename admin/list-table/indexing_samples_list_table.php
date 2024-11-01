<?php
require_once( USAM_FILE_PATH . '/includes/seo/yandex/webmaster.class.php' );	
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_indexing_samples extends USAM_List_Table
{	
	function column_url( $item )
	{	
		echo "<a href='".$item['url']."' target='_blank'>".$item['url']."</a>";
	}
	
	function column_date( $item )
	{	
		echo date("d.m.Y",strtotime($item['access_date']));
	}
	
	function column_http_code( $item )
	{	
		if ( $item['http_code'] == 200 )
			echo "<span class='item_status_valid item_status'>".$item['http_code']."</span>";
		elseif ( $item['http_code'] == 404 )
			echo "<span class='item_status_attention item_status'>".$item['http_code']."</span>";
		elseif ( $item['http_code'] == 301 )
			echo "<span class='item_status_notcomplete item_status'>".$item['http_code']."</span>";
		else
			echo $item['http_code'];
	}
			
	function get_columns()
	{
        $columns = array(   			
			'url'       => __('Ссылка', 'usam'),	
			'date'      => __('Дата обнаружения', 'usam'),	
			'http_code' => __('Код', 'usam'),		
        );
        return $columns;
    }

	public function get_number_columns_sql()
    {       
		return array('show', 'click', 'avg_show', 'avg_click');
    }
	
	function prepare_items() 
	{		
		$offset =($this->get_pagenum() - 1)*$this->per_page;
		
		$webmaster = new USAM_Yandex_Webmaster();		
		$external = $webmaster->get_indexing_samples( $offset, $this->per_page );
		
		$this->items = $external['samples'];
		$this->total_items = $external['count'];				
		
		$this->_column_headers = $this->get_column_info();
		$this->set_pagination_args( array(	'total_items' => $this->total_items, 'per_page' => $this->per_page ) );
	}
}