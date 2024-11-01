<?php
abstract class USAM_Page_Tab
{		
	protected $page_name = '';
	protected $tab;
	protected $list_table = null;		
	protected $table = null;
	protected $section = null;	
	protected $views = ['table'];
	protected $view = '';
	protected $sendback = [];	
	protected $vue = false;	
	protected $json = false;		
 
	protected  $current_action = '';
	protected  $redirect   = false;
	protected  $item_table;
	protected  $per_page = 20;	
	protected  $message_error = '';
	protected  $display_save_button = false;
	protected  $blank_slate = false;		
		
	public function __construct( ) { }		
	protected function load_tab(){ }	
	protected function action_processing(){ }	
	public function display( ) { }		
	
	public function add_help_center_tabs() 
	{		
		return [];
	}		
	
	public function load( $page, $tab ) 
	{	
		$this->page_name = $page;
		$this->tab       = $tab;	
		if( !USAM_DEBUG_THEME )
			$this->views = array_diff($this->views, ["map"]);		
		if( $this->views )
		{			
			$screens = $this->get_view_titles();
			foreach( $this->views as $k => $id )
			{		
			
				if( $screens[$id]['capability'] && !current_user_can($screens[$id]['capability']) )
					unset($this->views[$k]);
			}
			$view_tabs = get_user_option( 'usam_view_tabs' );		
			if ( isset($view_tabs[$this->tab]) && in_array($view_tabs[$this->tab], $this->views) ) 	
				$view = $view_tabs[$this->tab];		
			else
				$view = $this->views[0];		
			if( !empty($_REQUEST['view']) && $view != $_REQUEST['view'] && $_REQUEST['view'] != 'form' )
			{						
				$v = sanitize_title($_REQUEST['view']);	
				if( in_array($v, $this->views)  ) 
				{
					$view = $v;
					if( count($this->views) > 1 && $v != 'settings' ) 
					{
						if ( !is_array($view_tabs) )
							$view_tabs = array();
						$view_tabs[$this->tab] = $view;
						$user_id = get_current_user_id();
						update_user_option( $user_id, 'usam_view_tabs', $view_tabs );
					}
				}	
			}
			if( !empty($view) && in_array($view, $this->views) )
				$this->view = $view;		
			elseif( $view == 'settings' )
				$this->view = 'settings';				
			if( !empty($_GET['form_name']) )
				$this->view = 'form';
			elseif ( $this->view != 'grid' && $this->view != 'report' )
			{
				if ( !empty($_GET['table']) )
				{ 
					$this->table = sanitize_title($_GET['table']);	
					if ( !isset($_REQUEST['view']) )
						$this->view = 'table';					
				}	
				elseif ( $this->view == 'table' )			
				{ 	
					$tables = $this->get_available_tab_sections();
					$this->table = !empty($tables)?key($tables):$this->tab;
				} 	
			}		
			$this->submit_url();
			$method = $this->view.'_view';
			if ( !method_exists($this, $method) )
				$this->view = $this->views[0];			
			if ( $this->view == 'settings' || $this->view == 'simple' )
			{ 
				if ( !$this->table )
				{
					if ( $this->view == 'settings' )
						$tabs = $this->get_settings_tabs();
					else
						$tabs = $this->get_tab_sections();	
					if ( $tabs )
					{
						if ( !empty($_REQUEST['section']) )
						{
							$section = sanitize_title($_REQUEST['section']);
							if ( isset($tabs[$section]) )
								$this->section = $section;
						}						
						if ( !$this->section )
						{
							$section = key($tabs);
							$current_tab = current($tabs);
							if ( $current_tab['type'] == 'table' )
								$this->table = $section;
							else
								$this->section = $section;	
						}
					}
				}				
			}
			if ( $this->view == 'settings' )
				$this->display_save_button = true;
			$this->add_screen_option();			
			if ( $this->table )			
				$this->list_table();
		}
		$this->load_tab();	
		$this->process_bulk_action(); // менять с load_tab нельзя	
		$this->set_help_tabs( );			
		if ( !USAM_DEBUG_THEME )
			$this->views = array_diff($this->views, ["map"]);
		do_action( 'usam_page_load', $this->page_name, $this->tab );
			
		add_action( 'admin_enqueue_scripts', [$this, 'include_js'], 1 );			
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		add_action( 'admin_footer', [$this, 'footer_include_js'] );			
		add_action('admin_notices',   array($this, 'custom_bulk_admin_notices') );			
	//	add_filter('screen_settings', array( $this, 'screen_settings1'), 100, 2  );		
	}	
	
	public function add_to_footer() 
	{
		return;
	}	
	
	public function screen_settings1() 
	{
		return 'iiiiiiiiiiiiiiiiiiiiiiii';
	}	
	
	public function add_screen_option() 
	{ 
		if( !empty($_GET['table']) )
			$table = sanitize_title($_GET['table']);
		else
			$table = $this->tab;
		$option = strtolower("{$this->page_name}_{$table}_{$this->view}");			
		set_current_screen($option);
	}
	
	public function get_video_url() 
	{
	//	return $this->get_argument( 'video_url' );
	}
	
	public function vue() 
	{
		return false;
	}
	
	protected function nonce_field()
	{
		$screen = get_current_screen();	
		wp_nonce_field('usam-'.$screen->id,'usam_name_nonce_field');		
	}	
			
