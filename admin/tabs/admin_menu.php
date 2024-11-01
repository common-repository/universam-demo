<?php
class USAM_Tab_admin_menu extends USAM_Tab
{	
	protected $views = ['settings'];	
	protected $current_role = 'administrator';
	
	public function __construct()
	{
		$this->current_role = !empty($_REQUEST['role']) ? sanitize_title($_REQUEST['role']) : $this->current_role;
	}
	
	public function get_title_tab()
	{		
		if ( isset($_REQUEST['section']) )
		{
			if ( $_REQUEST['section'] == 'menu' )	
				return __('Доступы к разделам', 'usam');
			elseif ( $_REQUEST['section'] == 'document' )	
				return __('Документы', 'usam');
		}	
		return __('Управление доступом', 'usam');	
	}
	
	public function get_tab_sections() 
	{ 
		if ( $this->view == 'settings' )
			$tables = $this->get_settings_tabs();
		else 
			$tables = array();
		return $tables;
	}
		
	protected function action_processing()
    {	 		
		$capabilities = array_map('intval', $_REQUEST['capabilities']);		
		$role = get_role( $this->current_role );		
		if ( !empty($role) )
		{
			foreach ( $capabilities as $capability_id => $active ) 
			{						
				if ( $active )
				{	
					if ( !$role->has_cap( $capability_id ) )
						$role->add_cap( $capability_id );			
				}	
				else		
					$role->remove_cap( $capability_id );	
			}
		}	
	}
	
	public function get_settings_tabs() 
	{ 
		$tabs = [ 
			'menu' => ['title' => __('Меню','usam'), 'type' => 'section'], 
			'document' => ['title' => __('Документы','usam'), 'type' => 'section'], 			
			'event' => ['title' => __('Дела, задания, события','usam'), 'type' => 'section'],
			'crm' => ['title' => __('CRM','usam'), 'type' => 'section'],
			'product' => ['title' => __('Товар','usam'), 'type' => 'section'],		
			'wordpress' => ['title' => 'Wordpress', 'type' => 'section'],				
		];
		if ( get_option('usam_website_type', 'store' ) == 'marketplace' )
			$tabs['marketplace'] = ['title' => __('Маркетплейс','usam'), 'type' => 'section'];
		return $tabs;			
	}
		
	public function display_roles() 
	{
		global $wp_roles;
		?>		
		<div class="edit_form admin_menu_role">				
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php _e( "Роль для изменения доступа", "usam" ); ?>:</div>
				<div class ="edit_form__item_option">
					<select name="role" id="role">
						<?php 
							foreach( $wp_roles->roles as $key => $role )
							{ 
								?><option value="<?php echo $key; ?>" <?php selected($this->current_role, $key); ?>><?php echo translate_user_role($role['name']); ?></option><?php
							}
						?>
					</select>
				</div>
			</div>
		</div>
		<?php	
	}
	
	public function display_section_product() 
	{	
		$this->display_roles();
		usam_add_box( 'usam_product_settings', __('Общие возможности'), [$this, 'display_product_settings'] );
	}
	
	public function display_product_settings( ) 
	{
		$name_capability = [
			'edit_product' => __('Редактирование','usam'), 			
			'read_product' => __('Просмотр','usam'), 
			'delete_product' => __('Удаление','usam'), 
			'edit_products' => __('Редактирование товаров','usam'), 
			'edit_others_posts' => __('Редактирование товаров, которые принадлежат другому пользователю','usam'), 
			'delete_products' => __('Удаление товаров','usam'), 
			'read_private_products' => __('Просмотр личных товаров','usam'),			
			'publish_products' => __('Публикация','usam'),			
			'manage_product_attribute' => __('Просмотр свойств товаров','usam'), 
			'edit_product_attribute' => __('Изменения свойств товаров','usam'), 
			'add_product_attribute' => __('Добавлять свойства товаров','usam'), 
			'delete_product_attribute' => __('Удаление свойств товаров','usam'), 
			'manage_product_category' => __('Просмотр категорий','usam'), 
			'edit_product_category' => __('Редактирование категорий','usam'), 
			'delete_product_category' => __('Удаление категорий','usam'),			
			'manage_product_selection' => __('Просмотр подборок','usam'), 
			'edit_product_selection' => __('Редактирование подборок','usam'), 
			'delete_product_selection' => __('Удаление подборок','usam'),			
			'manage_product_catalog' => __('Просмотр каталогов','usam'), 
			'edit_product_catalog' => __('Редактирование каталогов','usam'), 
			'delete_product_catalog' => __('Удаление каталогов','usam'),
		];	
		$this->display_capability( $name_capability );
	}	
	
