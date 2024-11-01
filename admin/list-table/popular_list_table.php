<?php
require_once( USAM_FILE_PATH . '/includes/seo/yandex/webmaster.class.php' );	
require_once( USAM_FILE_PATH . '/includes/seo/keywords_query.class.php' );	
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_popular extends USAM_List_Table
{	
	protected $orderby = 'show';
	protected $order   = 'desc';		
	
	function column_query_text( $item )
	{	
		echo "<a href='https://yandex.ru/search/?text=".$item['query_text']."' class='keyword' target='_blank' rel='noopener'>".$item['query_text']."</a>";
	}
	
	function column_wordstat( $item )
	{	
		echo "<a href='https://wordstat.yandex.ru/#!/?words=".$item['query_text']."' target='_blank' rel='noopener'><span class='dashicons dashicons-editor-paste-word'></span></a>";
	}
	
	function column_favorites( $item )
	{	
		if ( $item['importance'] )
			echo '<span class="dashicons dashicons-star-filled"></span>';
		else
			echo '<span class="dashicons dashicons-star-empty"></span>';
	}
	
	function column_control_keyword( $item )
	{	
		if ( $item['keyword_id'] )
			echo '<span class="dashicons dashicons-no" data-id="'.$item['keyword_id'].'"></span>';
		else
			echo '<span class="dashicons dashicons-plus"></span>';
	}
	
	function column_avg_show( $item )
	{	
		if ( $item['avg_show'] < 11 )
			echo '<span class="item_status item_status_valid">'.$item['avg_show'].'</span>';
		else
			echo $item['avg_show'];
	}
			
	function get_sortable_columns() 
	{
		$sortable = array(
			'query_text' => array('query_text', false),
			'show'       => array('show', false),
			'click'      => array('click', false),
			'avg_show'   => array('avg_show', false),
			'avg_click'   => array('avg_click', false),
			);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(   			
			'favorites'  => '',	
			'control_keyword' => '',	
			'wordstat'  => '',	
			'query_text'=> __('Популярные запросы', 'usam'),
			'show'      => __('Показы', 'usam'),	
			'click'     => __('Клики', 'usam'),				
			'avg_show'  => __('Средняя позиция', 'usam'),
			'avg_click' => __('Средняя позиция клика', 'usam'),
        );
        return $columns;
    }

	public function get_number_columns_sql()
    {       
		return array('show', 'click', 'avg_show', 'avg_click');
    }
	
	function prepare_items() 
	{		
		$webmaster = new USAM_Yandex_Webmaster();		
		$popular = $webmaster->get_popular( 'TOTAL_SHOWS', array( 'TOTAL_SHOWS','TOTAL_CLICKS','AVG_SHOW_POSITION','AVG_CLICK_POSITION') );
				
		$keywords  = usam_get_keywords( array('source' => 'yandex' ) );
				
		$items = array();
		foreach ( $popular as $value )
		{
			$item = array( 'id' => $value['query_id'], 'query_text' => $value['query_text'], 'show' => $value['indicators']['TOTAL_SHOWS'], 'click' => $value['indicators']['TOTAL_CLICKS'], 'avg_show' => round($value['indicators']['AVG_SHOW_POSITION'],2), 'avg_click' => round($value['indicators']['AVG_CLICK_POSITION'],2), 'importance' => 0, 'keyword_id' => 0 );
					
			foreach ( $keywords as $key => $keyword )
			{
				if( $keyword->keyword == $value['query_text'] )
				{
					$item['keyword_id'] = $keyword->id;
					$item['importance'] = $keyword->importance;
					break;
				}
			}			
			$items[] = $item;
		}	
		unset($popular);
		$search_terms = empty( $this->search ) ? array() : explode( ' ', $this->search );			
		if ( !empty($search_terms) )
		{
			foreach ( $items as $item )
			{
				foreach ( $search_terms as $value )
				{
					if ( stripos($item['query_text'], $value) !== false)
					{
						$this->items[] = $item;
					}
				}
			}
		}
		else
			$this->items = $items;	
		
		$this->total_items = count($this->items);			
		$this->forming_tables();
	}
}