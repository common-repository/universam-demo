<?php
require_once( USAM_FILE_PATH . '/admin/includes/filter_processing.class.php' );
require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
class USAM_List_Table extends WP_List_Table 
{			
	protected $total_items;
	protected $bulk_actions = true;
	protected $per_page = 20;		
	
	protected $select = array();
	protected $where = array();
	protected $joins = array();	
	protected $search = '';
	protected $orderby = '';
	protected $order = 'desc';
	protected $limit = '';
	protected $prefix = '';	
	protected $pimary_id = 'id'; // Основной столбец с номером
	
	protected $filter = array();	
	protected $filter_box = true;	
	protected $sortable = true;
	protected $views = true;
	protected $standart_button = true;	
	protected $records = array();
	protected $url = array();	
	
	protected $page = '';		
	protected $tab = '';	
	protected $table = '';		
	protected $form = '';	
	protected $view = '';		
	
	protected $id;
	public    $items = [];
	protected $query_vars = [];	
	
	protected $start_date_interval = '';
	protected $end_date_interval = '';
	protected $date_column = 'date_insert';
	
	protected $period = '';
	protected $time_calculation_interval_start;
	protected $time_calculation_interval_end;
	protected $groupby_date = '';
	protected $status = 'all';		
		
	protected $total_amount = 0;	
	protected $results_line = [];
	protected $data_graph = [];	
	
	function __construct( $args = [] )
	{				
		if ( !empty($_REQUEST['s']) )
			$this->search = trim(stripslashes($_REQUEST['s'])); 
		elseif ( !empty($_REQUEST['search']) )
			$this->search = trim(stripslashes($_REQUEST['search'])); 
		
		if ( !empty($_REQUEST['cb']) )
		{
			if ( is_array($_REQUEST['cb'])) 	
				$records = array_values( $_REQUEST['cb'] );			
			else 	
				$records = explode( ',', $_REQUEST['cb'] );	
			foreach ( $records as $record ) 
			{            
			   $this->records[] = sanitize_title( $record );
			}	
		}	
		if ( !empty($args['view']) )
			$this->view = $args['view'];
		elseif ( isset($_REQUEST['view']) )				
			$this->view = sanitize_title($_REQUEST['view']);	
			
		if ( !empty($args['form']) )
			$this->form = $args['form'];
		elseif ( isset($_GET['form']) && $_REQUEST['form'] == 'view' )				
			$this->form = 'view';
					
		if ( !empty($args['id']) )
			$this->id = $args['id'];
		elseif ( isset($_REQUEST['id']) )				
			$this->id = sanitize_title($_REQUEST['id']);			
		if ( !empty($args['plural']) )
			$this->table = $args['plural'];
		elseif ( isset($_REQUEST['table']) )				
			$this->table = sanitize_title($_REQUEST['table']);						
		if ( !empty($args['page']) )
			$this->page = $args['page'];
		elseif ( !empty($_REQUEST['page']) )
			$this->page = sanitize_title($_REQUEST['page']);
		if ( !empty($args['tab']) )
			$this->tab = $args['tab'];
		elseif ( !empty($_REQUEST['tab']) )			
			$this->tab = sanitize_title($_REQUEST['tab']);	
		$this->url = admin_url( 'admin.php' );		
		/*
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
			$_SERVER['REQUEST_URI'] = wp_get_referer();	
*/
		if ( $this->page )
			$this->url  = add_query_arg( array('page' => $this->page ), $this->url  );		
		
		if ( $this->form == 'view' && $this->id )
		{
			$form_name = isset($_REQUEST['form_name']) ? sanitize_title($_REQUEST['form_name']) : $args['form_name'];
			$this->url  = add_query_arg( array('form' => $this->form, 'id' => $this->id, 'form_name' => $form_name ), $this->url  );	
		}
		if ( $this->tab )
			$this->url  = add_query_arg(['tab' => $this->tab], $this->url  );
				
		if ( !empty($this->table) )
			$this->url  = add_query_arg(['table' => $this->table], $this->url  );
		if ( !empty($this->view) )
			$this->url  = add_query_arg(['view' => $this->view], $this->url  );
		
		if ( !empty($_REQUEST['filter_id']) )
		{
			require_once( USAM_FILE_PATH . '/admin/includes/filter.class.php' );
			$id = absint($_REQUEST['filter_id']);	
			$filter = new USAM_Filter( $id );
			$filter_data = $filter->get_data();
			if ( $filter_data )
				$this->filter = $filter_data['setting']; 
		}
		$screen = get_current_screen();			
		if ( empty($screen->id) )
		{			
			$table = $this->table ? $this->table : $this->tab;
			$per_page_option = strtolower("{$this->page}_{$table}_table");	 
			set_current_screen($per_page_option);
			$screen = get_current_screen();	
		}		
		$selected = $this->get_filter_value('status');
		if ( $selected )
		{
			if ( is_array($selected) )
				$this->status = array_map('sanitize_title',$selected);
			else
				$this->status = sanitize_title($selected);
		}
		$this->get_orderby();		
		$per_page = $this->get_items_per_page($screen->id."_page", $this->per_page);	
		$this->set_per_page( $per_page );			
		$args['screen'] = empty($args['screen'])?$screen->id:$args['screen'];		
		$args['ajax'] = true;	
		$args['singular'] = $this->page;
		$args['plural'] = $this->table;	
		parent::__construct( $args );
		$this->set_date_period();
    }
	
