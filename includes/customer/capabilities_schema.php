<?php
class USAM_Roles
{
	public function __construct(  ) 
	{		
		global $wp_roles;	
					
		add_role('wholesale_buyer', __('Оптовый покупатель', 'usam'), array(
			'read' 						=> true,
			'edit_posts' 				=> false,
			'delete_posts' 				=> false
		));	
		add_role('partner', __('Партнер', 'usam'), array(
			'read' 						=> true,
			'edit_posts' 				=> false,
			'delete_posts' 				=> false
		));
		add_role('courier', __('Курьер', 'usam'), array(				
			'view_personnel'         => true,
			'store_section'          => true,		
			'view_delivery_documents' => true,	
			'view_delivery'          => true,	
			'view_shipped'           => true,
			'edit_status'            => true,
			'view_personnel'         => true,	
			'view_tasks'             => true,	
			
			'read' 			       => true,
			'edit_posts' 		   => false,
			'delete_posts' 		   => false
		));
		add_role('api', __('API', 'usam'), array(	
			'universam_api'  => true,	
			'read' 			=> true,
			'edit_product'   => true,
			'read_product'  => true, 
			'delete_product' => true,
			'edit_products' => true, 
			'edit_others_products' => true,
			'publish_products' => true, 
			'read_private_products' => true, 
			'delete_products' => true, 
			'delete_private_products' => true, 
			'delete_published_products' => true, 
			'delete_others_products' => true, 
			'edit_private_products' => true, 
			'edit_published_products' => true,
			'edit_posts' 		   => false,
			'delete_posts' 		   => false
		));	
		//remove_role('pickup_point_manager');
		$pickup_point_manager = [			
			'store_section'           => true,
			'view_orders'             => true,	
			'view_personnel'         => true,	
			'view_tasks'             => true,	
			'view_delivery_documents' => false,	
			'view_delivery'          => false,	
			'view_shipped'           => true,
			'view_payment'           => true,
			
			'read' 			         => true,	
			'edit_product'           => false,			
			'read_product'           => true,
			'delete_product'         => false,
			'edit_products'          => true,
			'print_product'          => true,
			
			'list_crm' => true,
			'grid_crm' => true,
			'map_crm' => true,			
			'calendar_crm' => true,			
			'report_crm' => true,
		];
		$pickup_point_manager = array_merge($pickup_point_manager, $this->get_rights_menu(['orders', 'personnel']) );
		$pickup_point_manager = array_merge($pickup_point_manager, $this->get_rights_documents(['order'], ['view', 'department_view', 'company_view', 'any_view', 'edit', 'add', 'print', 'edit_status', 'lists']) );
		$pickup_point_manager = array_merge($pickup_point_manager, $this->get_rights_documents(['payment'], ['view', 'add', 'print', 'edit_status', 'lists']) );
		$pickup_point_manager = array_merge($pickup_point_manager, $this->get_rights_documents(['shipped'], ['view', 'print', 'edit_status', 'lists']) );
		$pickup_point_manager = array_merge($pickup_point_manager, $this->get_rights_events(['convocation', 'task', 'project', 'closed_project']) );
		add_role('pickup_point_manager', __('Менеджер пункта выдачи', 'usam'), $pickup_point_manager);	

		$shop_manager = [
			'help_section'           => true,
			'services_section'       => true,	
				
			'sale'                   => true,
			'store_section'          => true,	
			'view_orders'            => true,
			'view_delivery'          => true,
			'view_shipped'           => true,
			'delete_shipped'         => true,	
			'edit_subscription'      => true,		
			'view_files'             => true,	
			'view_feedback'          => true,
			'view_crm'               => true,					
			'manage_prices'          => true,
			'view_inventory_control' => true,
			'view_interface'         => true,
			'view_exchange'          => true,
			'manage_discounts'       => true,
			'view_reports'           => true,
			'view_personnel'         => true,	
			'view_marketplace'       => true,			
			'view_bonus_cards'       => true,	
			'view_customer_accounts' => true,
			'view_communication_data' => true,		
			'view_showcases'         => true,				
			'view_carts'             => true,		
			
			'marketing_section'      => true,
			'view_marketing'         => true,
			'view_social_networks'   => true,
			'view_newsletter'        => true,	
			'view_seo'               => true,	
			
			'read'                   => true,
			'read_private_pages'     => true,
			'read_private_posts'     => true,
			'edit_users'             => true,		
			'edit_posts'             => true,
			'edit_pages'             => true,
			'edit_published_posts'   => true,
			'edit_published_pages'   => true,
			'edit_private_pages'     => true,
			'edit_private_posts'     => true,
			'edit_others_posts'      => true,
			'edit_others_pages'      => true,
			'publish_posts'          => true,
			'publish_pages'          => true,
			'delete_posts'           => true,
			'delete_pages'           => true,
			'delete_private_pages'   => true,
			'delete_private_posts'   => true,
			'delete_published_pages' => true,
			'delete_published_posts' => true,
			'delete_others_posts'    => true,
			'delete_others_pages'    => true,
			'manage_categories'      => true,
			'manage_links'           => true,
			'moderate_comments'      => true,
			'unfiltered_html'        => true,
			'upload_files'           => true,
			'export'                 => true,
			'import'                 => true,
			'list_users'             => true,			
			'edit_theme_options'     => true,				
			
		// Лицензионные договоры
			'edit_agreement'         => true,
			'read_agreement'         => true,
			'delete_agreement'       => true,
			'edit_agreements'        => true,	
		// Товары
			'edit_product' => true,
			'read_product' => true, 
			'delete_product' => true,
			'edit_products' => true, 
			'print_product' => true,
			'edit_others_products' => true,
			'publish_products' => true, 
			'read_private_products' => true, 
			'delete_products' => true, 
			'delete_private_products' => true, 
			'delete_published_products' => true, 
			'delete_others_products' => true, 
			'edit_private_products' => true, 
			'edit_published_products' => true,
			
			'manage_product_attribute' => true,
			'edit_product_attribute' => true,
			'delete_product_attribute' => true,
			'manage_product_category' => true,
			'edit_product_category' => true,
			'delete_product_category' => true,
			'manage_product_selection' => true,
			'edit_product_selection' => true,
			'delete_product_selection' => true,
			'manage_product_catalog' => true,
			'edit_product_catalog' => true,
			'delete_product_catalog' => true,		
			'grid_document' => true,
			'map_document' => true,
			'report_document' => true,
			'setting_document' => true,
			'list_crm' => true,
			'map_crm' => true,
			'calendar_crm' => true,	
			'grid_crm' => true,
			'report_crm' => true,
			'setting_crm' => true,	
		];
		$shop_manager = array_merge($shop_manager, $this->get_rights_documents() );
		$shop_manager = array_merge($shop_manager, $this->get_rights_events() );
		$shop_manager = array_merge($shop_manager, $this->get_rights_crm(['contact', 'company', 'subscription', 'bonus_card', 'customer_account'], ['edit', 'add', 'export', 'import']) );
	//	$shop_manager = array_merge($shop_manager, $this->get_rights_crm(['department','employee'], ['add']) );
		$shop_manager = array_merge($shop_manager, $this->get_rights_menu() );
		add_role('shop_manager', __('Менеджер магазина', 'usam'), $shop_manager);	

		$shop_crm = [
			'help_section'           => true,
			'services_section'       => true,	
				
			'sale'                   => true,
			'view_delivery'          => true,		
			'store_section'          => true,
			'view_orders'            => true,
			'edit_subscription'      => true,			
			'edit_order'             => true,		
			'edit_payment'           => true,
			'edit_shipped'           => true,	
			'view_files'             => true,	
			'view_feedback'          => true,
			'view_crm'               => true,	
			'view_inventory_control' => true,
			'view_interface'         => false,
			'view_exchange'          => false,
			'view_reports'           => true,
			'view_personnel'         => true,	
			'view_tasks'             => true,	
			'view_couriers'          => true,	
			'view_email'             => true,
						
			'marketing_section'      => false,
			'view_marketing'         => false,
			'view_social_networks'   => false,
			'view_newsletter'        => false,	
			'view_seo'               => false,	
			
			'read'                   => true,
			'read_private_pages'     => false,
			'read_private_posts'     => false,
			'edit_users'             => false,		
			'edit_posts'             => false,
			'edit_pages'             => false,
			'edit_published_posts'   => false,
			'edit_published_pages'   => false,
			'edit_private_pages'     => false,
			'edit_private_posts'     => false,
			'edit_others_posts'      => false,
			'edit_others_pages'      => false,
			'publish_posts'          => false,
			'publish_pages'          => false,
			'delete_posts'           => false,
			'delete_pages'           => false,
			'delete_private_pages'   => false,
			'delete_private_posts'   => false,
			'delete_published_pages' => false,
			'delete_published_posts' => false,
			'delete_others_posts'    => false,
			'delete_others_pages'    => false,
			'manage_categories'      => false,
			'manage_links'           => false,
			'moderate_comments'      => false,
			'unfiltered_html'        => false,
			'upload_files'           => false,
			'export'                 => false,
			'import'                 => false,
			'list_users'             => false,

			'print_product'          => false,
			'edit_product'           => true,
			'read_product'           => true,
			'edit_products'          => true,	
			
			'view_communication_data' => true,	
			'grid_document' => true,
			'map_document' => true,
			'report_document' => true,				
			'list_crm' => true,
			'map_crm' => true,
			'calendar_crm' => true,	
			'grid_crm' => true,
			'report_crm' => true,
			'setting_crm' => true,	
		];
		$types = usam_get_events_types( );
		unset($types['contacting']);
		$types = array_keys($types);
		$shop_crm = array_merge($shop_crm, $this->get_rights_documents() );
		$shop_crm = array_merge($shop_crm, $this->get_rights_events($types) );
		$shop_crm = array_merge($shop_crm, $this->get_rights_menu(['orders', 'delivery', 'crm', 'personnel', 'files', 'storage', 'reports']) );		
		$shop_crm = array_merge($shop_crm, $this->get_rights_crm(['contact', 'company'], ['edit', 'add', 'export', 'import']) );		
		add_role( 'shop_crm', __('Менеджер по продажам', 'usam'), $shop_crm);

		$marketer = [
			'help_section'           => true,
			'services_section'       => true,	
				
			'store_section'          => true,	
			'view_orders'            => true,	
			'edit_subscription'      => false,		
			'edit_order'             => false,		
			'edit_payment'           => false,
			'edit_shipped'           => false,	
			'view_files'             => true,	
			'view_feedback'          => true,
			'view_crm'               => true,					
			'manage_prices'          => false,
			'view_inventory_control'  => false,
			'view_interface'         => false,
			'view_exchange'          => false,
			'manage_discounts'       => false,
			'view_reports'           => true,
			'view_personnel'         => true,	
			'view_tasks'             => true,	
			'view_email'             => true,
			
			'marketing_section'      => true,
			'view_marketing'         => true,
			'view_social_networks'   => true,
			'view_newsletter'        => true,	
			'view_price_analysis'    => true,		
			'view_reputation'        => true,	
			'view_seo'               => false,	
			
			'read'                   => true,
			'read_private_pages'     => true,
			'read_private_posts'     => true,
			'edit_users'             => true,		
			'edit_posts'             => true,
			'edit_pages'             => true,
			'edit_published_posts'   => true,
			'edit_published_pages'   => true,
			'edit_private_pages'     => true,
			'edit_private_posts'     => true,
			'edit_others_posts'      => true,
			'edit_others_pages'      => true,
			'publish_posts'          => true,
			'publish_pages'          => true,
			'delete_posts'           => true,
			'delete_pages'           => true,
			'delete_private_pages'   => true,
			'delete_private_posts'   => true,
			'delete_published_pages' => true,
			'delete_published_posts' => true,
			'delete_others_posts'    => true,
			'delete_others_pages'    => true,
			'manage_categories'      => true,
			'manage_links'           => true,
			'moderate_comments'      => true,
			'unfiltered_html'        => true,
			'upload_files'           => true,
			'export'                 => false,
			'import'                 => false,
			'list_users'             => true,

			'edit_product'           => true,
			'read_product'           => true,
			'edit_products'          => true,
			
			'view_communication_data' => true,	
			'list_crm' => true,
			'map_crm' => true,
			'calendar_crm' => true,	
			'grid_crm' => true,
			'report_crm' => true,
			
			'send_sms' => true,
			'send_email' => true,
		];		
		$types = usam_get_events_types( );
		unset($types['contacting']);
		$types = array_keys($types);
		$marketer = array_merge($marketer, $this->get_rights_documents('all', ['view', 'department_view', 'company_view', 'any_view', 'lists']) );
		$marketer = array_merge($marketer, $this->get_rights_events($types) );
		$marketer = array_merge($marketer, $this->get_rights_crm(['contact', 'company', 'subscription', 'bonus_card', 'customer_account'], ['edit', 'add', 'export', 'import']) );
		$marketer = array_merge($marketer, $this->get_rights_menu(['orders', 'delivery', 'crm', 'personnel', 'files', 'marketing', 'social_networks', 'newsletter', 'reports']) );		
		add_role( 'marketer', __('Маркетолог', 'usam'), $marketer );

		$company_management = [			
			'help_section'           => true,
			'services_section'       => true,	
			
			'view_marketplace'       => true,
			'sale'                   => true,
			'personnel_management'   => true, // Руководство компанией
			'view_delivery'          => true,		
			'store_section'          => true,
			'view_orders'            => true,		
			'view_files'             => true,	
			'view_feedback'          => true,
			'view_crm'               => true,					
			'manage_prices'          => false,
			'view_inventory_control' => true,
			'view_interface'         => false,
			'view_exchange'          => false,
			'manage_discounts'       => false,
			'view_reports'           => true,
			'view_personnel'         => true,	
			'view_tasks'             => true,		
			'view_bookkeeping'       => true,				
			
			'marketing_section'      => true,
			'view_marketing'         => false,
			'view_social_networks'   => false,
			'view_newsletter'        => false,	
			'view_seo'               => false,	
			
			'read'                   => true,
			'read_private_pages'     => false,
			'read_private_posts'     => false,
			'edit_users'             => false,		
			'edit_posts'             => false,
			'edit_pages'             => false,
			'edit_published_posts'   => false,
			'edit_published_pages'   => false,
			'edit_private_pages'     => false,
			'edit_private_posts'     => false,
			'edit_others_posts'      => false,
			'edit_others_pages'      => false,
			'publish_posts'          => false,
			'publish_pages'          => false,
			'delete_posts'           => false,
			'delete_pages'           => false,
			'delete_private_pages'   => false,
			'delete_private_posts'   => false,
			'delete_published_pages' => false,
			'delete_published_posts' => false,
			'delete_others_posts'    => false,
			'delete_others_pages'    => false,
			'manage_categories'      => false,
			'manage_links'           => false,
			'moderate_comments'      => false,
			'unfiltered_html'        => false,
			'upload_files'           => false,
			'export'                 => false,
			'import'                 => false,
			'list_users'             => false,
			
			'edit_product'           => true,
			'read_product'           => true,
			'edit_products'          => true,	
			
			'view_communication_data' => true,	
			'list_crm' => true,
			'map_crm' => true,
			'calendar_crm' => true,	
			'grid_crm' => true,
			'report_crm' => true,
			'monitoring_events' => true,			
		];		
		$company_management = array_merge($company_management, $this->get_rights_events() );
		$company_management = array_merge($company_management, $this->get_rights_menu(['orders', 'delivery', 'crm', 'personnel', 'files', 'storage', 'marketing', 'social_networks', 'newsletter','reports']) );		
		$company_management = array_merge($company_management, $this->get_rights_documents('all', ['view', 'department_view', 'company_view', 'any_view', 'lists']) );			
		add_role( 'company_management', __('Руководство', 'usam'), $company_management);

		$personnel_officer = [
			'help_section'           => true,
			'services_section'       => true,	
				
			'store_section'          => true,
			'view_orders'            => false,	
			'edit_subscription'      => false,			
			'edit_order'             => false,		
			'edit_payment'           => false,
			'edit_shipped'           => false,	
			'view_files'             => true,	
			'view_feedback'          => true,
			'view_email'             => true,
			'view_crm'               => false,					
			'manage_prices'          => false,
			'view_inventory_control' => false,
			'view_interface'         => false,
			'view_exchange'          => false,
			'manage_discounts'       => false,
			'view_reports'           => false,
			'view_personnel'         => true,	
			'view_tasks'             => true,	
			
			'marketing_section'      => false,
			'view_marketing'         => false,
			'view_social_networks'   => false,
			'view_newsletter'        => false,	
			'view_seo'               => false,			
				
			'read'                   => true,
			'read_private_pages'     => false,
			'read_private_posts'     => false,
			'edit_users'             => false,		
			'edit_posts'             => false,
			'edit_pages'             => false,
			'edit_published_posts'   => false,
			'edit_published_pages'   => false,
			'edit_private_pages'     => false,
			'edit_private_posts'     => false,
			'edit_others_posts'      => false,
			'edit_others_pages'      => false,
			'publish_posts'          => false,
			'publish_pages'          => false,
			'delete_posts'           => false,
			'delete_pages'           => false,
			'delete_private_pages'   => false,
			'delete_private_posts'   => false,
			'delete_published_pages' => false,
			'delete_published_posts' => false,
			'delete_others_posts'    => false,
			'delete_others_pages'    => false,
			'manage_categories'      => false,
			'manage_links'           => false,
			'moderate_comments'      => false,
			'unfiltered_html'        => false,
			'upload_files'           => false,
			'export'                 => false,
			'import'                 => false,
			'list_users'             => false,

			'list_crm' => true,
			'map_crm' => true,
			'calendar_crm' => true,	
			'grid_crm' => true,
			'report_crm' => true,	
			'monitoring_events' => true,	
			'view_departments' => true,				
		];
		$personnel_officer = array_merge($personnel_officer, $this->get_rights_crm(['contact', 'company', 'department', 'employee'], ['edit', 'add', 'export', 'import']) );
		$personnel_officer = array_merge($personnel_officer, $this->get_rights_events(['convocation','task','project','closed_project'], ['view', 'edit', 'add', 'edit_status']) );
		$personnel_officer = array_merge($personnel_officer, $this->get_rights_menu(['crm', 'personnel', 'files']) );		
		add_role( 'personnel_officer', __('Кадровый работник', 'usam'), $personnel_officer);	
				
		$employee = [
			'help_section'           => true,
			'services_section'       => true,	
			
			'store_section'          => true,
			'view_orders'            => false,	
			'edit_subscription'      => false,			
			'edit_order'             => false,		
			'edit_payment'           => false,
			'edit_shipped'           => false,	
			'view_files'             => true,	
			'view_feedback'          => true,
			'view_email'             => true,	
			'view_crm'               => false,					
			'manage_prices'          => false,
			'view_inventory_control' => false,
			'view_interface'         => false,
			'view_exchange'          => false,
			'manage_discounts'       => false,
			'view_reports'           => false,
			'view_personnel'         => true,	
			'view_tasks'             => true,		
			
			'marketing_section'      => false,
			'view_marketing'         => false,
			'view_social_networks'   => false,
			'view_newsletter'        => false,	
			'view_seo'               => false,	
			
			'read'                   => true,
			'read_private_pages'     => false,
			'read_private_posts'     => false,
			'edit_users'             => false,		
			'edit_posts'             => false,
			'edit_pages'             => false,
			'edit_published_posts'   => false,
			'edit_published_pages'   => false,
			'edit_private_pages'     => false,
			'edit_private_posts'     => false,
			'edit_others_posts'      => false,
			'edit_others_pages'      => false,
			'publish_posts'          => false,
			'publish_pages'          => false,
			'delete_posts'           => false,
			'delete_pages'           => false,
			'delete_private_pages'   => false,
			'delete_private_posts'   => false,
			'delete_published_pages' => false,
			'delete_published_posts' => false,
			'delete_others_posts'    => false,
			'delete_others_pages'    => false,
			'manage_categories'      => false,
			'manage_links'           => false,
			'moderate_comments'      => false,
			'unfiltered_html'        => false,
			'upload_files'           => false,
			'export'                 => false,
			'import'                 => false,
			'list_users'             => false,

			'view_communication_data' => true,	
			'list_crm' => true,
			'map_crm' => true,
			'calendar_crm' => true,	
			'grid_crm' => true,
			'report_crm' => true,			
		];
		$employee = array_merge($employee, $this->get_rights_events(['convocation','task','project','closed_project']) );
		$employee = array_merge($employee, $this->get_rights_documents('all', ['view', 'department_view', 'company_view', 'any_view', 'lists']) );
		$employee = array_merge($employee, $this->get_rights_menu(['orders', 'crm', 'personnel', 'files']) );		
		add_role( 'employee', __('Сотрудник', 'usam'), $employee);

		$shop_seo = [
			'marketing_section'      => true,
			'view_seo'               => true,
			'view_seo_setting'       => true,	
			
			'help_section'           => true,
			'services_section'       => true,	
			
			'store_section'          => false,
			'view_orders'            => false,		
			'edit_subscription'      => false,			
			'edit_order'             => false,		
			'edit_payment'           => false,
			'edit_shipped'           => false,			
			'read'                   => true,
			'read_private_pages'     => true,
			'read_private_posts'     => true,
			'edit_users'             => true,		
			'edit_posts'             => true,
			'edit_pages'             => true,
			'edit_published_posts'   => true,
			'edit_published_pages'   => true,
			'edit_private_pages'     => true,
			'edit_private_posts'     => true,
			'edit_others_posts'      => true,
			'edit_others_pages'      => true,
			'publish_posts'          => true,
			'publish_pages'          => true,
			'delete_posts'           => false,
			'delete_pages'           => false,
			'delete_private_pages'   => false,
			'delete_private_posts'   => false,
			'delete_published_pages' => false,
			'delete_published_posts' => false,
			'delete_others_posts'    => false,
			'delete_others_pages'    => false,			
			'manage_categories'      => true,
			'manage_links'           => true,
			'moderate_comments'      => true,
			'unfiltered_html'        => true,
			'upload_files'           => true,
			'export'                 => false,
			'import'                 => false,
			'list_users'             => false,

			'edit_product'           => true,
			'read_product'           => true,
			'edit_products'          => true,	
		];		
		$shop_seo = array_merge($shop_seo, $this->get_rights_menu(['crm']) );
		$shop_seo = array_merge($shop_seo, $this->get_rights_menu(['seo']) );			
		add_role( 'shop_seo', __('SEO специалист', 'usam'), $shop_seo);
		
		if ( get_option('usam_website_type', 'store' ) == 'marketplace' )
		{			
			$seller = [				
			//	'store_section'          => true,	
			//	'view_orders'            => true,				
				
				'help_section'           => true,
				'services_section'       => true,	
				
				'edit_shipped'           => false,	
				'view_shipped_documents' => false,	
				'view_delivery_documents' => false,
				'seller_company' => true,				
				
				'read'                   => true,
				'read_private_pages'     => false,
				'read_private_posts'     => true,
				'edit_users'             => false,		
				'edit_posts'             => true,
				'edit_pages'             => false,
				'edit_published_posts'   => true,
				'edit_published_pages'   => false,
				'edit_private_pages'     => false,
				'edit_private_posts'     => false,
				'edit_others_posts'      => false,
				'edit_others_pages'      => false,
				'publish_posts'          => false,
				'publish_pages'          => false,
				'delete_posts'           => true,
				'delete_pages'           => false,
				'delete_private_pages'   => false,
				'delete_private_posts'   => false,
				'delete_published_pages' => false,
				'delete_published_posts' => false,
				'delete_others_posts'    => false,
				'delete_others_pages'    => false,
				'manage_categories'      => false,
				'manage_links'           => false,
				'moderate_comments'      => false,
				'unfiltered_html'        => true,
				'upload_files'           => true,
				'export'                 => false,
				'import'                 => false,								
			
				'edit_product'           => true,
				'read_product'           => true,
				'delete_product'         => true,		
				'edit_products'          => true,
				'delete_products'        => true,
				'publish_products'       => false,//отправить на рассмотрение	
				'edit_product_attribute' => false,					
			];	
			//remove_role( 'seller' ); 			
			add_role( 'seller', __('Продавец', 'usam'), $seller);
		}
		else
			remove_role( 'seller' );

		$capabilities = array( 	
			'grid_document' => ['administrator'],
			'map_document' => ['administrator'],
			'report_document' => ['administrator'],
			'setting_document' => ['administrator'],
						
			'help_section' => ['administrator'],
			'services_section' => ['administrator'],
			
			'list_crm' => ['administrator'],
			'map_crm' => ['administrator'],
			'calendar_crm' => ['administrator'],
			'grid_crm' => ['administrator'],
			'report_crm' => ['administrator'],
			'setting_crm' => ['administrator'],
						
			'store_section'     => ['administrator'],	// Раздел бизнес
			'marketing_section' => ['administrator'],
			'view_marketplace'  => ['administrator'],
			
			'view_seo_setting'  => ['administrator'],					
			'universam_settings'=> ['administrator'],	 //Настройки универсам
			'shop_tools'        => ['administrator'],
			'edit_subscription' => ['administrator'],
			
			'view_bonus_cards'   => ['administrator'],
			'view_customer_accounts' => ['administrator'],
			'view_carts'   => ['administrator'],

		// Лицензионные договоры	
			'edit_agreement'   => array( 'administrator' ),		
			'read_agreement' => array( 'administrator' ),		
			'delete_agreement'   => array( 'administrator' ),
			'edit_agreements'   => array( 'administrator' ),
			
			'edit_product'   => array( 'administrator' ),		
			'read_product' => array( 'administrator' ),		
			'delete_product'   => array( 'administrator' ),
			'edit_products'   => array( 'administrator' ),	
			'edit_others_products'   => array( 'administrator' ),		
			'publish_products' => array( 'administrator' ),		
			'read_private_products'   => array( 'administrator' ),
			'delete_products'   => array( 'administrator' ),
			'delete_private_products'   => array( 'administrator' ),		
			'delete_published_products' => array( 'administrator' ),		
			'delete_others_products'   => array( 'administrator' ),
			'edit_private_products'   => array( 'administrator' ),
			'edit_published_products'   => array( 'administrator' ),
			
			'manage_product_attribute' => ['administrator'],
			'edit_product_attribute' => ['administrator'],	
			'delete_product_attribute' => ['administrator'],
			'manage_product_category' => ['administrator'],
			'edit_product_category' => ['administrator'],
			'delete_product_category' => ['administrator'],
			'manage_product_selection' => ['administrator'],
			'edit_product_selection' => ['administrator'],
			'delete_product_selection' => ['administrator'],
			'manage_product_catalog' => ['administrator'],
			'edit_product_catalog' => ['administrator'],
			'delete_product_catalog' => ['administrator'],
			
			'view_applications' => ['administrator'],
			'view_all_applications' => ['administrator'],
			'view_installed_applications' => ['administrator'],
			'view_departments' => ['administrator'],			
			
			'print_product' => array('administrator'),
			'sale'          => array('administrator'),
			'send_sms'      => array('administrator'),
			'send_email'    => array('administrator'),
			
			'view_showcases'    => ['administrator'],
			'view_communication_data' => ['administrator'],
			'applications_section' => ['administrator'],
		);		
		foreach ( $capabilities as $capability_id => $roles ) 
		{	
			foreach ( $wp_roles->role_objects as $wp_role ) 
			{				
				if ( in_array($wp_role->name, $roles) && !$wp_role->has_cap( $capability_id ) )
						$wp_role->add_cap( $capability_id );
			}
		}
		$rights = [];
		$rights = array_merge($rights, $this->get_rights_crm() );
		$rights = array_merge($rights, $this->get_rights_events() );
		$rights = array_merge($rights, $this->get_rights_documents() );			
		$rights = array_merge($rights, $this->get_rights_menu() );	
		$role_administrator = get_role( 'administrator' );		
		foreach ( $rights as $right => $b )
		{			
			if ( !$role_administrator->has_cap( $right ) )
				$role_administrator->add_cap( $right );
		}
	}
	