	// Вывести центр помощи
	public function display_help_center() 
	{		
		require_once( USAM_FILE_PATH . '/admin/includes/help_center/help_center.class.php' );
		$help_center = new USAM_Help_Center( $this->page_name, $this );
		$help_center->output_help_center();
	}	
	
	protected function get_view_titles()
	{		
		return [
			'table' => ['title' => __('Таблицей', 'usam'), 'capability' => 'list_crm'], 
			'grid' => ['title' => __('Плиткой', 'usam'), 'capability' => 'grid_crm'], 
			'info' => ['title' => __('Информация', 'usam'), 'capability' => ''], 
			'map' => ['title' => __('Карта', 'usam'), 'capability' => 'map_crm'], 
			'report' => ['title' => __('Отчеты', 'usam'), 'capability' => 'report_crm'], 
			'calendar' => ['title' => __('Календарь', 'usam'), 'capability' => 'calendar_crm'], 
			'settings' => ['title' => __('Настройки', 'usam'), 'capability' => 'setting_crm'], 
			'simple' => ['title' => __('Страница', 'usam'), 'capability' => ''],
		];
	}
		
	protected function get_tab_forms()
	{
		return [];
	}
	
	public function get_button_url( ) 
	{ 
		$url = add_query_arg(['page' => $this->page_name, 'tab' => $this->tab, 'view' => $this->view], admin_url( 'admin.php' ));		
		if ( $this->table )
			$url = add_query_arg(['table' => $this->table], $url );	
		if ( isset($_REQUEST['n']) )
			$url = add_query_arg(['n' => $_REQUEST['n']], $url );	
		return $url;
	}
	
	public function display_connect_service( $title, $buttons ) 
	{
		?>
		<div class="blank_state">
			<h2 class="title blank_state__message blank_state__icon"><?php echo $title; ?></h2>
			<div class="blank_state__buttons">
				<?php
				foreach ( $buttons as $key => $button )
				{											
					?><a href="<?php echo $button['url']; ?>" class="button" target="_blank"><?php echo $button['title']; ?></a><?php 
				}			
				?>			
			</div>
		</div>
		<?php
	}
	
	public function display_title() 
	{	
		$title_tab = $this->get_title_tab( );				
		if ( !empty($title_tab) )
		{		
			$screen = get_current_screen();		
			$view_tabs = get_user_option( 'usam_favorite_pages' );		
			if ( !empty($view_tabs[$screen->id]) ) 	
				$favorite = 'filled';		
			else
				$favorite = 'empty';
			?><h2 class="tab_title">
				<div class='tab_title_left'>
					<span class='tab_title_text'>
						<span class='dashicons dashicons-star-<?php echo $favorite; ?> js-add-favorites-page' title='<?php _e('Добавить в избранное','usam') ?>'></span>
						<span class='js-tab-title'><?php echo $title_tab; ?></span>
					</span>
				<?php
				$buttons = $this->get_tab_forms( );	
				$buttons = apply_filters( 'usam_page_tab_buttons', $buttons, $this->page_name, $this->tab );
				if ( !empty($buttons) )
				{
					$url = $this->get_button_url( );
					echo "<span class='tab_title_buttons'>";	
					foreach ( $buttons as $key => $button )
					{											
						if ( isset($button['capability']) && !current_user_can($button['capability']) )
							continue;
						
						if ( isset($button['form']) )
						{
							?><a href="<?php echo add_query_arg(['form' => $button['form'], 'form_name' => $button['form_name']], $url); ?>" id="button-<?php echo $key; ?>" class="button"><?php echo $button['title']; ?></a><?php 
						}
						elseif ( isset($button['action']) )
						{
							?><a href="#" id="button-<?php echo $button['action']; ?>" data-action="<?php echo $button['action']; ?>" class="js-action button"><?php echo $button['title']; ?></a><?php 
						}
						elseif ( isset($button['button']) )
						{ //Для VUE
							?><a href="#" id="button-<?php echo $button['button']; ?>" class="button"><?php echo $button['title']; ?></a><?php 
						}
						elseif ( isset($button['table']) )
						{
							?><a href="<?php echo add_query_arg(['table' => $button['table']], $url ); ?>" id="button-<?php echo $key; ?>" class="button"><?php echo $button['title']; ?></a><?php 
						}					
					}	
					?></span><?php 
				}
				?></div><?php 
				$this->display_screen_select();	
			?></h2><?php
			$this->display_paid(); 
		}
	}
	
	// Отображение текущей вкладки
	public function display_tab() 
	{	
		if ( $this->view )
		{
			if ( $this->view != 'form' )
			{
				$this->display_title();	
				$this->display_help_center();	
			}		
			$method = $this->view.'_view';		
			if ( $this->table )		
				$class = "table_view";
			else
				$class = $this->view."_view";			
			?>
			<div id="tab_<?php echo esc_attr( $this->tab ); ?>_content" class="<?php echo $class; ?> <?php echo esc_attr( $this->tab ).'_'.$this->view; ?>_view" <?php echo in_array($this->view, ['report', 'map']) || $this->vue?'v-cloak':''; ?>>	
				<?php $this->$method(); ?>
			</div>
			<?php
		}		
	}	
	
	protected function table_view()
	{
		$this->display_tab_sections();	
		if ( $this->list_table )
			$this->list_table->display_table();				
	}
	