	public function ajax_response() 
	{
		$this->prepare_items();

		ob_start();
		if ( !empty( $_REQUEST['no_placeholder'] ) ) {
			$this->display_rows();
		} else {
			$this->display_rows_or_placeholder();
		}	
		$rows = ob_get_clean();
				
		ob_start();
		$this->print_column_headers();
		$headers = ob_get_clean();
		
		ob_start();
		$this->print_column_footer( );
		$footer = ob_get_clean();
	 
		ob_start();
		$this->pagination('top');
		$pagination_top = ob_get_clean();
	 
		ob_start();
		$this->pagination('bottom');
		$pagination_bottom = ob_get_clean();	

		ob_start();
		$this->views();
		$views = ob_get_clean();	
	 
		$response = array( 'rows' => $rows );
		$response['pagination']['top'] = $pagination_top;		
		$response['pagination']['bottom'] = $pagination_bottom;		
		$response['column_headers'] = $headers;
		$response['views'] = $views;
		$response['column_footer'] = $footer;
		$response['data_graph'] = $this->data_graph;
		$response['name_graph'] = $this->get_title_graph();	
		if ( isset($this->_pagination_args['total_items'] ) ) {
			$response['total_items_i18n'] = sprintf(
				_n( '%s item', '%s items', $this->_pagination_args['total_items'] ),
				number_format_i18n( $this->_pagination_args['total_items'] )
			);
		}
		if ( isset($this->_pagination_args['total_pages'] ) ) {
			$response['total_pages']      = $this->_pagination_args['total_pages'];
			$response['total_pages_i18n'] = number_format_i18n( $this->_pagination_args['total_pages'] );
		}					
		return $response;
	}
	
	public function _js_vars() 
	{ 
		$args_get = $this->return_post();
		$args_get = array_merge( $args_get, ['status', 'n', 'view']);	
		$query_vars = array();
		foreach( $args_get as $get)
		{
			if ( isset($_GET[$get]) )				
				$query_vars[$get] = $_GET[$get]; 
		}			
		if ( $this->form == 'view' )				
		{
			$query_vars['form'] = 'view'; 
			$query_vars['id'] = $this->id; 
			$query_vars['table'] = $this->table; 
		}		
		$args = [$this->table => ['class'  => get_class( $this ), 'query_vars'  => $query_vars, 'screen' => ['id' => $this->screen->id, 'base' => $this->screen->base]]];
		printf( "<script>list_args = %s;</script>\n", wp_json_encode( $args ) );
	}
	
	protected function set_date_period() 
	{  
		$this->period = $this->get_filter_value( 'period', $this->period );
		$this->groupby_date = $this->get_filter_value( 'groupby_date', $this->groupby_date );
		switch ( $this->period ) 
		{					
			case 'today':
			case 'yesterday':
			case 'week':	
				if ( $this->groupby_date != 'day'  )
					$this->groupby_date = 'day';
			break;				
		}		
		$f = new Filter_Processing();
		$date_interval = $f->get_date_interval( $this->period );
	
		$this->start_date_interval = $date_interval['from'] == ''?'':get_gmt_from_date(date('Y-m-d H:i:s', $date_interval['from']));					
		$this->end_date_interval = $date_interval['to'] == ''?'':get_gmt_from_date(date("Y-m-d H:i:s", $date_interval['to']));			
		switch ( $this->groupby_date ) 
		{					
			case 'day':	
				$this->time_calculation_interval_start = $date_interval['to']-86400;
				$this->time_calculation_interval_end = $date_interval['from'];	
			break;
			case 'week':	
				if ( $date_interval['to'] )
				{
					$w = date("w", $date_interval['to']);
					switch ( $w ) 
					{					
						case 0:
							$this->time_calculation_interval_start = $date_interval['to'] - 6*86400;							
						break;	
						case 1:		
							$this->time_calculation_interval_start = $date_interval['to'];										
						break;				
						case 2:
						case 3:
						case 4:
						case 5:
						case 6:
							$this->time_calculation_interval_start = $date_interval['to'] - ($w-1)*86400;												
						break;
					}
				}		
				if ( $date_interval['from'] )
				{
					$w = date("w", $date_interval['from']);	
					switch ( $w ) 
					{					
						case 0:					
							$this->time_calculation_interval_end = $date_interval['from'] - 6*86400;						
						break;	
						case 1:								
							$this->time_calculation_interval_end = $date_interval['from'] - 604800;							
						break;				
						case 2:
						case 3:
						case 4:
						case 5:
						case 6:						
							$this->time_calculation_interval_end = $date_interval['from'] - ($w-1)*86400;									
						break;
					}						
				}				
			break;			
			case 'month':				
				$this->time_calculation_interval_start = $date_interval['to'] == ''?'':mktime(0,0,0,date('m',$date_interval['to']),1, date('Y',$date_interval['to']));	
				$this->time_calculation_interval_end = $date_interval['from'] == ''?time():mktime(0,0,0,date('m',$date_interval['from']),1,date('Y',$date_interval['from']));				
			break;
			case 'year':				
				$this->time_calculation_interval_start = $date_interval['to'] == ''?'':mktime(0,0,0,1,1, date('Y',$date_interval['to']));
				$this->time_calculation_interval_end = $date_interval['from'] == ''?time():mktime(0,0,0,1,1,date('Y',$date_interval['from']));		
			break;				
		}		
	}
	