	public function display_section_Wordpress() 
	{	
		$this->display_roles();
		usam_add_box( 'usam_Wordpress_settings', __('Общие возможности'), [$this, 'display_Wordpress_settings'] );
		usam_add_box( 'usam_Wordpress_service_settings', __('Обслуживание'), [$this, 'display_service_Wordpress_settings'] );
	}
	
	public function display_Wordpress_settings( ) 
	{
		$name_capability = [
			'upload_files' => __('Загрузка файлов','usam'), 
			'publish_posts' => __('Публикация постов','usam'), 
			'edit_posts' => __('Редактирование записей','usam'), 
			'edit_published_posts' => __('Редактирование опубликованных записей','usam'), 
			'delete_posts' => __('Удаление записей','usam'), 
			'delete_published_posts' => __('Удаление опубликованных записей','usam'),
			
			'publish_pages' => __('Публикация страниц','usam'), 
			'read_private_pages' => __('Просмотр страниц, отмеченных как «Личное»','usam'), 
			'edit_pages' => __('Редактирование страниц','usam'), 
			'edit_others_pages' => __('Редактирование страниц, созданных другими пользователями','usam'), 
			'edit_private_pages' => __('Редактирование страниц, отмеченных как «Личное»','usam'), 
			'edit_published_pages' => __('Редактирование опубликованных страниц','usam'), 
			'delete_pages' => __('Удаление страниц','usam'), 
			'delete_pages' => __('Удаление страниц','usam'), 
			'delete_others_pages' => __('Удаление страниц, созданных другими пользователями','usam'),
			'delete_published_pages' => __('Удаление опубликованных страниц','usam'),
			'delete_private_pages' => __('Удаление страниц, отмеченных как «Личное»','usam')
		];
		$this->display_capability( $name_capability );
	}
	
	public function display_service_Wordpress_settings( ) 
	{
		$name_capability = ['update_plugins' => __('Обновление плагинов','usam')];
		$this->display_capability( $name_capability );
	}
	
	public function display_document( $document_type ) 
	{
		$name_capability = [
			'view' => __('Просмотр только своих','usam'), 'department_view' => __('Просмотр документов отдела','usam'), 'company_view' => __('Просмотр документов компании','usam'), 'any_view' => __('Просмотр все документов','usam'), 
			'edit' => __('Изменение только своих','usam'), 'department_edit' => __('Изменение документов отдела','usam'), 'company_edit' => __('Изменение документов компании','usam'), 'any_edit' => __('Изменение все документов','usam'),
			'edit_status' => __('Изменение статуса','usam'), 'add' => __('Создание','usam'), 'delete' => __('Удаление черновиков','usam'), 'delete_any' => __('Удаление с любым статусом','usam'), 'export' => __('Экспорт','usam'), 'import' => __('Импорт','usam'), 'print' => __('Печать','usam')];
		$this->display_capability( $name_capability, $document_type );
	}
	
	public function display_document_settings( ) 
	{
		$name_capability = ['grid_document' => __('Просмотр документов политкой','usam'),'setting_document' => __('Просмотр настроек документов','usam'), 'map_document' => __('Документы на карте','usam'), 'report_document' => __('Отчеты по документам','usam')];
		$this->display_capability( $name_capability );
	}
		