	protected function grid_view()
	{
		$file = usam_get_admin_template_file_path( "{$this->tab}_grid_view.class", 'grid-view' );	
		if ( file_exists($file) )
		{ 
			require_once( $file );
			$class = "USAM_{$this->tab}_Grid_View";
			$grid = new $class( $this->tab );
			$grid->display();		
		}
	}	
	
	protected function report_view()
	{				
		$file = usam_get_admin_template_file_path( "{$this->tab}_reports_view.class", 'reports-view' );	
		if ( file_exists($file) )
		{  
			require_once( $file );
			$class = "USAM_{$this->tab}_Reports_View";
			$report = new $class(['tab' => $this->tab]);		
			$report->display();
		}
	}
	
	protected function form_view()
	{				
		if ( isset($_REQUEST['form']) )
		{ 
			$form_type = sanitize_title($_REQUEST['form']);			
			$form_name = isset($_REQUEST['form_name'])?sanitize_title($_REQUEST['form_name']):$this->table;					
			$file = usam_get_admin_template_file_path( "{$form_type}-form-{$form_name}", 'form' );	
			if ( file_exists($file) )
			{		
				$name_class_form = "USAM_Form_{$form_name}";
				require_once( $file ); 						
				$class_form = new $name_class_form(['table' => $this->table]);
				$class_form->display();
			} 			
		}
	}	
	
	protected function simple_view()
	{
		$this->display_tab_sections();
		if ( $this->table )
			$this->list_table->display_table();			
		elseif ( $this->section && method_exists($this, "display_section_".$this->section) )
			$this->display_section();	
		else
		{
			if ( $this->display_save_button )
			{ 			
				?>
				<form method='POST' action='' id='usam-tab_form'>
					<?php 
					$this->nonce_field();
					$this->display(); 
					?>
					<div class="tab_buttons">
						<?php submit_button( __('Сохранить', 'usam'), 'primary', '', false, array( 'id' => 'action-submit' ) );	?>
						<input type="hidden" name="update" value="1" />										
					</div>
				</form>
				<?php
			}
			elseif ( $this->vue )
			{ 	
				$this->display(); 
				?>
				<div class="tab_buttons">
					<button type="button" class="button button-primary" @click="saveForm(false)"><?php _e('Сохранить', 'usam'); ?></button>
					<?php
					if ( $this->json )
					{ 			
						?>
						<button type="button" class="button" @click="uploadToJSON"><?php _e('Выгрузить в json', 'usam'); ?></button>
						<button type="button" class="button" @click="openloadFromJSON"><?php _e('Загрузить из json', 'usam'); ?></button>
						<?php						
						require_once( USAM_FILE_PATH.'/admin/includes/modal/modal-load-from-JSON.php' );
					}
					?>
				</div>
				<?php
			}
			else
				$this->display();
		}
	}
	
	protected function display_section( )
	{
		$method = "display_section_".$this->section;
		?>
		<div id="tab_section_<?php echo $this->section; ?>">
			<?php
			if ( $this->display_save_button )
			{ 			
				?>
				<form method='POST' action='' id='usam-tab_form'>
					<?php 
					$this->nonce_field();
					$this->$method(); 
					?>
					<div class="tab_buttons">
						<?php submit_button( __('Сохранить', 'usam'), 'primary', '', false, array( 'id' => 'action-submit' ) );	?>
						<input type="hidden" name="update" value="1" />										
					</div>
				</form>
				<?php
			}
			else
				$this->$method();	
			?>
		</div>
		<?php
	}
	
	protected function settings_view()
	{ 
		$this->display_tab_sections();			
		if ( $this->table )
			$this->list_table->display_table();	
		elseif ( $this->section && method_exists($this, "display_section_".$this->section) )
			$this->display_section();	
		elseif ( $this->display_save_button )
		{ 				
			?>
			<form method='POST' action='' id='usam-tab_form'>
				<?php 
				$this->nonce_field();
				$this->display_settings_view(); 
				?>
				<div class="tab_buttons">
					<?php submit_button( __('Сохранить', 'usam'), 'primary', '', false, array( 'id' => 'action-submit' ) );	?>
					<input type="hidden" name="update" value="1" />										
				</div>
			</form>
			<?php
		}		
		else			
			$this->display_settings_view();
	}
	
	public function display_settings_view( ) {	}
	
	public function get_settings_tabs( ) 
	{	
		return false;
	}
	
	public function map_view( ) 
	{		
		$codes = [ $this->tab.'_map', $this->tab ];
		foreach ( $codes as $filter_code ) 
		{
			if ( $filter_code )
			{
				$file = USAM_FILE_PATH . "/admin/interface-filters/{$filter_code}_interface_filters.class.php";		
				if ( file_exists($file) )
				{
					require_once( $file );
					$class = "{$filter_code}_Interface_Filters";
					$interface_filters = new $class();
					?>
					<div class='toolbar_filters'>
						<?php $interface_filters->display(); ?>
					</div>
					<?php			
				}
				break;
			}
		}
		switch ( $this->tab ) 
		{		
			case 'contacts' :
				$groups = usam_get_groups(['type' => 'contact']);			
			break;		
			case 'companies' :
				$groups = usam_get_groups(['type' => 'company']);	
			break;					
		}
		if ( !empty($groups) )
		{
			?>
			<div class="map_toolbar">
				<div class="map_toolbar__choice">				
					<div class="map_toolbar__title"><?php _e( 'Добавить в группу', 'usam'); ?></div>				
					<select v-model='pick_group'>
						<?php				
						foreach ( $groups as $group ) 
						{
							?>
							<option value='<?php echo $group->id; ?>'><?php echo $group->name; ?></option>
							<?php
						}															
						?>
					</select>
					<a class="map_toolbar__button button" @click="add_selected_layouts"><?php _e( 'Добавить', 'usam'); ?></a>
				</div>
				<div class="map_toolbar__counters">	
					<div class="map_toolbar__text"><?php _e( 'Выбрано', 'usam'); ?>:</div>		
					<div class="map_toolbar__counter" v-html="counter"></div>	
				</div>	
			</div>	
			<?php 
		} ?>
		<div id="map" style="width: 100%; height: 600px"></div>				
		<?php 
	}
	
