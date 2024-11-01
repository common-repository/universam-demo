<?php
require_once( USAM_FILE_PATH.'/admin/includes/admin_query.class.php' );
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
require_once( USAM_FILE_PATH . '/admin/includes/manage_columns_products.php' );		
class USAM_Product_List_Table extends USAM_List_Table 
{	
	protected $orderby = 'ID';
	protected $order   = 'desc'; 
	public  $post_status = ['private', 'draft', 'pending', 'publish', 'future'];

    function __construct( $args = array() )
	{	
        parent::__construct( $args );			
		USAM_Manage_Columns_Products::load_manage_custom_column();		
		$this->_column_headers = $this->get_column_info();
    }
	
	public function display_interface_filters(  ) 
	{ 
		if ( $this->filter_box )
		{			
			$interface_filters = $this->get_class_interface_filters(['products']); 							
			$filters = $this->get_filter_tablenav();
			$interface_filters->display( isset($filters['interval']) );
		}
	}
	
	protected function get_default_primary_column_name() 
	{
		return 'product_title';
	}
	
	function no_items() 
	{		
		_e( 'Не найдено ни одного товара.', 'usam');
	}
	
	public function get_views() 
	{	
		global $wp_post_statuses;
		
		$status = implode( "','", $this->post_status );
		$current_status = isset($_REQUEST['post_status'])?$_REQUEST['post_status']:'all';
		
		$num_posts = (array)wp_count_posts( 'usam-product', 'readable' );		
		$total_count = 0;

		if ( !empty( $num_posts ) )
		{		
			$total_count = array_sum( $num_posts );
			// Исключить системные статусы
			foreach ( get_post_stati( array( 'show_in_admin_all_list' => false ) ) as $state ) 	
			{
				$total_count -= $num_posts[$state];		
				unset($num_posts[$state]);						
			}
		}
		$all_text = sprintf(__('Всего <span class="count">(%s)</span>', 'usam'),	number_format_i18n( $total_count ) );
		$all_href = remove_query_arg( array('post_status', 'paged', 'action', 'action2', 'm', 'paged',	's', 'orderby','order') );
		$all_class = ( $current_status == 'all' && empty( $_REQUEST['m'] ) && empty( $_REQUEST['s'] ) ) ? 'class="current"' : '';
		$views = array(	'all' => sprintf('<a href="%s" %s>%s</a>', esc_url( $all_href ), $all_class, $all_text ), );

		foreach ( $num_posts as $status => $count )
		{
			if ( ! isset($wp_post_statuses[$status]) || $count == 0 )
				continue;			
			$text = $wp_post_statuses[$status]->label." <span class='count'>($count)</span>";
			$href = add_query_arg( 'post_status', $status );
			$href = remove_query_arg( array('action', 'action2', 'm', 'paged', 's','orderby','order'), $href );
			$class = ( $current_status == $status ) ? 'class="current"' : '';
			$views[$status] = sprintf( '<a href="%s" %s>%s</a>', esc_url( $href ), $class, $text );
		}
		return $views;
	}
		
	public function storages_css()
	{
		?>
		<style>		
			.wp-list-table .column-product_title {width:20%;}
			<?php 
			foreach( $this->storages as $storage )
			{
				?>.wp-list-table thead .column-storage_<?php echo $storage->id ?>{word-break: break-word;}<?php
			}	
			?>			
		</style>
		<?php 
	} 
	
	function column_title( $item ) 
    {
		$lzy = defined( 'DOING_AJAX' ) && DOING_AJAX ? false : true; 
		echo "<a href='".get_edit_post_link( $item->ID )."'>".usam_get_product_thumbnail( $item->ID, 'manage-products', $lzy )."</a>";
		echo "<a href='".get_edit_post_link( $item->ID )."'>".get_the_title( $item->ID )."</a>";
	}		
	
	function column_date( $item ) 
	{
	   echo date( "d.m.Y H:i:s", strtotime($item->post_date) );	
	}
	
