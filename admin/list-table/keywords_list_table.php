<?php 
require_once( USAM_FILE_PATH .'/includes/seo/keyword.class.php' );	
require_once( USAM_FILE_PATH . '/includes/seo/keywords_query.class.php' );		
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );	
class USAM_List_Table_keywords extends USAM_List_Table
{
	protected $orderby = 'yandex_hits';
	protected $yandex_hits = 0;	
		
	function column_word( $item ) 
    {			
		$actions = $this->standart_row_actions( $item->id, 'keyword' );		
		if ( $this->query_vars['parent'] == $item->id )	
		{
			$url = add_query_arg( array('parent' => $item->parent ), $this->url  );
			$name = '<a title="'.esc_attr__('Вверх', 'usam').'" href = "'.$url.'">...</a>';	
		}
		else
		{
			if ( $this->query_vars['parent'] )	
				$name = " — ".$item->keyword;
			else
				$name = $item->keyword;
			$url = add_query_arg( array('parent' => $item->id ), $this->url  );
			$name = "<a href='$url'>{$name}</a>";				
		}
		$this->row_actions_table( $name, $actions );	
	}
	
	function column_n( $item )
	{	
		static $i = 0;
		$i++;
		echo $i;
	}
	
	function column_wordstat( $item )
	{	
		echo "<a href='https://wordstat.yandex.ru/#!/?words=".$item->keyword."' rel='noopener' target='_blank'><span class='dashicons dashicons-editor-paste-word'></span></a>";
	}
	
	function column_yandex( $item )
	{	
		echo "<a href='https://yandex.ru/search/?text=".$item->keyword."' rel='noopener' target='_blank' class='yandex_link'>Я</a>";	;
	}
	
	function column_google( $item )
	{	
		echo "<a href='https://www.google.com/search?q=".$item->keyword."' rel='noopener' target='_blank' class='google_link'>G</a>";	;
	}	
	
	function column_favorites( $item )
	{	
		if ( $item->importance )
			echo '<span id="importance" class="dashicons dashicons-star-filled"></span>';
		else
			echo '<span id="importance" class="dashicons dashicons-star-empty"></span>';
	}	
				
	function column_link( $item )
	{			
		if ( $item->link )
		{
			echo "<a href='".$item->link."' target='_blank'>{$item->link}</a>";
		}
	}	
	
	function column_yandex_hits( $item )
	{
		echo usam_currency_display( $item->yandex_hits, array( 'decimal_point' => false ) );
	}
	
	function column_source( $item )
	{			
		$sources = usam_get_keyword_sources();		
		if ( !empty($sources[$item->source]) )
		{
			echo $sources[$item->source];			
		}
	}

	function column_check( $item )
	{			
		if ( $item->check )
			echo '<span class="keyword_check yes"></span>';
		else
			echo '<span class="keyword_check no"></span>';
	}	
	
	function get_bulk_actions_display() 
	{	
		$actions = array(						
			'analyze' => __('Анализировать', 'usam'),
			'check' => __('Отслеживать позицию в поиске', 'usam'),
			'do_not_check' => __('Не отслеживать позицию в поиске', 'usam'),
			'importance' => __('Важное', 'usam'),
			'do_not_importance' => __('Не важное', 'usam'),
			'delete'  => __('Удалить', 'usam'),			
		);
		return $actions;
	}	
		
	public function single_row( $item )
	{
		global $row_item;		
		$row_item = false;
		
		echo '<tr>';
		$this->single_row_columns( $item );
		echo '</tr>';
	}
	
	function get_sortable_columns() 
	{
		$sortable = array(
			'word'  => array('keyword', false),	
			'favorites'  => array('importance', false),	
			'yandex_hits'  => array('yandex_hits', false),	
		);
		return $sortable;
	}
	
	function get_columns()
	{
        $columns = array(        	
			'n'    => '',	
			'cb'        => '<input type="checkbox" />',				
			'favorites' => '',				
			'check'     => '',					
			'yandex'    => '',
			'google'    => '',
			'wordstat'  => '',		
			'word'      => __('Ключевые слова', 'usam'),
			'yandex_hits' => __('Показов в Яндексе', 'usam'),
			'source'    => __('Источник', 'usam'),	
			'link'      => __('Страница', 'usam'),								
        );				
		return $columns;
    }	
	
	public function pagination( $which )
	{
		ob_start();
		parent::pagination( $which );
		
		$output = ob_get_clean();
		$yandex_hits = ' - ' . sprintf( __('Всего показов: %s', 'usam'), usam_currency_display( $this->yandex_hits, array( 'decimal_point' => false ) ) );
		$yandex_hits = str_replace( '$', '\$', $yandex_hits );
		$output = preg_replace( '/(<span class="displaying-num">)([^<]+)(<\/span>)/', '${1}${2}'.' '.$yandex_hits.'${3}', $output );
		echo $output;
	}	
	
	function prepare_items() 
	{					
		$this->get_query_vars();		
		
		if ( isset($_REQUEST['parent'] ) )
			$this->query_vars['parent'] = absint($_REQUEST['parent']);
		else
			$this->query_vars['parent'] = 0;
		
		if ( empty($this->query_vars['include']) )
		{		
			$this->get_digital_interval_for_query( array('yandex_hits') );		
		} 
		$query = new USAM_Keywords_Query( $this->query_vars );
		$this->items = $query->get_results();
		
		foreach ($this->items as $item) 
		{
			$this->yandex_hits += $item->yandex_hits;			
		}		
		if ( $this->query_vars['parent'] )
		{ 
			$item = usam_get_keyword( $this->query_vars['parent'] );	
			array_unshift($this->items, (object)$item);
		}		
		if ( $this->per_page )
		{
			$total_items = $query->get_total();	
			$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page, ) );
		}	
	}
}