	public function get_tab_sections( ) { return []; }	
	
	
	public function get_available_tab_sections( ) 
	{ 
		$sections = $this->get_tab_sections();
		$sections = apply_filters( "usam_tab_sections", $sections, $this->tab, $this->page_name );	
		$results = [];
		if( !empty($sections) )
		{		
			foreach ( $sections as $key => $section )
			{
				if ( !isset($section['capability']) || current_user_can( $section['capability'] ) )
					$results[$key] = $section;
			}
		}
		return $results;
	}	
		
	public function display_tab_sections( $select = null ) 
	{ 		
		$sections = $this->get_available_tab_sections();		
		if ( $select == null )
		{
			if ( $this->section )
				$select = $this->section;				
			elseif ( $this->table )
				$select = $this->table;	
			else
				$select = key($sections);
		}	
		if ( !isset($sections[$select]) )
			return false;	
		if ( count($sections) < 2 )
			return;

		echo "<div class = 'display_panel'>";
		echo "<ul>\n";
		$views = array();
		$default_url = admin_url("admin.php?page={$this->page_name}&tab={$this->tab}&view={$this->view}");
		foreach ( $sections as $key => $section ) 
		{
			if ( isset($section['url']) )
				$url = add_query_arg( $section['url'], $default_url );	
			elseif ( isset($section['type']) )
				$url = add_query_arg( array( $section['type'] => $key ), $default_url );	
			else
				$url = admin_url("admin.php?page={$this->page_name}&tab={$this->tab}");
				
			$class = ( $select === $key ) ? 'current' : '';
			$view = sprintf('<a href="%s" %s>%s</a>', $url, '', $section['title'] );			
			$views[$key] = "\t<li class='display_panel_{$key} $class'>$view";
		}
		echo implode( " |</li>\n", $views ) . "</li>\n";
		echo "</ul>";
		echo "</div>";		
	}
	
	public function display_screen_select() 
	{ 		
		$screens = $this->get_view_titles();
		if ( empty( $screens ) )
			return;
		
		echo "<div id='button-switch-view' class = 'button-switch-view'>";
		$views = array();	
		if ( count($this->views)>1 )
		{
			foreach( $this->views as $id ) 
			{							
				$view = sprintf('<a href="%s" id ="usam_'.$id.'_handle" class="button_view view_'.$id.'" title="%s"></a>', esc_url( admin_url("admin.php?page={$this->page_name}&tab={$this->tab}&view={$id}") ), $screens[$id]['title'] );
				$class = ( $this->view == $id ) ? 'current' : "";				
				$views[ $id ] = "\t<div class='$id $class'>$view";
			}
		}
		$id = 'help';
		$view = sprintf('<a href="#" id ="usam_help_center_handle" class="button_view view_'.$id.'" title="%s"></a>', __('Центр поддержки','usam') );
		$class = ( $this->view == $id ) ? 'current' : "";				
		$views[ $id ] = "\t<div class='$id $class'>$view";
		echo implode( "</div>", $views ) . "</div>\n";
		echo "</div>";		
	}	
	
	protected function localize_script_tab()
	{	
		return [];
	}
		
	public function list_table( )
    {		
		if ( $this->per_page )
		{ 
			$screen = get_current_screen();	
			add_screen_option( 'per_page', ['label' => __('Записей','usam'), 'default' => $this->per_page, 'option' => $screen->id."_page"]);
		}
		$this->list_table = usam_get_table( $this->table, ['singular' => $this->page_name, 'plural' => $this->table, 'tab' => $this->tab, 'page' => $this->page_name] );				
		if ( !empty($_REQUEST['confirm']) )
		{ 
			add_action( 'usam_list_table_before', [$this, 'bulk_delete_confirm'] );
			$this->list_table->disable_bulk_actions();	
			$this->list_table->disable_standart_button(); 				
			$this->list_table->disable_views();		
			$this->list_table->disable_filter_box();	
		}			
		return true;			
	}	
	
	protected function button_dropdown( ) 
	{	
		?>
		<div class = "panel_buttons">	
			<input type="submit" class="button button-primary button-large" value="<?php esc_attr_e('Распечатать','usam'); ?> " name = "print">
			<input type="submit" class="button button-primary button-large" value="<?php esc_attr_e('Импортировать','usam'); ?> " name = "export">
		</div>
		<?php
	}
	
	public function bulk_delete_confirm( ) 
	{	
		$url = remove_query_arg( array('confirm', 'action' ), wp_get_referer()  );
		?>
			<h3><?php esc_html_e( 'Вы уверены, что хотите удалить эти позиции?', 'usam'); ?><br /></h3>
			<div>
				<a href="<?php echo esc_url( $url ); ?>" class="button"><?php esc_html_e( 'Назад', 'usam'); ?></a>
				<input class="button-primary" type="submit" value="<?php esc_attr_e( 'Удалить', 'usam'); ?>" />
				<input type="hidden" name="confirm" value="1" />
				<input type="hidden" name="action" value="delete" />
			</div>
		<?php
	}
	
