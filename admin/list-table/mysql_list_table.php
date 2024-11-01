<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_mysql extends USAM_List_Table
{	
	function get_views() 
	{	
		global $wpdb, $db_export;
		if ( empty($db_export) )
			return array();
		$total_items = $db_export->get_var("SELECT COUNT(*) FROM `".$wpdb->posts."` WHERE `post_type` = 'usam-product'");		
		$sendback = remove_query_arg( array('post_status') );	
		$views['all'] = "<a href='$sendback' ". (( empty ($_REQUEST['post_status'])) ?  'class="current"' : '' ).">". __('Все товары','usam')." <span class='count'>($total_items)</span></a>";
		$total_items = $db_export->get_var("SELECT COUNT(*) FROM `".$wpdb->posts."` WHERE `post_status` = 'publish' AND `post_type` = 'usam-product'");				
		if ( $total_items != 0 )
		{
			$sendback = add_query_arg( array( 'post_status' => 'publish' ) );
			$views['publish'] = "<a href='$sendback' ". (( !empty($_REQUEST['post_status']) && $_REQUEST['post_status'] == 'publish') ?  'class="current"' : '' ).">". __('Опубликованные','usam')."<span class='count'>($total_items)</span></a>";
		}
		$total_items = $db_export->get_var("SELECT COUNT(*) FROM `".$wpdb->posts."` WHERE `post_status` = 'draft' AND `post_type` = 'usam-product'");		
		if ( $total_items != 0 )
		{
			$sendback = add_query_arg( array( 'post_status' => 'draft' ) );	
			$views['draft'] = "<a href='$sendback' ". (( !empty ($_REQUEST['post_status']) && $_REQUEST['post_status'] == 'draft') ?  'class="current"' : '' ).">". __('Черновики','usam')."<span class='count'>($total_items)</span></a>";
		}
		$total_items = $db_export->get_var("SELECT COUNT(*) FROM `".$wpdb->posts."` WHERE `post_status` = 'pending' AND `post_type` = 'usam-product'");
		if ( $total_items != 0 )
		{
			$sendback = add_query_arg( array( 'post_status' => 'pending' ) );	
			$views['pending'] = "<a href='$sendback' ". (( !empty ($_REQUEST['post_status']) && $_REQUEST['post_status'] == 'pending') ?  'class="current"' : '' ).">". __('На утверждении','usam')."<span class='count'>($total_items)</span></a>";
		}
		return $views;
	}	
				
	function column_cb($item) 
	{	
		return "<input id='checkbox-".$item['ID']."' type='checkbox' name='cb[]' value='".$item['ID']."'/>";	
    }		
	
	public function extra_tablenav( $which ) 
	{			
		echo '<div class="alignleft actions">';	
		submit_button( __('Перенести', 'usam'), 'primary','insert_post', false, ['id' => 'add-post-submit']);									
		echo '</div>';
	}   
	
	public function display_interface_filters(  ) 
	{ 		
		require_once( USAM_FILE_PATH . "/admin/interface-filters/products_interface_filters.class.php" );
		$interface_filters = new Products_Interface_Filters();
		?>
		<div class='toolbar_filters' v-cloak>
			<?php $interface_filters->display(); ?>
		</div>
		<?php
	}
	 
	function get_sortable_columns()
	{
		$sortable = array(
			'ID'             => array('ID', false),
			'post_title'     => array('post_title', false),		
			'post_status'    => array('post_status', false),			
			'post_type'      => array('post_type', false),				
			'post_date'      => array('post_date', false)
			);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(           
			'cb'             => '<input type="checkbox" />',			
			'ID'             => __('ID', 'usam'),		
			'post_title'     => __('Имя', 'usam'),		
			'post_status'    => __('Статус поста', 'usam'),	
			'post_type'      => __('Тип', 'usam'),		
			'post_date'      => __('Дата', 'usam')
        );		
        return $columns;
    }
	
	function prepare_items() 
	{		
		global $wpdb, $db_export;
		
		$this->get_standart_query_parent(); 
		
		if ( !empty($db_export) )
		{
			if ( isset($_REQUEST['post_status']) )
				$post_status = sanitize_text_field($_REQUEST['post_status']);
			else
				$post_status = "publish', 'draft";
				
			if ( $this->search != '' )
			{				
				$search = "AND (`post_title` LIKE '{$this->search}%' OR `post_title` LIKE '%{$this->search}%')";			
			}
			else
				$search = "";	
			
			$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM `{$wpdb->posts}` WHERE `post_type`='usam-product' AND `post_status` IN ('". $post_status ."') $search ORDER BY {$this->orderby} {$this->order} {$this->limit}";
			$this->items = $db_export->get_results( $sql, ARRAY_A );
			$this->total_items = $db_export->get_var( 'SELECT FOUND_ROWS()' );
		}
		else
		{
			$this->total_items = 0;
			$this->items = array();		
		}
		$this->_column_headers = $this->get_column_info();			
		$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page ) );
	}
}