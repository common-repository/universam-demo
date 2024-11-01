<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_Discounts_Table extends USAM_List_Table 
{		
	protected $status = 1;	
	protected $type_rule;	
	
	function __construct( $args = array() )
	{			
		parent::__construct( $args );	
		$this->status = isset($_REQUEST['status']) ? (string)$_REQUEST['status'] : $this->status;
	}
	
	function get_bulk_actions_display() 
	{	
		$actions = array(			
			'activate'  => __('Активировать', 'usam'),
			'deactivate' => __('Отключить', 'usam'),
			'delete'    => __('Удалить', 'usam'),
		);
		return $actions;
	}	
	
	public function get_views() 
	{
		global $wpdb;
		
		$url = remove_query_arg( array('post_status', 'paged', 'action2', 'm',  'paged', 's', 'orderby','order','status') );	
		$views_query_vars = $this->get_views_query_vars();
		$views_query_vars['fields'] = array('active', 'count');
		$views_query_vars['groupby'] = 'active';
		if( isset($views_query_vars['active']) )
			unset($views_query_vars['active']);	
		$results = usam_get_discount_rules( $views_query_vars );
		
		$statuses = array();		
		$total_count = 0;	
		if ( !empty( $results ) )
		{			
			foreach ( $results as $result )
			{				
				$statuses[$result->active] = $result->count;						
			}
			$total_count = array_sum( $statuses );
		} 
		$all_text = sprintf(_nx('Все правила <span class="count">(%s)</span>', 'Все правила <span class="count">(%s)</span>', $total_count, 'events', 'usam'), number_format_i18n($total_count) );
		$all_class = $this->status == 'all' && $this->search == '' ? 'class="current"' : '';	
		$href = add_query_arg( 'status', 'all', $url );
		$views = array(	'all' => sprintf('<a href="%s" %s>%s</a>', esc_url( $href ), $all_class, $all_text ), );				
		$statuses_rule = array( 1 => __('Активные','usam'), 0 => __('Отключенные','usam') );
		foreach ( $statuses_rule as $key => $title )		
		{			
			$number = !empty($statuses[$key])?$statuses[$key]:0;
			if ( !$number )
				continue;
			$text = $text = sprintf( $title.' <span class="count">(%s)</span>', number_format_i18n( $number )	);
			$href = add_query_arg( 'status', $key, $url );
			$class = $this->status == (string)$key ? 'class="current"' : '';	
			$views[$key] = sprintf( '<a href="%s" %s>%s</a>', esc_url( $href ), $class, $text );			
		}		
		return $views;
	}
	
	function column_name( $item ) 
    {		
		$this->row_actions_table( $this->item_edit( $item->id, $item->name, $this->type_rule.'_discount' ), $this->standart_row_actions( $item->id, $this->type_rule.'_discount', ['copy' => __('Копировать', 'usam')] ) );	
	}	
	
	function column_type_prices( $item ) 
    {		
		$prices = usam_get_prices( );	
		$i = 0;
		$type_prices = usam_get_discount_rule_metadata( $item->id, 'type_prices');
		if ( !empty($type_prices) )
			foreach ( $prices as $price )
			{ 
				if ( in_array($price['code'], $type_prices) )
				{			
					if ( $i > 0 )
						echo '<hr size="1" width="90%">';
					echo $price['title'].' ( '.$price['code'].' )';				
					$i++;
				}
			}
	}
	
	function column_discount( $item ) 
    {
		switch ( $item->dtype ) 
		{
			case 'f':	
				echo usam_get_formatted_price( $item->discount );		
			break;
			case 'p':	
				echo round($item->discount, 2).'%';		
			break;				
		}	
	}
			 
	function get_sortable_columns()
	{
		$sortable = [
			'title'   => ['name', false],		
			'active'  => ['active', false],		
			'date'    => ['date_insert', false],	
		];
		return $sortable;
	}
	
	function prepare_items() 
	{	
		$this->get_query_vars();		
	
		$this->query_vars['type_rule'] = $this->type_rule;
		if ( empty($this->query_vars['include']) )
		{				
			if ( $this->status != 'all' )
				$this->query_vars['active'] = $this->status;
		} 	
		if ( $this->type_rule == 'product' || $this->type_rule == 'fix_price' )
			$this->query_vars['fields'] = ['id', 'name', 'active', 'start_date', 'end_date', 'date_insert', 'products', 'dtype', 'discount'];
			
		$query = new USAM_Discount_Rules_Query( $this->query_vars );
		$this->items = $query->get_results();	
		if ( $this->per_page )
		{
			$total_items = $query->get_total();	
			$this->set_pagination_args(['total_items' => $total_items, 'per_page' => $this->per_page]);
		}	
	}
}
?>