   function column_default( $item, $column_name ) 
   {
		switch( $column_name ) 
		{
			case 'product_title':
			case 'image': 
			case 'stock':
			case 'reserve':
			case 'price':	
			case 'old_price':
			case 'views':			
			case 'sku':
			case 'cats':		
			case 'brand':	
			case 'author':				
				return do_action( "manage_usam-product_posts_custom_column", $column_name, $item->ID );
			break;			
			case 'post_content':
			case 'post_excerpt':
				return "<div class = 'overflow_y'>".stripcslashes($item->$column_name)."</div>";
			break;
			default:
				if ( stripos($column_name, 'storage_') !== false )
				{
					$storage_id = absint(str_replace('storage_', '', $column_name));
					$stock = usam_get_stock_in_storage($storage_id, $item->ID);
					if ( $stock )
						return $stock;
				}
				elseif ( stripos($column_name, 'type_price_') !== false )
				{
					$code = str_replace('type_price_', '', $column_name);
					$price = usam_get_product_price_currency( $item->ID, false, $code );
					if ( $price )
						return $price;
				}
				else
					return stripcslashes($item->$column_name);
			break;
		}
    }   
		
	public function has_items() 
	{
		return have_posts();
	}
		
	public function display_rows( $posts = array(), $level = 0 ) 
	{
		global $wp_query;

		if ( empty($posts) )
			$posts = $wp_query->posts;			
		$this->_display_rows( $posts, $level );
	}
	
	public function single_row( $item ) 
	{		
		echo '<tr data-id = "'.$item->ID.'">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}	

	private function _display_rows( $posts, $level = 0 ) 
	{
		foreach ( $posts as $post )
			$this->single_row( $post, $level );
	}
	
	protected function column_cb( $item ) 
	{				
		$checked = in_array($item->ID, $this->records )?"checked='checked'":"";		
		echo "<input id='checkbox-".$item->ID."' type='checkbox' name='cb[]' value='".$item->ID."' ".$checked."/>";
    }		
	
	function query_vars( $query_vars )
	{
		return $query_vars;
	}
	
	protected function get_columns_storages( $columns )
	{
		foreach( $this->storages as $storage )
		{
			if ( count($this->storages) > 4 )
			{
				$t = strlen($storage->title) > 30 ? '...':'';
				$columns['storage_'.$storage->id] = mb_substr($storage->title,0,30).$t;
			}
			else
				$columns['storage_'.$storage->id] = $storage->title;
			
			$columns['storage_'.$storage->id] = $storage->title;
		}	
		return $columns;
	}
	
	public function get_sortable_columns() 
	{
		$sortable = [
			'product_title' => array('title', false),			
			'price'         => array('price', false),		
			'date'          => array('date', false),			
			'sku'           => array('sku', false),		
		];
		return $sortable;
	}
	
	public function prepare_items() 
	{			
		if ( !$this->total_items )
		{
			global $wp_query;				
			
			$this->get_query_vars();	

			$offset = ($this->get_pagenum() - 1) * $this->per_page;		
			
			if ( !empty($this->query_vars['include']) )
				$this->query_vars['post__in'] = $this->query_vars['include'];	
			
			$this->query_vars['prices_cache'] = true;
			$this->query_vars['stocks_cache'] = true;
			$this->query_vars['product_meta_cache'] = true;		
			$this->query_vars['post_type'] = 'usam-product';
			$this->query_vars['posts_per_page'] = $this->per_page;
			$this->query_vars['offset'] = $offset;
			$this->query_vars['post_parent'] = 0;
			if ( !empty($_REQUEST['s']) )
				$this->query_vars['s'] = trim(stripslashes($_REQUEST['s']));
			$this->query_vars['post_status'] = isset($_REQUEST['post_status'])?$_REQUEST['post_status']:$this->post_status;		
			
			$this->query_vars = $this->query_vars( $this->query_vars );
			$wp_query = new WP_Query( $this->query_vars );		
			$this->_column_headers = $this->get_column_info();		
			$this->set_pagination_args(['total_items' => $wp_query->found_posts, 'total_pages' => $wp_query->max_num_pages, 'per_page' => $this->per_page]);
		}
	}	
}