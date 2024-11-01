<?php
/**
 * Функции для редактирования и добавления товаров на странице ТОВАРОВ
 */
abstract class USAM_Admin_Products_List
{
	function __construct( ) 
	{	
		require_once( USAM_FILE_PATH . '/admin/includes/manage_columns_products.php' );			
		require_once( USAM_FILE_PATH.'/admin/includes/admin_query.class.php' );
		USAM_Manage_Columns_Products::load_manage_custom_column();	
		
		if ( !isset($_REQUEST['no_minors_allowed']))
			add_filter( 'request', array(&$this, 'no_minors_allowed'), 10, 1 );

		add_filter( 'request', array(&$this, 'column_sql_orderby') );			
		add_action( 'manage_posts_extra_tablenav', array(&$this, 'display_filters') );	
		add_action( 'quick_edit_custom_box', array(&$this, 'quick_edit_boxes'), 10, 2 );
		add_action( 'bulk_edit_custom_box', array(&$this, 'quick_edit_boxes'), 10, 2 );			
		add_action( 'admin_notices',        array(&$this, 'custom_bulk_admin_notices'));		
		add_action( 'handle_bulk_actions-edit-usam-product', array(&$this, 'bulk_action_handler'), 10, 3);
		add_filter( 'bulk_actions-edit-usam-product', [&$this, 'register_bulk_actions'] );	
		add_filter( 'disable_months_dropdown', [$this, 'disable_months_dropdown'] );	
		add_filter( 'pre_get_posts', array($this, 'split_the_query'), 8 );
		add_filter( 'get_terms_args', array(&$this, 'get_terms_args'), 10, 2 );	
		
		$this->load();
	}	
	
	function get_terms_args($args, $taxonomies)
	{	
		$args['update_term_meta_cache'] = false;
		return $args;
	}	
	
	function load(  ) {	}

	function split_the_query( $query )
	{			
		$query->query_vars['product_meta_cache'] = true;
		$query->query_vars['post_meta_cache'] = true;
		$query->query_vars['user_list_cache'] = true; 
		$query->query_vars['prices_cache'] = true; 
		$query->query_vars['stocks_cache'] = true; 
		$query->query_vars['product_attribute_cache'] = !empty($this->get_admin_columns());	
	}
	
	function get_admin_columns()
	{
		static $terms = null;
		if ( $terms === null )
			$terms = get_terms(['taxonomy' => 'usam-product_attributes', 'hide_empty' => 0, 'usam_meta_query' => [['key' => 'admin_column','value' => 1, 'compare' => '=']]]);
		return $terms;
	}
		
	public function disable_months_dropdown( $post_type ) 
	{	
		return true;
	}
	
	function register_bulk_actions( $bulk_actions ) 
	{				
		$print = false;
		if ( !empty($_GET['post_status']) && $_GET['post_status'] != 'all' )						
		{			
			switch ( $_GET['post_status'] ) 
			{
				case 'draft' :
					$bulk_actions['publish'] = __('Опубликовать', 'usam');
					$bulk_actions['archive'] = __('В архив', 'usam');
					$print = true;			
				break;
				case 'publish' :
					$bulk_actions['draft'] = __('В черновик', 'usam');
					$bulk_actions['archive'] = __('В архив', 'usam');
					$print = true;							
				break;
				case 'archive' :
					$bulk_actions['draft'] = __('В черновик', 'usam');					
					$bulk_actions['publish'] = __('Опубликовать', 'usam');
				break;								
			}			
		}	
		else
		{						
			$bulk_actions['draft'] = __('В черновик', 'usam');
			$bulk_actions['archive'] = __('В архив', 'usam');
			$bulk_actions['publish'] = __('Опубликовать', 'usam');
			$print = true;			
		}	
		if ( $print )
		{
			$bulk_actions['print'] = __('Печать', 'usam');			
			$bulk_actions['barcode_printing'] = __('Печать штрих-кода', 'usam');	
			$bulk_actions['qr_printing'] = __('Печать QR-кода оплаты товара', 'usam');
			$bulk_actions['qr_gotocart_printing'] = __('Печать QR-кода добавления в корзину товара', 'usam');	
		}
		$bulk_actions['category'] = __('Изменить категорию', 'usam');
		$bulk_actions['brand'] = __('Изменить бренд', 'usam');		
		$bulk_actions['category_sale'] = __('Изменить категорию скидок', 'usam');	
		$bulk_actions['product_attribute'] = __('Изменить свойства', 'usam');	
		$bulk_actions['system_product_attribute'] = __('Изменить системные свойства', 'usam');	
		$bulk_actions['product_thumbnail'] = __('Установить миниатюру', 'usam');
		return $bulk_actions;
	}		
	