	public function set_user_screen_error( $message_error ) 
	{		
		$screen = get_current_screen();	
		if ( $message_error != '' )
			usam_set_user_screen_error( $message_error, $screen->id );
	}
		
	private function display_message( $messages, $type = 'message' ) 
	{
		$screen = get_current_screen();	
		if ( $type == 'message' )		
			$screen_message = usam_get_user_screen_message( $screen->id );
		else		
			$screen_message = usam_get_user_screen_error( $screen->id );	
		
		if ( !empty($screen_message) )
		{
			if ( is_array( $screen_message ))
				$messages = array_merge($messages, $screen_message);		
			else
				$messages[] = $screen_message;
		}	
		if ( !empty($messages) )		
		{
			foreach ( $messages as $message )
			{
				if ( $type == 'message' )	
					echo "<div class=\"updated\"><p>{$message}</p></div>";
				else
					echo "<div class=\"error\"><p>".__("Ошибка","usam").": {$message}</p></div>";
			}
		}		
	}	

	public function custom_bulk_admin_notices() 
	{		
		$messages = array();
		$errors = array();
		
		$message = $this->get_message();	
		if( $message != '' )	
		{
			if ( is_array( $message ))
				$messages = array_merge($messages, $message);		
			else
				$messages[] = $message;			
		}		
		if( isset($_REQUEST['send_email']) )		
		{
			if( $_REQUEST['send_email'] )
				$messages[] = __('Сообщение отправлено', 'usam');	
			else
				$errors[] = __('Сообщение не отправлено. Пожалуйста, убедитесь, что ваш сервер может отправлять сообщения электронной почты.', 'usam');
		}
		if( isset($_REQUEST['send_sms']) )		
		{
			if( $_REQUEST['send_sms'] )
				$messages[] = __('Сообщение отправлено', 'usam');	
			else
				$errors[] = __('Сообщение не отправлено. Пожалуйста, убедитесь, что вы правильно настроили смс шлюз.', 'usam');		
		}	
		if( isset($_REQUEST['update']) )		
			$messages[] = __('Настройки сохранены', 'usam');	
		$this->display_message( $messages, 'message' );	
	}

	protected function get_message()
	{	
		return '';
	}
		
	function help_tabs() 
	{
		return [];
	}
	
	function set_help_tabs() 
	{	
		$help_tabs = $this->help_tabs();		
		$help = new USAM_Help_Tab();
		$help->set_help_tabs( $this->page_name, $this->tab, $help_tabs );
	}
	
	public function get_tab() 
	{			
		return $this->tab;
	}
	
		// Получить название и описание вкладки
	public function get_title_tab()
	{			
		return '';
	}
	
	protected function submit_url( )
	{
		$this->sendback = add_query_arg( array('page' => $this->page_name, 'tab' => $this->tab, 'view' => $this->view ) );
		$this->sendback = remove_query_arg( array('update'), $this->sendback  );			
		$this->sendback = remove_query_arg( array('_wpnonce', '_wp_http_referer', 'usam_name_nonce_field', 'action', 'action2' ), $this->sendback  );	
		$this->post_in_get();		
	}	
	
	public function post_in_get()
	{			
		$posts = ['status', 'n', 'step', 'table', 's',  'usam-category', 'usam-brands', 'usam-category_sale'];	
		$mas = array();
		foreach( $posts as $post)
		{
			if ( isset($_POST[$post]) && $_POST[$post] !== '' )
				$mas[$post] = sanitize_text_field($_POST[$post]);
		}			
		if ( !empty($mas) )
			$this->sendback = $_SERVER['REQUEST_URI'] = add_query_arg( $mas , $this->sendback );
	}
	