	public function display_section_document() 
	{	
		$this->display_roles();
		usam_add_box( 'usam_document_settings', __('Общие возможности'), [$this, 'display_document_settings'] );
		foreach( usam_get_details_documents( ) as $type => $document )
		{		
			usam_add_box( 'usam_document_'.$type, $document['single_name'], [$this, 'display_document'], $type );	
		}		
	}
	
	public function display_section_marketplace() 
	{
		$this->display_roles();
		usam_add_box( 'usam_marketplace_settings', __('Общие возможности'), [$this, 'display_marketplace_settings'] );
	}
	
	public function display_marketplace_settings() 
	{
		$name_capability = ['seller_company' => __('Продавец компания','usam'), 'seller_contact' => __('Продавец физ. лицо','usam')];
		$this->display_capability( $name_capability );
	}	
	
	public function display_event( $type ) 
	{		
		$name_capability = ['view' => __('Просмотр','usam'), 'edit' => __('Изменение','usam'), 'edit_status' => __('Изменение статуса','usam'), 'add' => __('Создание','usam'), 'delete' => __('Удаление','usam')];
		$this->display_capability( $name_capability, $type );
	}	
	
	public function display_section_event() 
	{	
		$this->display_roles();	
		foreach( usam_get_events_types( ) as $type => $document )
		{		
			usam_add_box( 'usam_event_'.$type, $document['single_name'], [$this, 'display_event'], $type );	
		}		
	}
	
	public function display_crm_settings( )
	{ 
		$name_capability = [
			'view_communication_data' => __('Отображение телефонов и почты','usam'),
			'send_sms' => __('Отправка СМС','usam'), 
			'send_email' => __('Отправка почты','usam'),
			'sale' => __('Продажа','usam'),
			'view_departments' => __('Список отделов компании','usam'),
			'view_bonus_cards' => __('Список бонусных карт','usam'),
			'view_customer_accounts' => __('Список клиентских счетов','usam'),
			'monitoring_events' => __('Просмотр заданий и дел всех сотрудников','usam'), 
			'write_to_chat' => __('Администраторы чата','usam')
		];
		$this->display_capability( $name_capability );
	}
	
	public function display_interface_capabilities( ) 
	{
		$name_capability = ['list_crm' => __('Просмотр списком','usam'), 'grid_crm' => __('Просмотр плиткой','usam'), 'map_crm' => __('На карте','usam'), 'report_crm' => __('Просмотр отчетов','usam'), 'calendar_crm' => __('Календарь','usam'), 'setting_crm' => __('Просмотр настроек','usam')];
		$this->display_capability( $name_capability );
	}
	
	public function display_crm( $type ) 
	{
		$name_capability = ['edit' => __('Изменение','usam'), 'add' => __('Создание','usam'), 'delete' => __('Удаление','usam'), 'export' => __('Экспорт','usam'), 'import' => __('Импорт','usam')];
		$this->display_capability( $name_capability, $type );
	}
	
	public function display_section_crm() 
	{	
		$this->display_roles();
		usam_add_box( 'usam_crm_settings', __('Возможности интерфейса'), [$this, 'display_interface_capabilities'] );
		usam_add_box( 'usam_crm_settings', __('Общие возможности'), [$this, 'display_crm_settings'] );	
		foreach( ['contact' => __('Контакт', 'usam'), 'employee' => __('Сотрудник', 'usam'), 'department' => __('Отделы', 'usam'), 'company' => __('Компания', 'usam'), 'subscription' => __('Подписки', 'usam'), 'bonus_card' => __('Бонусные карты', 'usam'), 'customer_account' => __('Клиентские счета', 'usam')] as $type => $name )
		{		
			usam_add_box( 'usam_event_'.$type, $name, [$this, 'display_crm'], $type );	
		}		
	}
	