	protected function get_orderby() 
	{			
		if ( !isset($_REQUEST['order']) && !isset($_REQUEST['orderby']) )
		{
			$page_sorting = get_user_option( 'usam_page_sorting' );
			$screen = get_current_screen();					
			if ( !empty($screen->id) && !empty($page_sorting[$screen->id]) )
			{
				$sorting = explode("-",$page_sorting[$screen->id]);				
				$this->orderby = !empty($sorting[0])?$sorting[0]:$this->orderby;
				$this->order = !empty($sorting[1])?$sorting[1]:$this->order;
			}
		}	
		$this->orderby = $this->orderby == '' ? $this->pimary_id : $this->orderby; 		
		if ( isset($_REQUEST['orderby']) )
		{ 
			$order_by = explode(",",$_REQUEST['orderby']);		
			$sortable_columns = (array)$this->get_sortable_columns();		
			$orderby = array();			
			foreach( $order_by as $value )
			{
				foreach( $sortable_columns as $key => $columns )
				{
					if ( is_array($columns) && $value == $columns[0] || $value == $columns )
					{
						$orderby[] = sanitize_title($value);				
						break;
					}
				}
			}
			if ( !empty($orderby) )
				$this->orderby = count($orderby) == 1 ? $orderby[0] : $orderby;
		}		
		if ( $this->prefix != '' )
			$this->orderby = $this->prefix.'.'.$this->orderby;
	
		if ( isset($_REQUEST['order']) )
			$this->order = usam_product_order( $_REQUEST['order'] );		
	}
	
	protected function get_standart_query_parent( )
	{	
		$offset = ($this->get_pagenum() - 1) * $this->per_page;		
		$this->limit = ( $this->per_page !== 0 ) ? "LIMIT {$offset}, {$this->per_page}" : '';		
			
		$prefix = $this->prefix != '' ? $this->prefix.'.':'';
		
		if ( in_array($this->orderby,$this->get_number_columns_sql()))
			$this->orderby = "CAST({$prefix}{$this->orderby} AS SIGNED)";
	
		if ( !empty( $this->records ) )
			$this->where[] = $prefix.$this->pimary_id." IN ('" . implode( "','", $this->records ) . "')";			
		else
			$this->where = array("1 = '1'");			
	}	
	
	// Формирование таблицы
	protected function forming_tables( )
	{	
		$this->get_order();
		$this->_column_headers = $this->get_column_info();
		
		if ( empty($this->items) )
			return;
	
		$current = current($this->items);
		if ( is_object($current) )
			usort( $this->items, array( $this, 'usort_reorder' ) );	
		else
			usort( $this->items, array( $this, 'usort_reorder_array' ) );	
		if ( $this->per_page )
		{ 
			$this->items = array_slice( $this->items, ($this->get_pagenum() - 1)*$this->per_page ,$this->per_page);
			$this->set_pagination_args( array(	'total_items' => $this->total_items, 'per_page' => $this->per_page ) );	
		}
	}	
	
	public function usort_reorder( $a, $b ) 
	{			
		$orderby = $this->orderby;		
		if ( in_array($this->orderby, $this->get_number_columns_sql()))
		{			
			if ( $this->order === 'ASC')		
			{				
				if ( $a->$orderby > $b->$orderby )			
					return 1;				
			}
			else 				
			{				
				if ( $a->$orderby < $b->$orderby )			
					return 1;
			}									
			return 0;			
		}
		else
		{		
			$result = strcmp( $a->$orderby, $b->$orderby );	
			return ( $this->order === 'ASC' ) ? $result : -$result;
		}		
	}
	
	public function usort_reorder_array( $a, $b ) 
	{			
		$orderby = $this->orderby;		
		if ( in_array($this->orderby, $this->get_number_columns_sql()))
		{			
			if ( $this->order === 'ASC')		
			{				
				if ( $a[$orderby] > $b[$orderby] )			
					return 1;				
			}
			else 				
			{				
				if ( $a[$orderby] < $b[$orderby] )			
					return 1;
			}									
			return 0;			
		}
		else
		{	
			$result = strcmp( $a[$orderby], $b[$orderby] );	
			return ( $this->order === 'ASC' ) ? $result : -$result;
		}		
	}
	
	public function get_number_columns_sql()
    {       
		return array();
    }
	
	function logical_column( $logical ) 
	{		
		echo "<span class='".($logical?'item_status_valid':'status_blocked')." item_status'>";
		$logical == 1?_e('Да','usam'):_e('Нет','usam');
		echo '</span>';
	}
	