	public function process_bulk_action()
    {
		if( isset($_REQUEST['usam_name_nonce_field']) )
			$nonce = $_REQUEST['usam_name_nonce_field'];
		elseif( isset($_REQUEST['_wpnonce']) )
			$nonce = $_REQUEST['_wpnonce'];
		else			
			return;
		$screen = get_current_screen();		
//check_admin_referer( 'update-options', 'usam-update-options' );					
		if ( !wp_verify_nonce( $nonce, 'usam-'.$screen->id ) )
		{
		//	echo 'Проверка не прошла';		
		//	wp_redirect( remove_query_arg( array('_wp_http_referer', '_wpnonce', 'action', 'action2'), stripslashes($_SERVER['REQUEST_URI']) ));
		//	exit;			  
		}		
		if (isset($this->list_table))					
			$this->current_action = $this->list_table->current_action();	
		elseif ( !empty($_REQUEST['action']) )
			$this->current_action = sanitize_title( $_REQUEST['action'] );		
	
		if ( !empty($this->current_action) )
			$this->redirect = true;	// Если нет действия, например, нажать фильтр		
	
		switch( $this->current_action )
		{				
			case 'save':										
				if ( isset($_REQUEST['form_name']) )
					$this->sendback = add_query_arg( array( 'form_name' => $_REQUEST['form_name'] ), $this->sendback );
				if ( $this->table )
					$this->sendback = add_query_arg( array( 'table' => $this->table ), $this->sendback );
				
				if ( isset($_POST['view-form']) )					
					$this->sendback = add_query_arg( array( 'form' => 'view' ), $this->sendback );	
				elseif ( empty($_REQUEST['save-close']) )						
					$this->sendback = add_query_arg( array( 'form' => 'edit' ), $this->sendback );							
				
				require_once( USAM_FILE_PATH . '/admin/includes/form-actions.class.php' );
				$result = USAM_Form_Actions::start();				
				if ( !empty($result) )
				{
					$this->sendback = add_query_arg( $result, $this->sendback );	
					if ( empty($result['id']) || isset($_REQUEST['save-close']) ) 
						$this->sendback = remove_query_arg(['form', 'id', 'form_name'], $this->sendback );				

					if ( isset($_REQUEST['save-add']) ) 
						$this->sendback = remove_query_arg( array( 'id' ), $this->sendback );								
				}					
			break;						
			default:				
				require_once( USAM_FILE_PATH . '/admin/includes/form-actions.class.php' );
				$result = USAM_Form_Actions::start();
				if ( empty($result) )
				{
					$this->sendback = remove_query_arg( array('cb', 'action', 'status', 'id' ), $this->sendback );	
					require_once( USAM_FILE_PATH . '/admin/includes/elements-actions.class.php' );
					$type_item = $this->table?$this->table:$this->tab;
					$result = USAM_Elements_Actions::start( $type_item );
				}
				if ( empty($result) )
					$this->action_processing();	
				else
					$this->sendback = add_query_arg( $result, $this->sendback );	
			break;
		}
		if ( $this->redirect )
		{ 
			wp_redirect( $this->sendback );
			exit();
		}		
	}
	
	// Вернуть правила условий корзины
	public function get_rules_basket_conditions( ) 
	{				
		require_once( USAM_FILE_PATH . '/admin/includes/rules/basket_discount_rules.class.php' );	
			
		$rules_work_basket = new USAM_Basket_Discount_Rules( );	
		return $rules_work_basket->get_rules_basket_conditions(  );	
	}
	
	public function include_js()
    {				
		wp_print_scripts( 'jquery' );	
		wp_enqueue_script( 'hc-sticky' );		
		if ( $this->view == 'report' )
		{
			wp_enqueue_script( 'd3' );		
			wp_enqueue_script( 'usam-graph' );	
		}
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'jquery-ui-droppable' );	
		wp_enqueue_script( 'jquery-ui-sortable' );
		if ( $this->view == 'form' )
			wp_enqueue_style( 'usam-element-form' );
				
