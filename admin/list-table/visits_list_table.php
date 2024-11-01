<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
require_once( USAM_FILE_PATH . '/includes/analytics/visits_query.class.php' );
class USAM_List_Table_visits extends USAM_List_Table 
{		
	protected $order = 'desc';	
//	protected $orderby = 'date_update';	
	
	function column_time( $item ) 
	{
		echo human_time_diff( strtotime($item->date_update), strtotime($item->date_insert) );
	}	
	
	function column_ip( $item ) 
	{
		echo long2ip($item->ip);
	}

	function column_referer( $item ) 
	{
		$url = usam_get_visit_metadata($item->id, 'referer');
		?><a href='<?php echo $url; ?>' target="_blank"><?php echo $url; ?></a><?php
	}
	
	function column_view( $item ) 
	{
		?><a href='<?php echo admin_url("admin.php?page=feedback&tab=monitor&view=table&table=pages_viewed&visit_id=$item->id"); ?>'><?php echo $item->views; ?></a><?php
	}
	
	function column_visits( $item ) 
	{
		?><a href='<?php echo admin_url("admin.php?page=feedback&tab=monitor&view=table&table=visits&contact=$item->contact_id"); ?>'><?php echo $item->visits; ?></a><?php
	}
	
	protected function get_filter_tablenav( ) 
	{		
		return ['interval' => ''];
	}

	function column_device( $item ) 
	{
		$device = usam_get_visit_metadata($item->id, 'device');		
		echo $device == 'mobile'?__('Мобильные','usam'):__('ПК','usam');
	}	
	
	function column_source( $item ) 
	{
		if ( $item->source == 'bot' )
			return __('Робот','usam');
		elseif ( $item->source == 'link' )
			return __('Прямой заход','usam');
		else
			return $item->source;
	}
	
	function get_sortable_columns() 
	{
		$sortable = [		
			'contact'    => array('contact_id', false),		
			'date'       => array('date_insert', false),
			'view'       => array('views', false),
			'referer'    => array('referer', false)			
		];
		return $sortable;
	}
		
	function get_columns(){
        $columns = [   
			'id'            => __('Номер', 'usam'),
			'date'          => __('Дата', 'usam'),		
			'contact'       => __('Контакт', 'usam'),						
			'time'          => __('Время на сайте', 'usam'),	
			'visits'        => __('Номер визита', 'usam'),		
			'view'          => __('Просмотры', 'usam'),	
			'source'        => __('Источник', 'usam'),	
			'referer'       => __('Переход с сайта', 'usam'),						
			'device'        => __('Тип устройства', 'usam'),		
			'ip'            => __('IP', 'usam'),
        ];
        return $columns;
    }
	
	function prepare_items() 
	{						
		$this->get_query_vars();
		$this->query_vars['cache_contacts'] = true;	
		$this->query_vars['cache_meta'] = true;				
		if ( empty($this->query_vars['include']) )
		{		
			$selected = $this->get_filter_value( 'contact' );
			if ( $selected ) 
				$this->query_vars['contact_id'] = array_map('intval', (array)$selected);
			
			$selected = $this->get_filter_value( 'channel' );
			if ( $selected ) 
				$this->query_vars['channel'] = array_map('sanitize_title', (array)$selected);	
						
			$selected = $this->get_filter_value( 'bot' );
			if ( $selected && $selected !== 'all' ) 
			{
				if ( $selected == 'people' ) 
					$this->query_vars['source__not_in'] = 'bot';	
				else
					$this->query_vars['source'] = 'bot';
			}	
			foreach ( ['category', 'brands', 'category_sale', 'catalog', 'selection', 'variation'] as $tax_slug )
			{						
				$selected = $this->get_filter_value($tax_slug);
				if ( $selected )	
				{
					if ( !isset($this->query_vars["term_ids"]) )
						$this->query_vars["term_ids"] = [];
					$this->query_vars["term_ids"] = array_merge( $this->query_vars["term_ids"], array_map('intval', (array)$selected) );	
				}
			}	
			$this->get_digital_interval_for_query(['visits', 'views']);			
		}
		$query = new USAM_Visits_Query( $this->query_vars );	
		$this->items = $query->get_results();
		
		if ( $this->per_page )
		{
			$this->total_items = $query->get_total();			
			$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page, ) );
		}	
	}
}