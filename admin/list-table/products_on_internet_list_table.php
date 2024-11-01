<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
require_once( USAM_FILE_PATH . '/includes/product/products_on_internet_query.class.php');
class USAM_List_Table_products_on_internet extends USAM_List_Table
{	
	protected $order = 'desc'; 
	
	function get_bulk_actions_display() 
	{
		$actions = array(
			'delete'    => __('Удалить', 'usam')
		);
		return $actions;
	}	

	function column_product_title( $item ) 
    {		
		$lzy = defined( 'DOING_AJAX' ) && DOING_AJAX ? false : true; 
		echo "<a href='".get_edit_post_link( $item->product_id )."'>".usam_get_product_thumbnail( $item->product_id, 'manage-products', '', $lzy )."</a>";
		echo "<a href='".get_edit_post_link( $item->product_id )."' class='product_title_link'>".get_the_title( $item->product_id )."</a>"; 
    }

	function column_post( $item )
	{
		?>
		<div class="post_internet">
			<div class="post_internet__description"><?php echo $item->description; ?></div>
			<div class="post_internet__image"><img src='<?php echo $item->foto_url; ?>' loading='lazy'/></div>	
			<div class ="post_internet__like_comment_box">
				<div class ="post_internet__like_comment post_internet__like"><span class="dashicons dashicons-heart"></span><span class="post_internet__like_comment_counter"><?php echo $item->likes; ?></span></div>
				<div class ="post_internet__like_comment post_internet__comment"><span class="dashicons dashicons-admin-comments"></span><span class="post_internet__like_comment_counter"><?php echo $item->comments; ?></span></div>
			</div>				
		</div>	
		<?php
	}	
	
	function column_status( $item )
	{
		echo $item->status?__("Опубликовано","usam"):__("Не опубликовано","usam");
	}
	
	function column_source( $item )
	{		
		echo usam_get_system_svg_icon( $item->source );
	}
	
	function get_sortable_columns()
	{
		$sortable = array(
			'source'        => array('source', false),		
			);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(           
			'cb'             => '<input type="checkbox" />',			
			'image'          => '',
			'product_title'  => __('Ваш товар', 'usam'),	
			'post'           => __('Пост в интернете', 'usam'),	
			'source'         => __('Источник', 'usam'),	
			'status'         => __('Статус', 'usam'),					
			'views'          => '<span class = "usam-dashicons-icon" title="' . esc_attr__('Просмотры' ) . '"></span>',		
        );		
        return $columns;
    }	
	
	function prepare_items() 
	{				
		$this->get_query_vars();				
		
		if ( $this->status != 'all' )
			$this->query_vars['status'] = $this->status;			
		if ( empty($this->query_vars['include']) )
		{					
			
		}
		$this->query_vars['cache_product'] = true;		
		$query = new USAM_Products_Internet_Query( $this->query_vars );
		$this->items = $query->get_results();	
		if ( $this->per_page )
		{
			$total_items = $query->get_total();	
			$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page, ) );
		}			
	}
}