	public function display_section_menu() 
	{
		global $wp_roles;				
		$pages = usam_get_admin_menu();
		$page_tabs = usam_get_page_tabs();		
		unset($pages['options-general.php']);	
		unset($pages['index.php']);	
		unset($page_tabs['shop']);				
		$this->display_roles();
		foreach ( $pages as $key => $page )
		{	
			if ( isset($page['toplevel']) )
			{
				$capability = !empty($wp_roles->roles[$this->current_role]['capabilities'][$page['toplevel']['capability']]) ? 1 : 0;
				?>		
				<div class="menu_page usam_checked__item js-checked-item usam_checked-<?php echo esc_attr( $key ); ?> <?php echo $capability?'checked':''; ?>">
					<input type="hidden" name="capabilities[<?php echo $page['toplevel']['capability']; ?>]" value="0"/>
					<input type="checkbox" class="input-checkbox js-input-checkbox-<?php echo $page['toplevel']['capability']; ?>" name="capabilities[<?php echo $page['toplevel']['capability']; ?>]" <?php checked( $capability ); ?> value="1"/>
					<label><h4 class="menu_section"><?php echo esc_html( $page['toplevel']['page_title'] ); ?></h4></label>
				</div>					
				<?php
			}				
			if ( isset($page['submenu']) )
			{					
				?>	
				<div class="usam_checked">		
					<?php
				foreach ( $page['submenu'] as $key => $submenu )
				{										
					$capability = !empty($wp_roles->roles[$this->current_role]['capabilities'][$submenu['capability']]) ? 1 : 0;				
					?>		
					<div class="menu_page usam_checked__item js-checked-item usam_checked-<?php echo esc_attr( $key ); ?> <?php echo $capability?'checked':''; ?>">
						<input type="hidden" name="capabilities[<?php echo $submenu['capability']; ?>]" value="0"/>
						<input type="checkbox" class="input-checkbox js-input-checkbox-<?php echo $submenu['capability']; ?>" name="capabilities[<?php echo $submenu['capability']; ?>]" <?php checked( $capability ); ?> value="1"/>
						<label><?php echo esc_html( $submenu['page_title']." ( ".$submenu['menu_title']." ) "  ); ?></label>
					</div>		
					<?php
					if ( isset($page_tabs[$submenu['menu_slug']]) && $submenu['menu_slug'] != 'interface' )
					{ 
						foreach ( $page_tabs[$submenu['menu_slug']] as $key => $tab )
						{							
							$capability = !isset($tab['capability']) || !empty($wp_roles->roles[$this->current_role]['capabilities'][$tab['capability']]) ? 1 : 0;							
							?>		
							<div class="menu_tab usam_checked__item js-checked-item usam_checked-<?php echo esc_attr( $key ); ?> <?php echo $capability?'checked':''; ?>">
								<input type="hidden" name="capabilities[<?php echo $tab['capability']; ?>]" value="0"/>
								<input type="checkbox" class="input-checkbox" name="capabilities[<?php echo $tab['capability']; ?>]" <?php checked( $capability ); ?> value="1"/>
								<label><?php echo __("Вкладка","usam")." &laquo;".esc_html( $tab['title'] )."&raquo;"; ?></label>
							</div>		
							<?php
						}
					}
				}
				?>	
				</div>		
			<?php
			}
		}		
	}	
	
	public function display_capability( $capabilities, $type = '' ) 
	{		
		global $wp_roles;	
		$type = $type ? '_'.$type : '';
		foreach( $capabilities as $key => $title )
		{						
			$capability = !empty($wp_roles->roles[$this->current_role]['capabilities'][$key.$type]) ? 1 : 0;
			?>		
			<div class="menu_tab usam_checked__item js-checked-item usam_checked-<?php echo esc_attr( $key ); ?> <?php echo $capability?'checked':''; ?>">
				<input type="hidden" name="capabilities[<?php echo $key.$type; ?>]" value="0"/>
				<input type="checkbox" class="input-checkbox" name="capabilities[<?php echo $key.$type; ?>]" <?php checked( $capability ); ?> value="1"/>
				<label><?php echo $title; ?></label>
			</div>		
			<?php
		}	
	}
}