	function roles_name( $_roles )
	{
		$roles = get_editable_roles();			
		$roles['notloggedin']['name'] = __('Не зарегистрированные','usam');
		$html = [];
		foreach( $_roles as $value )
		{
			if ( isset($roles[$value]) )
				$html[] = translate_user_role( $roles[$value]['name'] );	
		}
		echo implode(', ',$html);
	}
		
	function no_items() 
	{
		_e( 'Ничего не найдено', 'usam');
	}
		
	function column_active( $item ) 
	{		
		if ( is_object($item) )
			$logical = $item->active;	
		else
			$logical = $item['active'];	
		$this->logical_column( $logical );
	}
	
	function column_language( $item ) 
	{		
		if ( is_object($item) )
			$lang = $item->language;	
		else
			$lang = $item['language'];
		
		if ( $lang == '' )
			_e("Все языки","usam");
		else
		{
			$languages = maybe_unserialize(get_site_option('usam_languages'));
			foreach ( $languages as $language )
			{ 
				if ( $language['code'] == $lang )
				{
					echo $language['name'];
					break;
				}
			}
		}
	}
	
	function column_role( $item )
	{
		$_roles = is_object($item) ? $item->roles : $item['roles'];			
		if ( !empty($_roles))
			$this->roles_name( $_roles );
	}
	
	function column_interval( $item )
    {		
		if ( is_object($item) )
			$start_date = $item->start_date;	
		else			
			$start_date = $item['start_date'];			
		$end_date = is_object($item) ? $item->end_date : $item['end_date'];	

		$str = '';
		if ( !empty($start_date) )	
			$str =  __('с', 'usam').' <strong>'.usam_local_date($start_date).'</strong>';		
			
		if ( !empty($end_date) )	
		{
			if ( $str != '' )
				$str .= ' <br> ';
			$str .= __('по', 'usam').' <strong>'.usam_local_date($end_date).'</strong>';	
		}			
		if ( $str )
		{
			$date = date("Y-m-d H:i:s");
			if ( (empty($start_date) || $start_date <= $date) && (empty($end_date) || $end_date >= $date) )
				$str = "<span class='item_status_valid item_status'>$str</span>";
			elseif( !empty($end_date) && $end_date <= $date )
				$str = "<span class='item_status_attention item_status'>$str</span>";
			elseif( !empty($start_date) && $start_date >= $date )
				$str = "<span class='item_status_notcomplete item_status'>$str</span>";
			echo $str;	
		}
    } 
	
	function column_color( $item )
	{		
		if ( !empty($item->color) )
		{
			$style = $item->color != ''?'style="background:'.$item->color.'"':'';
			echo "<span class ='usam_color_view' $style></span>";
		}
	}
	
	function column_type_prices( $item ) 
    {		
		$prices = usam_get_prices( );	
		$i = 0;
		foreach ( $prices as $price )
		{ 
			if ( in_array($price['code'], $item['type_prices']) )
			{			
				if ( $i > 0 )
					echo '<hr size="1" width="90%">';
				echo $price['title'].' ( '.$price['code'].' )';				
				$i++;
			}
		}
	}
	
	function column_last_comment( $item )
	{			
		if ( $item->last_comment )
		{
			echo "<div class='user_block user_comment'>";
			echo "<div class='image_container usam_foto'><img src='".esc_url( usam_get_contact_foto( $item->last_comment_user, 'user_id' ) )."'></div>";
			echo "<div class='user_block__content'><div class='user_comment__user'><span class='user_comment__user_name'>".usam_get_manager_name($item->last_comment_user)."</span><span class='user_comment__date'>".usam_local_formatted_date( $item->last_comment_date )."</span></div><div class='user_comment__message'>".nl2br(esc_html(usam_limit_words($item->last_comment)))."</div></div>";
			echo "</div>";
		}
	}
	
