<?php
require_once(USAM_FILE_PATH.'/includes/feedback/customer_reviews_query.class.php');
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_reviews extends USAM_List_Table
{
	private $status_reviews = array( 1 => 'Не утвержденные', 2 => 'Утвержденные', 3 => 'В корзине' );	
	protected $status  = '';
	
	protected $orderby = 'id';
	protected $order   = 'desc';	
		
    function __construct( $args = array() )
	{	
		parent::__construct( $args );		
		
		if ( !empty($_REQUEST['status']) )
			$this->status = absint($_REQUEST['status']);
		else
			$this->status = 'all';		
	}		

	function get_views() 
	{	
		global $wpdb;	
		
		$total = 0;
		$results = $wpdb->get_results("SELECT COUNT(*) AS count, status FROM ".USAM_TABLE_CUSTOMER_REVIEWS." GROUP BY status");			
		foreach ( $results as $key => $value )
		{		
			if ( $value->status != 3 )
				$total	+= $value->count;
		}		
		$sendback = add_query_arg( array( 'status' => 'all' ) );
		$views['all'] = "<a href='$sendback' ". ( $this->status == 'all' ?  'class="current"' : '' ).">". __('Все записи','usam')." <span class='count'> ($total)</span></a>";
		foreach ( $results as $key => $value )
		{			
			if ( $value->count != 0 )
			{				
				$sendback = add_query_arg( array( 'status' => $value->status ) );
				$views[$value->status] = "<a href='$sendback' ". (( $this->status == $value->status ) ?  'class="current"' : '' ).">".$this->status_reviews[$value->status]." <span class='count'> (".$value->count.")</span></a>";
			}
		}	
		return $views;
	}

	function url() 
	{	
		return "?page=feedback&tab=reviews";
	}
	
	function column_page( $item ) 
	{		
		$order_id = usam_get_review_metadata( $item->id, 'order_id' );
		if ( $order_id )
		{		
			printf( __('Отзыв о заказе №%s', 'usam'), usam_get_link_order( $order_id ) );
		}
		else
		{
			$product = get_post( $item->page_id );	
			if ( !empty($product) )
			{
				if ( $product->post_type == "usam-product" )
				{					
					echo usam_get_product_thumbnail( $item->page_id, 'manage-products' );
					?><p><?php _e("Отзыв о товаре","usam"); ?></p><?php
				}
				else
				{
					?><p><?php _e("Отзыв со страницы","usam"); ?></p><?php
				}
				?><a target="_blank" href="<?php echo usam_reviews_url($item, $this->page); ?>"><?php echo $product->post_title; ?></a><?php
			}
		}
	}
	
	function url_status( ) 
	{		
		return $this->get_nonce_url( $this->url()."&amp;status=".$this->status );
	}
	
	function column_review( $item ) 
	{	
		if( $item->title )
			echo "<h4>".usam_get_fast_data_editing( stripslashes($item->title), $item->id, 'title', 'review_edit', 'input' )."</h4>"; 
		$str = '[[1,"'. __("Одна звезда","usam").'"],[2,"'.__("Две звезды","usam").'"],[3,"'.__("Три звезды","usam").'"],[4,"'.__("Четыре звезды","usam").'"],[5,"'.__("Пять звезд","usam").'"]]'; 
		echo usam_get_fast_data_editing( usam_get_rating( $item->rating ), $item->id, 'rating', 'review_edit', 'select', 'make_stars_from_rating', $str ); 
		
		if ( $item->review_text )
			echo usam_get_fast_data_editing( stripslashes($item->review_text), $item->id, 'review_text', 'review_edit', 'textarea' ); 
				
		$actions = [];
		if ( $item->status == 1 ) 
		{
			$actions['approvereview'] = __('Утвердить', 'usam');
			$actions['delete'] = __('В корзину', 'usam');
		}
		elseif ( $item->status == 2 ) 
		{
			$actions['unapprovereview'] = __('Не утвердить', 'usam');
			$actions['delete'] = __('В корзину', 'usam');
		}		
		$actions = $this->standart_row_actions( $item->id, 'review', $actions );		
		if ( $item->status == 3 )
			unset($actions['edit']);		
		$this->row_actions_table( '', $actions );		
	}
	
	public function single_row( $item ) 
	{	
		echo '<tr class ="row review_status_'.$item->status.'" id = "row-'.$item->id.'">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}
	
	function column_response( $item ) 
	{
		echo $this->format_description( $item->review_response );		
	}	
   
    function get_bulk_actions_display() 
	{	
		if ( $this->status != 2 )
			$actions['approvereview'] = __('Утвердить', 'usam');
		if ( $this->status != 1 )
			$actions['unapprovereview'] = __('Не утвердить', 'usam');		
		$actions['delete'] = __('Удалить', 'usam');		
		return $actions;
	}
	
	function get_sortable_columns() 
	{
		$sortable = array(
			'author'         => array('name', false),
			'page'           => array('page_id', false),		
			'date'           => array('date_insert', false),		
			'status'         => array('status', false)
			);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(   
			'cb'            => '<input type="checkbox" />',					
			'review'        => __('Отзыв', 'usam'),		
			'contact'       => __('Автор', 'usam'),		
			'response'      => __('Официальный ответ', 'usam'),	
			'page'          => __('Тип отзыва', 'usam'),		
			'date'          => __('Дата', 'usam'),		
        );
        return $columns;
    }	
	
	function prepare_items() 
	{				
		$this->get_query_vars();
		if ( empty($this->query_vars['include']) )
		{				
			if ( $this->status == 'all' ) 					
				$this->query_vars['status__not_in'] = 3;			
			else
				$this->query_vars['status'] = $this->status;

			$selected = $this->get_filter_value( 'page_id' );		
			if ( $selected ) 
				$this->query_vars['page_id'] = array_map('intval', (array)$selected);			
		}			
		$this->query_vars['cache_contacts'] = true;
		$query = new USAM_Customer_Reviews_Query( $this->query_vars );
		$this->items = $query->get_results();	
		
		$post_ids = array();
		foreach ( $this->items as $key => $item )
		{
			if ( $item->page_id != 0 )
				$post_ids[] = $item->page_id;
		}
		if ( !empty($post_ids) )
			$products = usam_get_products( array( 'post__in' => $post_ids, 'update_post_term_cache' => false, 'stocks_cache' => false, 'prices_cache' => false ), true);
		
		if ( $this->per_page )
		{
			$total_items = $query->get_total();	
			$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page, ) );
		}		
	}
}