		$call = apply_filters( 'usam_download_call_data', [] );		
		$screen = get_current_screen();		
		wp_enqueue_script( 'usam-admin-tab', USAM_URL . '/admin/assets/js/tabs/tabs.js', ['jquery-query'], USAM_VERSION_ASSETS );
		$universam_tabs_localize = [							
			'navigate_tab_nonce'                  => usam_create_ajax_nonce( 'navigate_tab' ),		
			'hidden_menu_tab_nonce'               => usam_create_ajax_nonce( 'hidden_menu_tab' ),	
			'add_favorites_page_nonce'            => usam_create_ajax_nonce( 'add_favorites_page' ),				
			'sort_menu_tabs_nonce'                => usam_create_ajax_nonce( 'sort_menu_tabs' ),		
			'show_hidden_menu_tab_nonce'          => usam_create_ajax_nonce( 'show_hidden_menu_tab' ),	
			'show_all_menu_tab_nonce'             => usam_create_ajax_nonce( 'show_all_menu_tab' ),	
			'load_list_data_nonce'                => usam_create_ajax_nonce( 'load_list_data' ),	
			'load_graph_data_nonce'               => usam_create_ajax_nonce( 'load_graph_data' ),		
			'total_results_report_nonce'          => usam_create_ajax_nonce( 'total_results_report' ),			
			'add_event_nonce'                     => usam_create_ajax_nonce( 'add_event' ),							
			'get_list_table'                      => usam_create_ajax_nonce( 'get_list_table' ),	
			'bulkactions_nonce'                   => usam_create_ajax_nonce( 'bulkactions' ),	
			'save_option_nonce'                   => usam_create_ajax_nonce( 'save_option' ), 
			'export_table_to_excel_nonce'         => usam_create_ajax_nonce( 'export_table_to_excel' ),	
			'print_table_nonce'                   => usam_create_ajax_nonce( 'print_table' ),	
			'phone_call_nonce'                    => usam_create_ajax_nonce( 'phone_call' ),	
			'cancel_phone_call_nonce'             => usam_create_ajax_nonce( 'cancel_phone_call' ),
			'get_email_sending_form_nonce'        => usam_create_ajax_nonce( 'get_email_sending_form' ),
			'get_sms_sending_form_nonce'          => usam_create_ajax_nonce( 'get_sms_sending_form' ),		
			'get_signature_nonce'                 => usam_create_ajax_nonce( 'get_signature' ),						
			'get_map_data_nonce'                  => usam_create_ajax_nonce( 'get_map_data' ),	
			'bulk_actions_nonce'                  => usam_create_ajax_nonce( 'bulk_actions_'.$this->table ),	
			'get_form_confirmation_delete_nonce'  => usam_create_ajax_nonce( 'get_form_confirmation_delete' ),
			'delete_nonce'                        => usam_create_ajax_nonce( 'delete' ),			
			'add_pick_group_nonce'                => usam_create_ajax_nonce( 'add_pick_group' ),		
			'form_save_nonce'                     => usam_create_ajax_nonce( 'form_save' ),	
			'call_status_message'                 => __('Соединение', 'usam'),	
			'not_assigned_message'                => __('Не назначено','usam'),
			'call'                                => $call,					
			'id'                                  => isset($_GET['id'])?$_GET['id']:0,		
			'tab'                                 => $this->tab,				
			'page'                                => $this->page_name,			
			'table'                               => isset($_GET['subtab'])?$_GET['subtab']:$this->table,	
			'screen_id'                           => !empty($screen)?$screen->id:0,		
			'url'                                 => add_query_arg(['page' => $this->page_name, 'tab' => $this->tab], admin_url('admin.php') ),	
			'edit_button_text'                    => __('Изменить', 'usam'),
			'before_unload_dialog'                => __('Внесенные изменения будут потеряны, если вы уйдете с этой страницы.', 'usam'),
			'ajax_navigate_confirm_dialog'        => __('Внесенные изменения будут потеряны, если вы уйдете с этой страницы.', 'usam')."\n\n".__('Нажмите OK, чтобы отменить изменения, или Отмена, чтобы оставаться на этой странице.' )
		];				
		if ( $this->view == 'map' )
		{
			$company_id = get_option( 'usam_shop_company', 0 );
			$latitude = usam_get_company_metadata( $company_id, 'latitude' );
			$longitude = usam_get_company_metadata( $company_id, 'longitude' );		
			$universam_tabs_localize['latitude'] = !empty($latitude)?$latitude:54.7089669;
			$universam_tabs_localize['longitude'] = !empty($longitude)?$longitude:20.552531899999963;	
			?>	
			<script src="https://maps.api.2gis.ru/2.0/loader.js?pkg=full"></script>			
			<script src="https://maps.api.2gis.ru/2.0/cluster_realworld.js"></script>
			<link rel="stylesheet" href="https://2gis.github.io/mapsapi/vendors/Leaflet.markerCluster/MarkerCluster.css" />		
			<link rel="stylesheet" href="https://2gis.github.io/mapsapi/vendors/Leaflet.markerCluster/MarkerCluster.Default.css" />	
			<?php		
			$yandex = get_option('usam_yandex');
			if ( !empty($yandex['developer']['api']) )
			{
			//	wp_enqueue_script( 'api-maps-yandex', 'https://api-maps.yandex.ru/2.1/?apikey='.$yandex['developer']['api'].'&lang=ru_RU', array( ), USAM_VERSION_ASSETS );	
			}
		}
		wp_localize_script( 'usam-admin-tab', 'USAM_Tabs', $universam_tabs_localize );			
		$file_js = '/admin/assets/js/tabs/'.$this->page_name.'.js';
		if ( file_exists(USAM_FILE_PATH.$file_js) ) 
		{ 			
			$js_class = 'USAM_Page_'.str_replace("-", "_", $this->page_name);
			wp_enqueue_script( 'usam-admin-tab-'.$this->page_name, USAM_URL.$file_js, ['jquery-query'], USAM_VERSION_ASSETS );		
			
			$data = $this->localize_script_tab();			
			wp_localize_script( 'usam-admin-tab-'.$this->page_name, $js_class, $data );			
		}				
		wp_enqueue_style( 'usam-tabs-admin', USAM_URL .'/admin/assets/css/tabs.css', false, USAM_VERSION_ASSETS, 'all' );	
		$file_css = '/admin/assets/css/tabs/'.$this->page_name.'.css';
		if ( file_exists(USAM_FILE_PATH.$file_css) ) 
			wp_enqueue_style( 'usam-admin-tab-'.$this->page_name, USAM_URL.$file_css, false, USAM_VERSION_ASSETS, 'all' );		

