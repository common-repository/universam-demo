<?php
require_once( USAM_FILE_PATH . '/admin/includes/page_tab.class.php' );

final class USAM_Page_Tabs
{	
	private static $instance; 
	private static $default_tabs = array(); 
	private static $display_tabs = array();
	private static $hidden_tabs = array();	
	private static $page_name = '';		

	private $group_tab_id;
	private $current_tab_id; 
	private $current_tab_class = null; 
	private $tabs; 
	
	public function __construct( $page = null, $tab_id = null )
	{
		$this->set_current_page( $page );			
		if ( !empty(self::$display_tabs) )
		{	
			$this->init();
			
			do_action( 'usam_register_tabs', $this, self::$display_tabs );		
			$this->set_current_tab( $tab_id );					
		}
	}
	
	public static function init(  )
	{	
		add_action( 'usam_load_tab_class', ['USAM_Page_Tabs', 'load_default_tab_class'], 1 );
		add_action( 'usam_register_tabs' , ['USAM_Page_Tabs', 'register_default_tabs'], 2, 2 );		
	}
	
	public static function get_instance() 
	{ 
		if ( ! self::$instance ) 
			self::$instance = new USAM_Page_Tabs();		
		return self::$instance;
	}

	public static function load_default_tab_class( $page_instance )
	{
		$current_tab_id = $page_instance->get_current_tab_id();		
		$file_tab =  USAM_FILE_PATH . '/admin/tabs/'.$current_tab_id.'.php';	
		if ( file_exists($file_tab) ) 
			require_once( $file_tab );
	}
	
	public static function register_default_tabs( $page_instance, $tabs )
	{		
		foreach ( (array)$tabs as $id => $value )
		{		
			$page_instance->register_tab( $value['id'], $value['title'] );	
			if ( isset($value['level']) )
			{
				$page_instance->register_default_tabs($page_instance, $value['level']);
			}		
		}		
	}	
	
	public function get_current_tab()
	{			
		if ( ! $this->current_tab_class ) 
		{ 
			do_action( 'usam_load_tab_class', $this );
			$class_name = ucwords( $this->current_tab_id );			
			$class_name = 'USAM_Tab_' . $class_name;
			if ( class_exists($class_name) ) 
			{		
				$reflection = new ReflectionClass( $class_name );			
				$this->current_tab_class = $reflection->newInstance();
				$this->current_tab_class->load( self::$page_name, $this->current_tab_id );						
			}
		}		
		return $this->current_tab_class;
	}
	
	public function get_current_tab_id()
	{
		return $this->current_tab_id;
	}
	
	public function set_current_page( $page = null )
	{	
		if ( $page !== null )
			self::$page_name = $page;
		elseif ( !empty($_GET['page']) )
			self::$page_name = sanitize_text_field($_GET['page']);	
		elseif ( !empty($_POST['page']) )
			self::$page_name = sanitize_text_field($_POST['page']);
		else
			self::$page_name = '';
						
		$filename = USAM_FILE_PATH . '/admin/pages/'.self::$page_name.'.php';
		if ( file_exists($filename) ) 
			require( $filename );
		
		do_action( 'usam_set_current_page', $this );	
	
		$page_tabs = usam_get_page_tabs();
		if ( !empty($page_tabs[self::$page_name]) )
		{	
			$current_user = wp_get_current_user();	
			$hidden_tabs = get_user_option( 'usam_hidden_menu_tabs' );		
			$display_tabs = array();
			foreach ( $page_tabs[self::$page_name] as $tab )
			{		
				if ( !isset($tab['capability']) || $current_user->has_cap( $tab['capability'] ) )
				{					
					self::$default_tabs[] = $tab; //Вкладки	
					if ( empty($hidden_tabs[self::$page_name]) || !in_array($tab['id'],$hidden_tabs[self::$page_name]) )						
						$display_tabs[$tab['id']] = $tab; //Вкладки	
					else
						self::$hidden_tabs[] = $tab; //Вкладки	
				}
			}			
			$sort_menu_tabs = get_user_option( 'usam_sort_menu_tabs' );		
			if ( isset($sort_menu_tabs[self::$page_name]) )
			{
				foreach ( $sort_menu_tabs[self::$page_name] as $tab )
				{				
					if( isset($display_tabs[$tab]) )
					{
						self::$display_tabs[] = $display_tabs[$tab];
						unset($display_tabs[$tab]);
					}
				}
				if ( !empty($display_tabs) )
				{
					foreach ( $display_tabs as $tab )
						self::$display_tabs[] = $tab;
				}
			}
			else
				self::$display_tabs = $display_tabs;
		
			if ( self::$page_name == 'shop_settings' )
				self::$display_tabs = self::$default_tabs;
		}
	}
	