	public function get_rights_menu( $types = 'all' ) 
	{			
		$results = [];
		require_once( USAM_FILE_PATH. '/admin/includes/admin_menu.class.php' );		
		$pages = usam_get_admin_menu();
		$page_tabs = usam_get_page_tabs();		
		foreach ( $pages as $page ) 
		{	
			if ( isset($page['submenu']) )
			{			
				foreach ( $page['submenu'] as $key => $submenu )
				{						
					if ( $types == 'all' || in_array($submenu['menu_slug'], $types) )
					{
						foreach ( $page_tabs[$submenu['menu_slug']] as $tab )
						{
							if ( isset($tab['capability']) )
							{
								$results[$tab['capability']] = true;
								$results[$submenu['capability']] = true;
							}							
						}
					}
				}
			}
		}	
		return $results;
	}
		
	//Контакты, компании и сотрудники				
	public function get_rights_crm( $types = 'all', $rights = 'all' ) 
	{		
		$rights = $rights=='all'?['edit', 'delete', 'add', 'export', 'import']:$rights;		
		$results = [];		
		foreach(['contact', 'employee', 'company', 'department', 'subscription', 'bonus_card', 'customer_account'] as $type )
		{	
			if ( $types == 'all' || in_array($type, $types) )
			{
				foreach( $rights as $right )
					$results[$right.'_'.$type] = true;
			}
		}		
		return $results;
	}
		
	//Дела, задания, события
	public function get_rights_events( $types = 'all', $rights = 'all' ) 
	{		
		$rights = $rights=='all'?['view', 'edit', 'delete', 'add', 'edit_status']:$rights;		
		$results = [];
		foreach( usam_get_events_types( ) as $type => $event )
		{	
			if ( $types == 'all' || in_array($type, $types) )
			{
				foreach( $rights as $right )
					$results[$right.'_'.$type] = true;
			}
		}		
		return $results;
	}
		
	public function get_rights_documents( $documents = 'all', $rights = 'all' ) 
	{
		$rights = $rights=='all'?['view', 'department_view', 'company_view', 'any_view', 'edit', 'department_edit', 'company_edit', 'any_edit', 'delete', 'add', 'export', 'print', 'edit_status', 'lists']:$rights;			
		$results = [];
		foreach( usam_get_details_documents( ) as $type => $document )
		{	
			if ( $documents == 'all' || in_array($type, $documents) )
			{
				foreach( $rights as $right )
				{
					if ( $right == 'lists' )
						$results['view_'.$type.'_lists'] = true;
					else
						$results[$right.'_'.$type] = true;
				}				
			}
		}		
		return $results;
	}	
}
new USAM_Roles();