	function column_contact( $item ) 
	{				
		static $social_networks = null;
		if ( is_object($item) )
		{
			$contact_id = $item->contact_id;			
		}
		else
		{
			$contact_id = $item['contact_id'];			
		}
		if ( $contact_id )
		{					
			$contact = usam_get_contact( $contact_id );		
			if ( !empty($contact) )
			{
				$emails = usam_get_contact_emails( $contact_id );	
				$phones = usam_get_contact_phones( $contact_id );					
				$actions = array( );	
				$link = '';
				if ( !empty($emails) )
					$link .= "<a class='js-open-message-send' data-emails='".implode(',',$emails)."' data-name='".$contact['appeal']."' title='".__("Отправить письмо","usam")."'>".usam_get_icon( 'email', 20 )."</a>";
				if ( !empty($phones) )
				{
					$link .= "<a class='js-open-sms-send' data-phones='".implode(',',$phones)."' data-name='".$contact['appeal']."' title='".__("Отправить СМС","usam")."'>".usam_get_icon( 'sms', 20 )."</a>";
					$link .= "<a class='js-communication-phone' data-phones='".implode(',',$phones)."' data-name='".$contact['appeal']."' title='".__("Позвонить","usam")."'>".usam_get_icon( 'phone', 20 )."</a>";
				}					
				$url = $this->get_nonce_url(add_query_arg( array( 'page' => 'feedback', 'tab' => 'chat', 'contact_id' => $contact_id, 'action' => 'open_dialog' ), admin_url("admin.php") ));				
				$link .= "<a class='' href='$url' title='".__("Начать чат","usam")."'>".usam_get_icon( 'chat', 20 )."</a>";
				if ( $social_networks === null )
					$social_networks = usam_get_social_network_profiles();
				foreach( $social_networks as $channel )
				{			
					$user_id = usam_get_contact_metadata( $contact_id, $channel->type_social.'_user_id' );
					if ( !empty($user_id) )
					{
						$url = add_query_arg( array( 'channel' => $channel->type_social, 'channel_id' => $channel->id ), $url );	
						$link .= "<a class='' href='$url' title='".__("Начать чат","usam")."'>".usam_get_icon( $channel->type_social, 20 )."</a>";				
					}
				}
				$actions['usam-link'] = $link;
				$url = usam_get_contact_url( $contact_id );			
				echo "<div class='user_block'>";
				echo "<a href='$url' class='image_container usam_foto'><img src='".esc_url( usam_get_contact_foto( $contact_id ) )."'></a>";
				echo "<div class='user_block__name'>";
				$this->row_actions_table( "<a href='$url' class='js-object-value'>".$contact['appeal']. "</a>", $actions );
				echo "</div></div>";	
			}
		}
	}	
			
	function column_manager( $item ) 
	{		
		if ( is_object($item) )
		{
			$id = $item->manager_id;			
		}
		else
		{
			$id = $item['manager_id'];			
		}
		if ( $id )
		{		
			$url = add_query_arg( array( 'manager' => $id, 'page' => $this->page, 'tab' => $this->tab ), wp_get_referer() );	
			?> 
			<a href="<?php echo $url; ?>"><?php echo usam_get_manager_name( $id ); ?></a>		
			<?php	
		}
	}
	
	function column_user( $item ) 
	{		
		$user_id = is_object($item) ? $item->user_id : $item['user_id'];
		if ( $user_id )
			$this->display_user( $user_id );
	}
	
	function column_customer( $item ) 
	{		
		$user_id = is_object($item) ? $item->user_id : $item['user_id'];
		
		if ( $user_id )
			$this->display_user( $user_id );
		else
			_e('Гость','usam');
	}
	
	function display_user( $user_id ) 
	{	
		?>			
		<div class='user_block'>
			<div class="image_container usam_foto"><img src='<?php echo usam_get_contact_foto($user_id, 'user_id'); ?>'></div>
			<div class='user_block__name'><a href="<?php echo usam_get_contact_url($user_id, 'user_id'); ?>"><?php echo usam_get_customer_name($user_id); ?></a></div>	
		</div>
		<?php
	}
		
	function column_date( $item )
    {			
		if ( is_object($item) )
			$date = $item->date_insert;
		elseif ( is_array($item) )
			$date = $item['date_insert'];
		if ( !empty($date) )
			echo usam_local_formatted_date( $date );
	}
	
	function column_date_update( $item )
    {		
		if ( is_object($item) )
			echo usam_local_date( $item->date_update );
		elseif ( is_array($item) )
			echo usam_local_date( $item['date_update'] );
	}
	
	function column_size( $item )
	{	
		echo size_format($item->size );
	}
	
	protected function column_default( $item, $column_name ) 
	{		
		if ( is_object($item) && isset($item->$column_name) )
			$output = stripcslashes($item->$column_name);
		elseif ( is_array($item) && isset($item[$column_name]) )
			$output = stripcslashes($item[$column_name]);
		else
			$output = '';
		return $output;
	}
	
	public function extra_tablenav_display( $which ) {	}
	
	public function extra_tablenav( $which ) 
	{		
		if ( $this->bulk_actions )
			$this->extra_tablenav_display( $which );
	}	
	// Массовые действия
	function get_bulk_actions_display() 
	{
		return array();
	}
	// Массовые действия
	function get_bulk_actions() 
	{ 
		if ( $this->bulk_actions && $this->form != 'view' )
			$bulk_actions = $this->get_bulk_actions_display();
		else
			$bulk_actions = array();	
		return $bulk_actions;
	}
	
	protected function format_description( $description ) 
	{		
		return "<span class='item_description'>".nl2br(esc_html( usam_limit_words($description)))."</span>";
	}
		
	protected function column_cb( $item ) 
	{			
		$column = $this->pimary_id;
		if ( is_object($item) )
		{
			$checked = in_array($item->$column, $this->records )?"checked='checked'":""; 
			echo "<input id='checkbox-".$item->$column."' type='checkbox' name='cb[]' value='".$item->$column."' ".$checked."/>";	
		}
		else
		{ 
			$checked = in_array($item[$column], $this->records )?"checked='checked'":"";
			echo "<input id='checkbox-".$item[$column]."' type='checkbox' name='cb[]' value='".$item[$column]."' ".$checked."/>";				
		}
    }	
	
