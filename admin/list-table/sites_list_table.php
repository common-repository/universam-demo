<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
require_once( USAM_FILE_PATH .'/includes/seo/keywords_query.class.php' );	
		require_once( USAM_FILE_PATH .'/includes/seo/sites_query.class.php' );
class USAM_List_Table_sites extends USAM_List_Table 
{		
	private $keywords;
	private $keywords_count;
	private $statistics_keywords = array();
	function __construct( $args = array() )
	{	
		parent::__construct( $args );					
		$this->keywords = usam_get_keywords( array('check' => 1 ) );
		$this->keywords_count = count($this->keywords);				
    }
		
	// массовые действия 
	public function get_bulk_actions() 
	{
		if ( ! $this->bulk_actions )
			return array();

		$actions = array(
			'delete' => _x( 'Удалить', 'bulk action', 'usam'),	
		);	
		return $actions;
	}	
	
	function column_domain( $item ) 
    {	
		$actions = $this->standart_row_actions( $item->id, 'site' );
		$actions['statistics'] = '<a class="usam-statistics" href="'.add_query_arg( array('page' => 'seo','tab' => 'site_positions', 'site_id' => $item->id), admin_url('admin.php')).'">'. __('Статистика', 'usam').'</a>';	
		$this->row_actions_table( "<a href='http://$item->domain' target='_blank'>$item->domain</a>", $actions );	
	}
	
	function column_type( $item ) 
    {	
		switch ( $item->type ) 
		{
			case 'C' :
				_e( 'Конкуренты', 'usam');
			break;
			case '' :
	
			break;			
		}
	}
	
	function column_keyword( $item ) 
    {	
		if ( isset($this->statistics_keywords[$item->id]) )
			echo count($this->statistics_keywords[$item->id]);
	}
	
	function column_rating( $item ) 
    {	
		if ( isset($this->statistics_keywords[$item->id]) && $this->keywords_count )
			echo round(count($this->statistics_keywords[$item->id])*100/$this->keywords_count,2);
	}
	
	function get_sortable_columns() 
	{
		$sortable = array(
			'domain'   => array('domain', false),
			'type'     => array('type', false),
			'status'   => array('status', false),	
			'date'     => array('date', false),			
			);
		return $sortable;
	}
       
	function get_columns()
	{
        $columns = array(   
			'cb'          => '<input type="checkbox" />',				
			'domain'      => __('Сайт', 'usam'),
			'description' => __('Описание', 'usam'),		
			'keyword'     => __('Позиция', 'usam'),				
			'rating'      => __('Рейтинг', 'usam'),
			'type'        => __('Тип', 'usam'),	
			'date'        => __('Дата', 'usam'),				
        );
        return $columns;
    }	
	
	public function prepare_items() 
	{
		global $wpdb;	
		
		$this->get_query_vars();
		if ( $this->search == ''  )
		{
			$selected = $this->get_filter_value( 'type' );
			if ( $selected )
				$this->query_vars['type'] = $selected;
		}		
		$query_orders = new USAM_Sites_Query( $this->query_vars );
		$this->items = $query_orders->get_results();
		if ( $this->per_page && !empty($this->items) )
		{
			$site_ids = array();
			foreach($this->items as $item )
			{			
				$site_ids[] = $item->id;
			}
			if ( !empty($site_ids) )
			{				
				$sql_query = "SELECT * FROM ".USAM_TABLE_STATISTICS_KEYWORDS." WHERE site_id IN (".implode(',',$site_ids).")";		
				$statistics_keywords = $wpdb->get_results($sql_query);	
				foreach($statistics_keywords as $keyword )
				{
					$this->statistics_keywords[$keyword->site_id][] = $keyword;
				}
			}
			$this->total_items = $query_orders->get_total();	
			$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page, ) );
		}	
	}
	
	protected function get_filter_tablenav( ) 
	{		
		return array( 'interval' => '' );		
	}
}