	public function go_through_levels_tab( $tabs )
	{
		foreach ( $tabs as $tab )
		{
			if ( $tab['id'] == $this->current_tab_id )
				return true;
			elseif ( isset($tab['level']) && $this->go_through_levels_tab( $tab['level'] ))
			{
				return true;
			}
		}
		return false;
	}
	
	
	public function set_group_tab_id( )
	{
		foreach ( self::$display_tabs as $tab )
		{
			if ( $tab['id'] == $this->current_tab_id )
			{
				$this->group_tab_id == $this->current_tab_id;
				break;
			}
			elseif ( isset($tab['level']) && $this->go_through_levels_tab( $tab['level'] ) )
			{
				$this->group_tab_id = $tab['id'];
				break;
			}
		}
	}		
		
	public function set_current_tab( $tab_id = null )
	{		
		if ( empty($this->tabs) )
			return;
	
		if ( ! $tab_id ) 
		{				
			if ( !empty($_REQUEST['tab']) )
				$this->current_tab_id = sanitize_title($_REQUEST['tab']);
			else
			{ 
				$key = array_keys( $this->tabs );
				$this->current_tab_id = array_shift( $key );
			}			
		} 
		else 
			$this->current_tab_id = $tab_id;		
	
		$this->set_group_tab_id();	
		$this->current_tab_class = $this->get_current_tab();	
	}	
		
	public function register_tab( $id, $title ) 
	{
		$this->tabs[$id] = $title;
	}

	public function get_tabs() 
	{
		return $this->tabs;
	}

	private function tab_class( $id, $recursion ) 
	{		
		$class = 'navigation-tab';
		if ( $id == $this->current_tab_id || $id == $this->group_tab_id )
			$class .= ' navigation-tab-active';
		if ( $id == $this->group_tab_id )
			$class .= ' navigation-group-tab-active';
		if ( $recursion === 1 )
			$class .= ' droppable-item';
		else
			$class .= " navigation-tab-$recursion";		
		return $class;
	}	
	
	public function get_breadcrumbs( $tabs )
	{	
		$breadcrumbs = array();	
		foreach ( $tabs as $tab )
		{	
			$result = null;
			if ( isset($tab['level']) )				
				$result = $this->get_breadcrumbs( $tab['level'] );
			
			if ( $this->current_tab_id == $tab['id'] || !empty($result) )
			{					
				$breadcrumbs[] = array( 'id' => $tab['id'], 'title' => $tab['title'], 'url' => '' );
				if ( !empty($result) )
					$breadcrumbs = array_merge($breadcrumbs, $result);	
			}			
		}			
		return $breadcrumbs;
	}
	
	public function display_breadcrumbs(  )
	{	
		global $title;	
		
		$breadcrumbs = $this->get_breadcrumbs( self::$default_tabs );		
		?>		
		<div class="breadcrumbs-tab-wrapper">												
		<ul class="breadcrumb_tab">
			<li>
				<a class="breadcrumb-0" href="<?php echo esc_attr( '?page='.self::$page_name ); ?>"><?php echo $title; ?></a>&nbsp;&raquo;&nbsp;
			</li>
			<?php 			
			foreach ( $breadcrumbs as $breadcrumb )
			{				
				if ( $this->current_tab_id == $breadcrumb['id'] )
				{
					?>
					<li>
						<span class="breadcrumb-<?php echo $breadcrumb['id']; ?>"><?php echo esc_html( $breadcrumb['title'] ); ?></span>
					</li>
					<?php 
				}	
				else 
				{
				?>
				<li>
					<a class="breadcrumb-<?php echo $breadcrumb['id']; ?>" href="<?php echo esc_attr( '?page='.self::$page_name.'&tab='.$breadcrumb['id'] ); ?>"><?php echo esc_html( $breadcrumb['title'] ); ?></a>&nbsp;&raquo;&nbsp;
				</li>
				<?php 
				}
			}
			?>
		</ul>
		</div>
		<?php
	}	

	public function output_tabs( $tabs )
	{
		static $recursion = 0;		
		$recursion++;
		
		$count = 0;
		
		switch ( $recursion )
		{
			case 1:	
				$type_list_tag = 'ul';
				$type_item_tag = 'li';
				$class = 'main-menu';
				if ( $this->menu_control() )
					$class .= ' droppable-menu';
			break;
			case 2:	
				$type_list_tag = 'ul';
				$type_item_tag = 'li';
				$class = 'subtab-menu';
			break;
			case 3:			
				$type_list_tag = 'dl';
				$type_item_tag = 'dt';
				$class = 'subtab-menu';
				
				$count = ceil( count( $tabs ) / 2 );
			break;
			default:
				$type_list_tag = 'dl';
				$type_item_tag = 'dd';
				$class = 'subtab-menu';				
			break;					
		}		
		$i = 0;
		?>
		<<?php echo $type_list_tag; ?> id="menu_tabs-<?php echo $recursion; ?>" class="menu_tabs_level-<?php echo $recursion; ?> <?php echo $class; ?>">
			<?php 
			foreach ( $tabs as $tab )
			{					
				if ( $tab['title'] != '' )
				{
					$title = apply_filters( 'usam_display_tab_name', $tab['title'], $tab['id'] );	
					$i++;
					?>
					<<?php echo $type_item_tag; ?> class="<?php echo $this->tab_class( $tab['id'], $recursion ); ?>" id="navigation_tab_<?php echo esc_attr( $tab['id'] ); ?>">
						<a data-tab-id="<?php echo esc_attr( $tab['id'] ); ?>" href="<?php echo esc_attr( '?page='.self::$page_name.'&tab='.$tab['id'] ); ?>"><?php echo $title;
							if ( isset($tab['level']) )
							{
								if ( $recursion == 1 )
									echo '<span class="dashicons dashicons-arrow-down-alt2"></span>';
								elseif ( $recursion == 2 )
									echo '<span class="dashicons dashicons-arrow-right-alt2"></span>';
							}
						?></a>
					<?php 				
					if ( isset($tab['level']) )
					{						
						$this->output_tabs( $tab['level'] );
						$recursion --;
					}
					?>
					</<?php echo $type_item_tag; ?>>
					<?php 
					if ( $recursion == 2 && $count == $i )			
					{				
						$str .= '</dl><dl>';				
					}		
				}				
			}
			?>
		</<?php echo $type_list_tag; ?>>
		<?php
	}	
		