	public function column_totalprice( $item ) 
	{
		$totalprice = is_object($item) ? $item->totalprice : $item['totalprice'];
		echo usam_currency_display( $totalprice );
	}
	
	protected function column_description( $item ) 
	{		
		$description = is_object($item) ? $item->description : $item['description'];	
		echo $this->format_description( $description );
	}	
	
	function column_counterparty( $item ) 
    {
		if ( $item->customer_id ) 
		{
			if ( $item->customer_type == 'company' )
			{
				$company = usam_get_company( $item->customer_id ); 				
				if ( !empty($company) )
				{ 
					$title = !empty($company['name'])?$company['name']:'';
					echo "<a href='".usam_get_company_url( $company['id'] )."'>{$title}</a>";	
				}
			}
			else
			{
				$contact = usam_get_contact( $item->customer_id ); 
				if ( !empty($contact) )
				{
					$title = !empty($contact['appeal'])?$contact['appeal']:'';
					echo "<a href='".usam_get_contact_url( $contact['id'] )."'>{$title}</a>";	
				}
			}
		}
	}	
	
	function column_drag( $item )
	{		
		$pimary_id = $this->pimary_id;
		if ( is_object($item) )
		{
			$id = $item->$pimary_id;
		}
		else
		{ 
			$id = $item[$pimary_id];			
		}
		echo usam_get_icon('drag', 20, ['data-id' => $id, 'title' => __("Сортировка","usam")]);			
	}

	function row_actions_table( $value, $actions ) 
	{		
		if ( $this->bulk_actions )
			echo sprintf('%1$s %2$s', $value, $this->row_actions( $actions ) );
		else
			echo $value;		
	}	
	
	protected function item_view( $id, $text, $form_name )
	{			
		return '<a class="row-title" href="'.esc_url( add_query_arg( array('form' => 'view', 'form_name' => $form_name, 'id' => $id), $this->url ) ).'">'.$text.'</a>';
	}
	
	protected function item_edit( $id, $text, $form_name )
	{			
		return '<a class="row-title" href="'.esc_url( add_query_arg( array('form' => 'edit', 'form_name' => $form_name, 'id' => $id), $this->url ) ).'">'.$text.'</a>';
	}
	
	function standart_row_actions( $id, $form_name = '', $actions = array() ) 
	{				
		if ( is_array($id) )
		{
			$actions = $id;
			$id = false;
		}
		if ( $form_name && $id !== false )
			$new_actions['edit'] = '<a class="usam-edit-link" href="'.add_query_arg(['form' => 'edit', 'form_name' => $form_name, 'id' => $id], $this->url ).'">'.__('Изменить', 'usam').'</a>';
		foreach( $actions as $action => $title )
		{
			$new_actions[$action] = "<a class='js-table-action-link' data-action='{$action}' href='#'>{$title}</a>";			
		}
		if ( !isset($new_actions['delete']) && $id !== false )
			$new_actions['delete'] = '<a class="usam-delete-link" href="'.$this->get_nonce_url( add_query_arg(['action' => 'delete', 'cb' => $id], $this->url ) ).'">'.__('Удалить', 'usam').'</a>';	
		return $new_actions;
	}
		
	protected function get_nonce_url( $url )
	{
		$screen = get_current_screen();			
		if ( !empty($screen->id) )
			return wp_nonce_url( $url, 'usam-'.$screen->id);		
		else
			return $url;
	}
	
	protected function item_url( $id ) 
	{		
		$url = add_query_arg( array('id' => $id ), $this->url );		
		$url = $this->get_nonce_url($url);
		return $url;
	}

	public function disable_sortable() {
		$this->sortable = false;
	}
	
	public function disable_standart_button() {
		$this->standart_button = false;
	}
	
	public function disable_filter_box() {
		$this->filter_box = false;
	}	

	public function disable_bulk_actions() {
		$this->bulk_actions = false;
	}

	public function disable_views() {
		$this->views = false;
	}

	public function set_per_page( $per_page )
	{			
		$this->per_page = (int) $per_page;		
	}
	
	public function is_pagination_enabled() {
		return $this->per_page !== 0;
	}

	public function is_sortable() {
		return $this->sortable;
	}

	public function is_views_enabled() {
		return $this->views;
	}
	
	public function get_per_page(  )
	{				
		return $this->per_page;
	}		
	
