<?php
require_once( USAM_FILE_PATH .'/includes/feedback/webform.php'  );
require_once(USAM_FILE_PATH.'/includes/crm/contactings_query.class.php');
require_once(USAM_FILE_PATH.'/includes/crm/contacting.class.php');
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_Contacting extends USAM_List_Table 
{			
	protected $statuses = [];
	protected $status = 'work';	
	
	function __construct( $args = [] )
	{
		parent::__construct( $args );		
		$this->statuses = usam_get_object_statuses(['type' => 'contacting', 'fields' => 'code=>data', 'cache_results' => true]);		
		add_action( 'admin_footer', [&$this, 'admin_footer'] );			
    }
	
	function column_id( $item )
	{	
		$url = add_query_arg(['form' => 'view', 'id' => $item->id, 'form_name' => 'contacting'],$this->url );
		echo '<a class="row-title" href="'.$url.'">'.$item->id.'</a>';	
		echo '<div class="document_date">'.__("от","usam").' '.usam_local_formatted_date( $item->date_insert ).'</div>';	
	}
	
	function get_bulk_actions_display() 
	{
		$actions = array();		
		if ( usam_check_current_user_role('administrator') ) 
			$actions['delete'] = __('Удалить', 'usam');
		$actions['completed'] = __('Завершить', 'usam');
		return $actions;
	}
	
	function column_page( $item )
	{	
		if( $item->post_id )
		{
			$post = get_post( $item->post_id );
			if( $post )
			{
				if( $post->post_type == 'usam-product' )
				{
					?>
					<div class="product">					
						<?php
						echo "<span class='js-product-viewer-open viewer_open product_image image_container' product_id='$post->ID'>".usam_get_product_thumbnail( $post->ID, 'manage-products' )."</span>";
						echo "<a href='".get_edit_post_link( $post->ID )."' class='product_title_link'>".$post->post_title."</a>"; 
						$sku = usam_get_product_meta( $post->ID, 'sku' );
						if ( $sku )
						{
							?><div class="product_sku"><?php _e( 'Артикул', 'usam'); ?>: <span class="js-copy-clipboard"><?php echo esc_html( $sku ) ?></span></div><?php
						}						
						?>
					</div>
					<?php
				}
				else
					echo '<a href="'.get_permalink($post->ID).'">'.$post->post_title.'</a>';		
			}
		}
	}
	
	function column_webform( $item )
	{
		$webform_code = usam_get_contacting_metadata( $item->id, 'webform');	
		$webform = usam_get_webform( $webform_code, 'code' );
		if ( !empty($webform) )
		{
			echo '<a href="'.add_query_arg(['webform' => $webform_code, 'status' => $this->status],$this->url ).'">'.$webform['title'].'</a>';
		}
	}		
	
	function column_status( $item ) 
	{		
		if ( $item->status == 'canceled' || $item->status == 'controlled' ) 
			$this->display_object_status_name( $item );
		else
		{			
			if ( $item->status == 'completed' ) 
				$this->display_object_status_name( $item );
			elseif ( $item->status == 'not_started' || $item->status == 'stopped') 
			{
				?>
				<div class='event_buttons'>
					<button type='submit' class='button js_start_performing' data-status='started'><?php _e('Начать выполнять', 'usam'); ?></button>
					<span class="js_status_result_started hide"><span class="item_status item_status_valid"><?php _e('Выполняется', 'usam'); ?></span></span>
				</div>
				<?php
			}		
			else
			{				
				$statuses = usam_get_object_statuses_by_type( 'contacting', $item->status );
				$this->display_status_selection( $statuses, $item );
			}
		}
	}
	
	protected function display_status_selection( $statuses, $item ) 
	{
		?>
		<select data-id = "<?php echo $item->id; ?>" class = "js-select-status-record">
			<?php
			foreach( $statuses as $status )
			{
				if ( $status->visibility || $item->status == $status->internalname )
				{
					?><option <?php selected($item->status, $status->internalname); ?> value='<?php echo $status->internalname; ?>'><?php echo $status->name; ?></option><?php
				}
			}
			?>
		</select>
		<?php
	}
	
	protected function display_object_status_name( $item ) 
	{ 
		if ( $item->status == 'started' )
		{
			?><span class="item_status item_status_valid"><?php echo usam_get_object_status_name( $item->status, 'contacting' ); ?></span><?php
		}
		elseif ( $item->status == 'controlled' )
		{
			?><span class="item_status status_customer"><?php echo usam_get_object_status_name( $item->status, 'contacting' ); ?></span><?php
		}
		else
		{
			?><span class="item_status status_blocked"><?php echo usam_get_object_status_name( $item->status, 'contacting' ); ?></span><?php
		}
	}
	
	public function single_row( $item ) 
	{				
		echo '<tr class ="row" id = "row-'.$item->id.'" data-id = "'.$item->id.'">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}	
	
	function get_columns()
	{
        $columns = array(   
			'cb'            => '<input type="checkbox" />',	
			'id'            => __('Номер', 'usam'),	
			'webform'       => __('Веб-форма', 'usam'),
			'status'        => __('Статус', 'usam'),	
			'last_comment'  => __('Последний комментарий', 'usam'),
			'page'          => __('Страница', 'usam'),
        );
        return $columns;	
    }	
	
	public function get_views() 
	{ 
		global $wpdb;
		
		$url = remove_query_arg(['post_status', 'paged', 'action2', 'm',  'paged', 's', 'orderby','order']);		
		$statuses = ['work' => 0];		
		$total_count = 0;	
		foreach( $this->statuses as $code => $status )
		{
			$statuses[$code] = $status->number;
			$total_count += $status->number;
			if ( !$status->close )
				$statuses['work']++;
		}		
		$all_text = sprintf(_nx('Всего <span class="count">(%s)</span>', 'Всего <span class="count">(%s)</span>', $total_count, 'events', 'usam'), number_format_i18n($total_count) );
		$all_class = $this->status === 'all' && $this->search == '' ? 'class="current"' : '';	
		$href = add_query_arg( 'status', 'all', $url );
		$views = array(	'all' => sprintf('<a href="%s" %s>%s</a>', esc_url( $href ), $all_class, $all_text ), );				
	
		$all_text = sprintf(_nx('В работе <span class="count">(%s)</span>', 'В работе <span class="count">(%s)</span>', $statuses['work'], 'events', 'usam'), number_format_i18n($statuses['work']) );
		$all_class = $this->status === 'work' && $this->search == '' ? 'class="current"' : '';	
		$href = add_query_arg( 'status', 'work', $url );
		$views['work'] = sprintf('<a href="%s" %s>%s</a>', esc_url( $href ), $all_class, $all_text );	

		foreach( $this->statuses as $status )		
		{			
			$number = !empty($statuses[$status->internalname])?$statuses[$status->internalname]:0;
			if ( !$number )
				continue;
			
			$text = $text = sprintf( $status->short_name.' <span class="count">(%s)</span>', number_format_i18n( $number )	);
			$href = add_query_arg( 'status', $status->internalname, $url );
			$class = $this->status === $status->internalname ? 'class="current"' : ''; 
			$views[$status->internalname] = sprintf( '<a href="%s" %s>%s</a>', esc_url( $href ), $class, $text );			
		}		
		return $views;
	}
	
	function prepare_items() 
	{		
		if( current_user_can('view_contacting') )
		{
			add_action('admin_footer', function(){
				require_once( USAM_FILE_PATH.'/admin/templates/template-parts/product-viewer.php' );					
			});				
			$this->get_query_vars();							
			if ( empty($this->query_vars['include']) )
			{
				$selected = $this->get_filter_value( 'contacts' );
				if ( $selected )
					$this->query_vars['contacts'] = array_map('intval', (array)$selected);	
				
				$selected = $this->get_filter_value( 'manager' );
				if ( $selected )
					$this->query_vars['manager_id'] = array_map('intval', (array)$selected);	
				
				$selected = $this->get_filter_value( 'webform' );
				if ( $selected )		
				{
					$webform = array_map('sanitize_title', (array)$selected);		
					$this->query_vars['meta_query'][] = array('key' => 'webform', 'value' => $webform, 'compare' => 'IN' );	
				}	
				if ( $this->status == 'work' )
				{
					$this->query_vars['status'] = [];				
					foreach ( $this->statuses as $key => $status )	
					{
						if ( !$status->close )
							$this->query_vars['status'][] = $key;
					}
				}
				elseif ( $this->status != 'all' )
					$this->query_vars['status'] = $this->status;				
				$this->get_vars_query_filter();
			}		
			$this->query_vars['cache_meta'] = true;		
			$this->query_vars['cache_contacts'] = true;		
			$this->query_vars['add_fields'] = ['last_comment'];
			$query = new USAM_Contactings_Query( $this->query_vars );
			$this->items = $query->get_results();
			if ( $this->per_page )
			{
				$total_items = $query->get_total();	
				$this->set_pagination_args(['total_items' => $total_items, 'per_page' => $this->per_page]);
			}	
		}
	}
}