	/**
	 * Обработка массовых действий
	 */
	function bulk_action_handler( $redirect_to, $action, $post_ids )
	{ 
		global $typenow, $wpdb;
		$type_price = usam_get_manager_type_price();
		switch ( $action ) 
		{					
			case 'publish':
			case 'archive':
			case 'draft':	
			case 'to_order':	// Под заказ			
				$update = 0;
				foreach( $post_ids as $post_id )
				{	
					$update_post = ['ID' => $post_id, 'post_status' => $action];
					$update_status = wp_update_post( $update_post );						
					if ( $update_status >= 1 ) 
						$update++;
				}						
				$key = $action.'_product';
				$redirect_to = add_query_arg( array( $key => $update ), $redirect_to );
			break;						
			case 'print':				
				echo usam_get_printing_forms( 'printing_products', $post_ids );
				exit;
			break;
			case 'barcode_printing':
				echo usam_get_printing_forms( 'barcode_printing', $post_ids );
				exit;
			break;	
			case 'qr_printing':
				echo usam_get_printing_forms( 'qr_products_printing', $post_ids );
				exit;
			break;	
			case 'qr_gotocart_printing':
				echo usam_get_printing_forms( 'qr_gotocart_printing', $post_ids );
				exit;
			break;
		}			
		return remove_query_arg(['action', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status',  'post', 'bulk_edit', 'post_view'], $redirect_to );	
	}
	/**
	 * отображать администратора уведомление на странице сообщений после действия
	 */
	function custom_bulk_admin_notices() 
	{
		global $post_type, $pagenow;
		
		if($pagenow == 'edit.php' && $post_type == 'usam-product')
		{
			$message = '';
			if( isset($_REQUEST['exported']) )
			{
				$exported = absint($_REQUEST['exported']);	
				$message = sprintf( _n( 'Элементы выгружены в Excel.', '%s элемента экспортировано.', $exported, 'usam'), $exported );
			}
			if( isset($_REQUEST['publish_product']) )	
			{
				$publish_product = absint( $_REQUEST['publish_product'] );	
				$message = sprintf( _n( 'Выбранные элементы опубликованы.', '%s элемента опубликовано.', $publish_product , 'usam'), $publish_product );	
			}
			if( isset($_REQUEST['draft_product']) )		
			{
				$draft_product = absint( $_REQUEST['draft_product'] );	
				$message = sprintf( _n( 'Выбранные элементы перемещены в черновик.', '%s элемента перемещено в черновик.', $draft_product, 'usam'), $draft_product );	
			}
			if ( $message != '' )
				echo "<div class=\"updated\"><p>{$message}</p></div>";
		}
	}	
	
	/**
	 * Ограничить на странице продуктов к показу только родительские продукты и убрать вариации.
	 */
	function no_minors_allowed( $vars ) 
	{		
		$vars['post_parent'] = 0;
		return $vars;
	}
	
	//устанавливает порядок сортировки по умолчанию	
	/*
	[draft] => Черновик
	[pending] => На утверждении
	[private] => Личное
	[publish] => Опубликовано
	*/
	function column_sql_orderby( $vars ) 
	{				
		unset($vars['posts_per_archive_page']);		
		unset($vars['fields']);	
		
		$per_page = (int)get_user_option( "edit_usam-product_per_page" );		
		$vars['posts_per_page'] = empty($per_page)?20:$per_page; // Так как товары являются древовидными нужен лимит иначе загрузятся все.
		if ( !isset($_GET['orderby']) )
		{		
			$page_sorting = get_user_option( 'usam_page_sorting' );					
			$screen = get_current_screen();					
			if ( !empty($screen->id) && !empty($page_sorting[$screen->id]) )
			{
				$sorting = explode("-",$page_sorting[$screen->id]);	
				$vars['orderby'] = isset($sorting[0])?$sorting[0]:'ID';
				$vars['order'] = isset($sorting[1])?$sorting[1]:'DESC';
			}		
			else
			{
				$vars['order']   = 'DESC';	
				$vars['orderby'] = 'ID';		
			}			
		}
		return $vars;		
	}
	
	//Менеджер фильтров
	function display_filters( $which ) 
	{		
		if ( !is_singular() && $which == 'top' ) 
		{	
			require_once( USAM_FILE_PATH . "/admin/interface-filters/products_interface_filters.class.php" );
			$interface_filters = new Products_Interface_Filters();					
			?>				
			<div id='post_filters' v-cloak>
				<?php $interface_filters->display( true ); ?>
			</div>
			<?php
		}	
		return $which;
	}		
		
	//Создает поля для различных мета в быстром полях редактирования. Post_id не могут быть доступны здесь, вводят в рамках соответствующих строк, используя JavaScript.
	function quick_edit_boxes( $col_name, $_screen_post_type = null ) 
	{
		// See http://core.trac.wordpress.org/ticket/16392#comment:9
		if ( current_filter() == 'quick_edit_custom_box' && $_screen_post_type == 'edit-tags' )
			return;
		global $post;		
		?>
		<fieldset class="inline-edit-col-left usam-cols">
			<div class="inline-edit-col">
				<div class="inline-edit-group">
		<?php
		switch ( $col_name ) :
			case 'sku' :
				$sku = usam_get_product_meta( $post->ID, 'sku' );
				?>
				<label style="max-width: 85%" class="alignleft">
					<span class="checkbox-title usam-quick-edit"><?php esc_html_e( 'Артикул', 'usam'); ?>: </span>
					<input type="text" name="sku" value ="<?php echo $sku; ?>"/>						
				</label>
				<?php
			break;
			case 'weight' :
				$weight = usam_get_product_weight( $post->ID );
				?>
				<label style="max-width: 85%" class="alignleft">
					<span class="checkbox-title usam-quick-edit"><?php esc_html_e( 'Вес', 'usam'); ?>: </span>
					<input type="text" name="weight" value ="<?php echo $weight; ?>"/>							
				</label>
				<?php
			break;			
		endswitch;
		?>
				 </div>
			</div>
		</fieldset>
		<?php
	}
} 
?>