	protected function get_query_vars() 
	{	
		$this->query_vars = ['order' => $this->order, 'orderby' => $this->orderby, 'date_query' => [], 'conditions' => [], 'meta_query' => [], 'search' => ''];		
		if ( $this->per_page )
		{
			$this->query_vars['paged'] = $this->get_pagenum();	
			$this->query_vars['number'] = $this->per_page;	
		}
		if ( !empty($this->records) )
			$this->query_vars['include'] = $this->records;	
		else
		{
			if ( $this->search != ''  )
				$this->query_vars['search'] = $this->search;	
			$selected = $this->get_filter_value( 'date_group' );
			$date_column = $selected ? 'date_'.$selected : $this->date_column;				
			if ( $date_column != '' )
			{
				if ( $this->start_date_interval )
					$this->query_vars['date_query'][] = ['after' => $this->start_date_interval, 'inclusive' => true, 'column' => $date_column];	
				if ( $this->end_date_interval )
					$this->query_vars['date_query'][] = ['before' => $this->end_date_interval, 'inclusive' => true, 'column' => $date_column];					
				$selected = $this->get_filter_value( 'weekday' );
				if ( !empty($selected) )	
				{						
					$weekday = array_map('intval', (array)$selected);
					$this->query_vars['date_query'][] = ['dayofweek' => $weekday, 'compare' => 'IN', 'column' => $date_column];		
				}
			}				
			$selected = $this->get_filter_value( 'status' );
			if ( $selected )
			{
				if ( is_array($selected) )
					$this->query_vars['status'] = array_map('sanitize_title',$selected);
				else
					$this->query_vars['status'] = sanitize_title($selected);
			}
		}			
		return $this->query_vars;
	}
	
	protected function get_views_query_vars() 
	{
		$views_query_vars = $this->query_vars;
		
		$views_query_vars['fields'] = ['status', 'count'];
		$views_query_vars['groupby'] = 'status';
		if( isset($views_query_vars['status']) )
			unset($views_query_vars['status']);	
		if( isset($views_query_vars['status__not_in']) )
			unset($views_query_vars['status__not_in']);			
		if( isset($views_query_vars['paged']) )
			unset($views_query_vars['paged']);
		if( isset($views_query_vars['number']) )
			unset($views_query_vars['number']);	
		if( isset($views_query_vars['date_query']) )
			unset($views_query_vars['date_query']);	
		if( isset($views_query_vars['conditions']) )
			unset($views_query_vars['conditions']);
		if( isset($views_query_vars['cache_meta']) )
			unset($views_query_vars['cache_meta']);
		if( isset($views_query_vars['add_fields']) )
			unset($views_query_vars['add_fields']);
		return $views_query_vars;		
	}	
	
	public function standart_button(  ) 
	{
		if ( $this->standart_button )
		{
			?>		
			<div class="table_buttons actions">
				<?php 
				$this->print_button();	
				$this->excel_button();	
				?>
			</div>
			<?php 				
		}
	}
	
	public function print_button(  ) 
	{
		?>		
		<div class="table_buttons__button">
			<button class = "button button-export" @click="printTable"><?php _e( 'Распечатать', 'usam'); ?></button>
		</div>
		<?php 
	}
	
	public function excel_button(  ) 
	{	
		?>		
		<div class="table_buttons__button">
			<button class = "button button-export" @click="exportTable"><?php _e( 'Excel', 'usam'); ?></button>
		</div>
		<?php 
	}
		
	public function return_post()
	{
		return [];
	}	
	
	public function get_table_block_classes()
    {
		return [];
	}
		
	public function display_table()
    {
		$this->prepare_items();
		do_action( 'usam_form_display_table_before' );		
		$_SERVER['REQUEST_URI'] = remove_query_arg(['_wp_http_referer', 'cb'], $_SERVER['REQUEST_URI'] );		
		?>				
		<div class='usam_tab_table <?php echo implode(" ",$this->get_table_block_classes() ); ?>'>
			<?php
			if ( $this->is_views_enabled() )
				$this->views();
			?>				
			<div class='interface_filters' v-cloak>
				<?php $this->display_interface_filters( ); ?>
			</div>			
			<input type='hidden' value='<?php echo $this->table; ?>' class='js-table-name' ref="table_name">	
			<input type='hidden' value='<?php echo $this->order; ?>' class='js-table-order'>
			<input type='hidden' value='<?php echo is_array($this->orderby)?implode(",",$this->orderby):$this->orderby; ?>' class='js-table-orderby'>			
			<?php do_action( 'usam_list_table_before' ); ?>
			<div class='usam_list_table_wrapper'><?php $this->display(); ?></div>
			<?php do_action( 'usam_list_table_after' );	?>		
		</div>
		<?php		
		$this->set_data_graph( $this->data_graph, $this->get_title_graph() );	
	}
	
	public function display() 
	{
		$singular = $this->_args['singular'];
		$this->display_tablenav( 'top' );
		$this->screen->render_screen_reader_content( 'heading_list' );
		?>
		<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
			<thead>
			<tr>
				<?php $this->print_column_headers(); ?>
			</tr>
			</thead>
			<tbody id="the-list" <?php if ( $singular ) {	echo " data-wp-lists='list:$singular'";	} ?> >
				<?php $this->display_rows_or_placeholder(); ?>
			</tbody>	
			<tfoot>
				<tr <?php echo !empty($this->results_line)?'class="results_line"':''; ?>>
					<?php $this->print_column_footer( ); ?>
				</tr>
			</tfoot>
		</table>
		<?php
		$this->display_tablenav( 'bottom' );
	}
	