		wp_enqueue_style( 'usam-help-center-admin', USAM_URL .'/admin/assets/css/help-center.css', false, USAM_VERSION_ASSETS, 'all' );						
	} 	
		
	public function footer_include_js() 
	{
		$this->add_to_footer();
		
		require_once( USAM_FILE_PATH . '/admin/includes/help_center/help_center.class.php' );
		$help_center = new USAM_Help_Center();		
		wp_enqueue_script( 'usam-admin-help_center', USAM_URL . '/admin/assets/js/help_center.js', [], USAM_VERSION_ASSETS );
		wp_localize_script( 'usam-admin-help_center', 'HC', [			
			'tabs' => $help_center->get_default_tabs(),
		]);	
	}				
	
	protected function display_table_row_option( $options ) 
	{
		require_once( USAM_FILE_PATH .'/admin/includes/save-options.class.php' );
		$so = new USAM_Save_Option();
		$so->display( $options );		
	}
	
	protected function row_option( $options ) 
	{		
		require_once( USAM_FILE_PATH .'/admin/includes/save-options.class.php' );
		$so = new USAM_Save_Option();
		$so->row_option( $options );
	}
						
	protected function buy_section() 
	{		
		$section = $this->page_name.'_'.$this->tab;
		?>
		<div class="buy_section">
			<h2 class="tab_title message"><?php _e( 'Подключите этот раздел чтобы иметь больше возможностей для управления', 'usam'); ?></h2>
			<a target="_blank" href="<?php echo add_query_arg(['action' => 'buy_section', 'section' => $section],'http://wp-universam/buy/' ); ?>" rel="noopener" id="buy_section-<?php echo $section; ?>" class="button-primary button"><?php _e( 'Подключить сейчас', 'usam'); ?></a>
		</div>
		<?php
	}	
		
	public function check_the_version() 
	{
		$page_free = [			
			'orders' => ['buyer_refunds', 'leads', 'subscriptions', 'checks'], 
			'delivery' => 'all',			
			'feedback' => ['chat', 'sms', 'email'], 
			'personnel' => 'all',
			'crm' => ['suggestions', 'invoice', 'affairs', 'contracts', 'acts', 'pricelist', 'plan'],
			'procurement' => 'all', 
			'documents' => 'all', 
			'site_company' => 'all', 
			'bookkeeping' => 'all', 			
			'manage_discounts' => ['accumulative'], 			
			'storage' => ['warehouse_documents', 'sold'], 
			'exchange' => ['parser', '1c', 'showcases'],
			'social_networks' => 'all',
			'seo' => ['positions','sites','analysis','keywords'],
			'marketing' => ['trading_platforms', 'sets'],		
			'marketplace' => 'all',			
			'automation' => 'all', 
			'newsletter' => 'all', 
			'reports' => ['constructor'],
			'competitor_analysis' => 'all', 
		];
		$page_lite = [
			'orders' => ['buyer_refunds', 'leads', 'subscriptions', 'checks'], 
			'delivery' => 'all',			
			'feedback' => ['sms', 'email'], 
			'crm' => ['suggestions', 'invoice', 'affairs', 'contracts', 'acts', 'pricelist', 'plan'],
			'procurement' => 'all', 
			'documents' => 'all', 
			'bookkeeping' => 'all', 			
			'manage_discounts' => ['accumulative'], 			
			'storage' => ['warehouse_documents', 'sold'], 
			'exchange' => ['1c', 'showcases'],
			'social_networks' => 'all',
			'marketing' => ['sets'],		
			'marketplace' => 'all',			
			'automation' => 'all', 
			'newsletter' => 'all', 
			'reports' => ['constructor'],
			'competitor_analysis' => 'all', 
		];	
		$SMALL_BUSINESS = [
			'orders' => ['buyer_refunds', 'subscriptions', 'checks'], 
			'delivery' => 'all',
			'crm' => ['suggestions', 'invoice', 'affairs', 'contracts', 'acts', 'plan'],
			'procurement' => 'all', // Закупки
			'documents' => 'all', 
			'bookkeeping' => 'all', 			
			'manage_discounts' => ['accumulative'], 			
			'storage' => ['warehouse_documents', 'sold'], 
			'exchange' => ['showcases'],	
			'marketplace' => 'all',			
			'reports' => ['constructor'],
			'competitor_analysis' => 'all', 
		];	
		$BUSINESS = [
			'marketplace' => 'all',			
			'competitor_analysis' => 'all', 
		];						
		if ( usam_is_license_type('FREE') )
		{
			return false;
			$page = $page_free; //Отключил времменно
		}
		elseif ( usam_is_license_type('LITE') )
			$page = $page_lite;
		elseif ( usam_is_license_type('SMALL_BUSINESS') )
			$page = $SMALL_BUSINESS;
		elseif ( usam_is_license_type('BUSINESS') )
			$page = $BUSINESS;
		else
			return false;
				
		if ( isset($page[$this->page_name]) )
		{ 
			if ( $page[$this->page_name] == 'all' )
				return true;	
			elseif ( in_array($this->tab, $page[$this->page_name]) )
				return true;	
		}	
		return false;			
	}
	
	public function display_paid() 
	{	
		if ( $this->check_the_version() )
		{
			?>		
			<div style="margin-bottom:10px; width:100%;color:#fff;background-color: #a4286a;box-shadow: inset 0 10px 10px -5px rgba(123, 30, 80, 0.5), inset 0 -10px 10px -5px rgba(123, 30, 80, 0.5);">
				<div style="padding:5px;text-align:center;">
					Этот раздел имеет ограниченную функциональность в вашей версии. Перейди на расширенную версию, чтобы использовать все функции раздела. 
					<a target="_blank"  rel="noopener" href="http://wp-universam.ru/buy/" style="color:#ff8f8f;font-weight: 600; text-transform: uppercase;border-bottom: 1px solid #ff8f8f;">Перейти сейчас</a>
				</div>	
			</div>
			<?php
		}
		
		if ( usam_check_current_user_role('administrator') ) 
		{
			$license = get_option( 'usam_license', [] );
			if ( !empty($license['license_end_date']) )
			{
				$day = round((strtotime($license['license_end_date']) - time())/(60*60*24));	
				if ( $day < 0 )
				{
					?><div class ="subscription_ended item_status_attention"><?php printf( __( 'Обновления и поддержка завершилась %s дней назад. Вам нужно продлить лицензию на год, чтобы обновлять и получать техническую помощь.', 'usam'), abs($day) ); ?> <a href="https://wp-universam.ru/product-category/licenses/"><?php _e('Продлить сейчас', 'usam'); ?></a></div><?php
				}
				elseif ( $day < 30 )
				{
					?><div class ="subscription_ended item_status_notcomplete"><?php printf( __( 'Обновления и поддержка завершатся через %s дней. Вам нужно продлить лицензию на год, чтобы обновлять и получать техническую помощь.', 'usam'), abs($day) ); ?> <a href="https://wp-universam.ru/product-category/licenses/"><?php _e('Продлить сейчас', 'usam'); ?></a></div><?php
				}
			}
		}
	}		
}
?>