	public function display_current_tab()
	{
		?>
		<div id="tab_<?php echo esc_attr( $this->current_tab_id ); ?>" class="tab-content tab_<?php echo esc_attr( $this->current_tab_id ); ?>">
			<?php 
			if ( $this->current_tab_class == null )
				_e('Не доступа к вкладке','usam');
			elseif ( method_exists( $this->current_tab_class, 'display_tab' ) ) 
				$this->current_tab_class->display_tab();
			else
				_e('Файл вкладки не существует','usam');
			?>			
		</div>
		<?php
	}
	
	public function menu_control()
	{		
		if ( self::$page_name != 'shop_settings' && self::$page_name != 'license' )
			return true;
		return false;
	}
		
	public function display()
	{
		global $title;					
		if ( self::$page_name == 'orders' && (filter_input( INPUT_GET, 'about' ) || get_transient( 'usam_process_complete')) ) 
		{			
			delete_transient('usam_process_complete');
			require_once( USAM_FILE_PATH . '/admin/includes/about.php' );
			return;
		}		
		if ( $this->current_tab_class != null )
		{			
			?>
			<div id="usam_page_tabs" class="usam_page_tabs wrap">
				<div class="usam-page-tabs-titles-wrap">
					<button id="menu-toggle" class="menu-toggle" aria-expanded="false" aria-controls="site-navigation social-navigation"><?php _e('Меню','usam'); ?></button>
					<h1 id="usam-page-tabs-title"><?php echo $title; ?></h1>		
					<div class="wp-header-end"></div>
				</div>					
				<div class="main_menu <?php echo self::$page_name!='shop_settings'?'nav_tab_droppable':''; ?>">							
					<div class="main_menu__items menu-tab">
						<?php $this->output_tabs( self::$display_tabs ); ?>	
					</div>
					<?php
					if ( $this->menu_control() )
					{
						?>	
						<div class="main_menu__settings">
							<div id="main_menu_settings_button" class="main_menu__settings_button"><span class="dashicons dashicons-admin-generic"></span></div>	
							<div class="settings-menu-tabnav">	
								<div class="hidden_menu_tabs">
									<div class="hidden_items_name">
										<?php _e('Скрытые вкладки','usam'); ?>
									</div>
									<div class="hidden_items">
										<ul id="list_hidden_items" class ="droppable-menu">
										<?php
										if ( !empty(self::$hidden_tabs) )
										{								
											foreach ( self::$hidden_tabs as $tab )	
											{
												?>		
												<li class="hidden_tab droppable-item navigation-tab">
													<a data-tab-id="<?php echo esc_attr( $tab['id'] ); ?>" href="<?php echo esc_attr( '?page='.self::$page_name.'&tab='.$tab['id'] ); ?>"><?php echo esc_html( $tab['title'] ); ?></a>
												</li>		
												<?php 
											}											
										}
										?>			
											<li class="none_hidden_items"><?php _e('Перенесите в эту область, чтобы скрыть','usam'); ?></li>												
										</ul>												
									</div>										
								</div>
								<div class="show_all">
									<?php _e('Показать все','usam'); ?>
								</div>
							</div>								
						</div>
						<?php
					}
					?>						
				</div>					
				<div id='usam_page_tab' class = 'usam_page page-<?php echo self::$page_name; ?>'>
					<?php //$this->display_breadcrumbs( ); ?>									
					<?php $this->display_current_tab();	?>
				</div>	
			</div>
		<?php
		}
		else
		{
			?>
			<div id="usam_page_tabs" class="usam_page_tabs wrap">
				<h1 id="usam-page-tabs-title"><?php printf( __('У вас нет доступа к странице &laquo;%s&raquo;','usam'),$title); ?></h1>				
			</div>
		<?php
		}
	}		
}
?>