	public function print_column_footer() 
	{	
		if ( empty($this->results_line) )
		{
			$this->print_column_headers(false);
		}
		else
		{
			list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();				
			foreach ( $columns as $column_key => $column_display_name ) 
			{
				$class = ['manage-column', "column-$column_key"];
				if ( in_array( $column_key, $hidden ) ) {
					$class[] = 'hidden';
				}
				if ( $column_key === $primary ) {
					$class[] = 'column-primary';
				}						
				$tag   = ( 'cb' === $column_key ) ? 'td' : 'th';
				$scope = ( 'th' === $tag ) ? 'scope="col"' : '';

				if ( !empty( $class ) ) {
					$class = "class='" . join( ' ', $class ) . "'";
				}			
				$display_name = isset($this->results_line[$column_key])?$this->results_line[$column_key]:'';			
				if ( is_numeric($display_name) )
					$display_name = $this->currency_display( $display_name );	
				echo "<$tag $scope $class>$display_name</$tag>";
			}	
		}
	}
	
	function currency_display( $price ) 
	{
		$format_price = ['currency_symbol' => false, 'decimal_point' => false, 'currency_code' => false];
		$result = explode('.', $price);		
		$format_price['decimal_point'] = isset($result[1]) ? true : false;	
		return usam_currency_display( $price, $format_price );		
	}

	protected function set_data_graph( $data_graph, $name_graph = '' ) 
	{  
		if ( !empty($this->data_graph) )
		{	
			$anonymous_function = function() use ($data_graph, $name_graph) { 
				?>
				<script>	
				var usam_data_graph = <?php echo json_encode( $data_graph ); ?>;
				var usam_name_graph = '<?php echo $name_graph; ?>';	 
				</script>
				<?php
				return true; 
			};	
			add_action('admin_footer', $anonymous_function, 10, 2 );		
		}
	}
	
	protected function get_title_graph( ) 
	{
		return '';
	}
		
	protected function get_filter_tablenav( ) 
	{
		return [];
	}
	
	public function get_class_interface_filters( $codes = [] ) 
	{ 
		if ( !$codes )
			$codes = [$this->table];
		require_once( USAM_FILE_PATH . '/admin/includes/interface_filters.class.php' );
		$class = "USAM_Interface_Filters";
		foreach( $codes as $code ) 
		{
			if( $code )
			{ 
				$file = USAM_FILE_PATH . "/admin/interface-filters/{$code}_interface_filters.class.php";	
				if( file_exists($file) )
				{ 
					require_once( $file );
					$class = "{$code}_Interface_Filters";	
					break;		
				}
				
			}
		}
		$filters = $this->get_filter_tablenav();
		$filters['period'] = $this->period;
		$filters['groupby_date'] = $this->groupby_date;
		$filters['start_date_interval'] = $this->start_date_interval;
		$filters['end_date_interval'] = $this->end_date_interval;		
		$interface_filters = new $class( $filters );
		return $interface_filters;		
	}
	
	public function display_interface_filters(  ) 
	{ 
		if( $this->filter_box )
		{			
			$interface_filters = $this->get_class_interface_filters(); 			
			$filters = $this->get_filter_tablenav();
			$interface_filters->display( isset($filters['interval']) );
		}
	}
		
	protected function get_filter_value( $key, $default = null ) 
	{		
		$filters = $this->get_filter_tablenav();
		if( !defined('DOING_AJAX') || !DOING_AJAX )
		{
			if ( isset($filters[$key]) )
				$default = $filters[$key];
		}
		if ( !empty($this->filter[$key]) )
			$select = $this->filter[$key]; 
		elseif ( isset($_REQUEST[$key]) )
		{
			if ( is_array($_REQUEST[$key]) )
				$select = stripslashes_deep($_REQUEST[$key]);
			elseif ( stripos($_REQUEST[$key], ',') !== false )
				$select = explode(',',stripslashes($_REQUEST[$key]));
			else
				$select = stripslashes($_REQUEST[$key]);				
		}
		else
			$select = $default;
		return $select;
	}
	
	protected function get_digital_interval_for_query( $columns_search, $key = 'conditions' )
	{				
		if ( !isset($this->query_vars[$key]) )
			$this->query_vars[$key] = [];
		
		$f = new Filter_Processing();
		$this->query_vars[$key] = array_merge($this->query_vars[$key], $f->get_digital_interval_for_query( $columns_search ) );
	}
	
	protected function get_date_interval_for_query( $columns_search )
	{			
		if ( !isset($this->query_vars['date_query']) )
			$this->query_vars['date_query'] = [];
		
		$f = new Filter_Processing();
		$this->query_vars['date_query'] = array_merge($this->query_vars['date_query'], $f->get_date_interval_for_query( $columns_search ) );
	}
		
	protected function get_string_for_query( $columns_search, $key = 'conditions' )
	{			
		$f = new Filter_Processing();
		$this->query_vars[$key] = array_merge($this->query_vars[$key], $f->get_string_for_query( $columns_search ) );
	}
	
	protected function get_meta_for_query( $type, $key = 'meta_query' )
	{			
		if ( !isset($this->query_vars[$key]) )
			$this->query_vars[$key] = [];		
		
		$f = new Filter_Processing();
		$this->query_vars[$key] = array_merge($this->query_vars[$key], $f->get_meta_for_query( $type, $this->query_vars[$key] ) );
	}	
}
?>