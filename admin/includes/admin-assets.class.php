<?php
/**
 * Загрузка стилей и скриптов.
 */

class USAM_Admin_Assets 
{	
	public function __construct() 
	{			
		add_action( 'admin_enqueue_scripts', array( $this, 'init' ), -9999999 );	
		add_action( 'admin_head-media-upload-popup', array( $this, 'address_book' ), 3 );	
		add_filter( 'script_loader_tag', array( $this, 'mihdan_add_defer_attribute' ), 10, 2 );			
	}
	
	function mihdan_add_defer_attribute( $tag, $handle ) 
	{    
		$handles = array(
			'usam-sort_fields',			
			'usam-sortable-table',			
			'usam-set_thumbnails',
			'usam-selecting_locations',
			'usam-term',
			'edit-data-admin',
		); 
		foreach( $handles as $defer_script) 
		{
			if ( $defer_script === $handle ) 
			{
				return str_replace( ' src', ' defer="defer" src', $tag );
			}
		}
		return $tag;
	}		
	
	public function address_book( $pagehook ) 
	{
		wp_enqueue_style( 'usam-address_book' );		
	}
		
	public function init( $pagehook ) 
	{				
		$this->register( $pagehook );		
		$this->scripts_and_style( $pagehook);				
	}
	
	private function register( $pagehook ) 
	{					
		$styles = [
			'usam-admin-taxonomies' => ['file_name' => 'taxonomies.css', 'deps' => [], 'media' => 'all'],		
			'usam-form' => ['file_name' => 'form.css', 'deps' => [], 'media' => 'all'],
			'usam-element-form' => ['file_name' => 'element_form.css', 'deps' => [], 'media' => 'all'], // Стиль форм элементов
			'usam-admin-silder' => ['file_name' => 'silder.css', 'deps' => [], 'media' => 'all'],
			'usam-order-admin' => array( 'file_name' => 'order.css', 'deps' => [], 'media' => 'all' ),
			'usam-admin' => ['file_name' => 'admin.css', 'deps' => [], 'media' => 'all'],	
			'usam-address_book' => array( 'file_name' => 'address_book.css', 'deps' => [], 'media' => 'all' ),	
			'usam-progress-form' => array( 'file_name' => 'progress-form.css', 'deps' => [], 'media' => 'all' ),		
			'usam-calendar' => array( 'file_name' => 'calendar.css', 'deps' => [], 'media' => 'all' ),				
		];
		foreach( $styles as $name => $style )
		{
			wp_register_style( $name, USAM_URL . '/admin/assets/css/'.$style['file_name'], $style['deps'], USAM_VERSION_ASSETS, $style['media'] );	
		}		
		$scripts = array( 			
			'usam-importer' => array( 'file_name' => 'import_process.js', 'deps' => ['vue'], 'in_footer' => false ),
			'usam-sort_fields' => array( 'file_name' => 'sort_fields.js', 'deps' => array( 'jquery-query' ), 'in_footer' => false ),// Сортировка полей		
			'usam-sortable-table' => ['file_name' => 'sortable-table.js', 'deps' => array('jquery-query'), 'in_footer' => false],          // Сортировка полей
			'usam-set_thumbnails' => array( 'file_name' => 'set_thumbnails.js', 'deps' => array('jquery', 'usam-admin'), 'in_footer' => false ),   //Установка миниатюр     
			'usam-admin' => ['file_name' => 'admin.js', 'deps' => ['jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'vue', 'v-mask', 'universam'], 'in_footer' => true],   	
			'usam-mail-editor' => array( 'file_name' => 'mail-editor.js', 'deps' => array('jquery', 'jquery-ui-sortable', 'jquery-ui-droppable'), 'in_footer' => false ),  
			'usam-selecting_locations' => array( 'file_name' => 'selecting_locations.js', 'deps' => array('jquery', 'usam-admin'), 'in_footer' => false ), 		
			'usam-term' => ['file_name' => 'taxonomy/taxonomy.js', 'deps' => array('jquery', 'usam-admin'), 'in_footer' => false], 
			'edit-data-admin' => array( 'file_name' => 'edit_data.js', 'deps' => array('jquery'), 'in_footer' => false ),			
			'usam-calendar' => array( 'file_name' => 'calendar.js', 'deps' => array('jquery'), 'in_footer' => true ),			
			'wp-tinymce' => array( 'file_name' => includes_url( 'js/tinymce' )."/tinymce.min.js", 'deps' => array('jquery'), 'in_footer' => false ),	
			'usam-basket_conditions' => array( 'file_name' => 'basket_conditions.js', 'deps' => array('jquery' ), 'in_footer' => true ),		
			'usam-post-meta-tags' => array( 'file_name' => 'post-meta-tags.js', 'deps' => ['vue'], 'in_footer' => true ),				
		);				
		foreach( $scripts as $name => $script )
		{
			wp_register_script( $name, USAM_URL . '/admin/assets/js/'.$script['file_name'], $script['deps'], USAM_VERSION_ASSETS, $script['in_footer'] );	
		}
	}	

	public function scripts_and_style( $pagehook ) 
	{		
		global $post; 		
		$screen = get_current_screen();	
								
	//	wp_enqueue_script( 'babel-standalone');
	//	wp_enqueue_script( 'require');
	//  wp_enqueue_script( 'qs');
	//	wp_enqueue_script( 'axios');			
		
		
	//	wp_enqueue_script( 'vue-global');						
	//	wp_enqueue_script( 'vue-browser');	
			
		wp_enqueue_script('vue');	
		wp_enqueue_script('vue-demi');
		wp_enqueue_script('pinia');	
		wp_enqueue_script('v-mask');
		wp_enqueue_script('universam' );			
				
	//	wp_enqueue_script( 'v-calendar');
	
		wp_enqueue_style( 'usam-form' );		
		wp_enqueue_script( 'chosen' );			
		wp_enqueue_script( 'usam-tab' );			

		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'v-calendar' );		
		
		wp_localize_script( 'usam-term', 'USAM_Taxonomy', [				
			'set_taxonomy_order_nonce' => usam_create_ajax_nonce( 'set_taxonomy_order' ),	
			'set_taxonomy_thumbnail_nonce' => usam_create_ajax_nonce( 'set_taxonomy_thumbnail' ),	
		]);					
		$args = [
			'dragndrop'                => isset($_GET['orderby']) && strpos($_GET['orderby'], 'menu_order')!== false?true:false,
			'bulk_actions_terms'       => usam_create_ajax_nonce( 'bulk_actions_terms' ), // Перенести термин		
			'bulk_actions_product_attribute' => usam_create_ajax_nonce( 'bulk_actions_product_attribute' ), 
			'bulk_actions_system_product_attribute' => usam_create_ajax_nonce( 'bulk_actions_system_product_attribute' ), 
			'update_featured_products'   => usam_create_ajax_nonce( 'update_featured_products' ), 
			'empty_trash_post'           => usam_create_ajax_nonce( 'empty_trash_post' ), 
			'bulkactions_nonce'          => usam_create_ajax_nonce( 'bulkactions' ),		
			'set_products_thumbnail_nonce' => usam_create_ajax_nonce( 'set_products_thumbnail' ), 		
			'get_modal'                    => usam_create_ajax_nonce( 'get_modal' ), 			
			'current_page'                 => empty($_REQUEST['page']) ? '' : sanitize_title($_REQUEST['page']),			
			'save_nav_menu_metaboxes_nonce'  => usam_create_ajax_nonce( 'save_nav_menu_metaboxes' ),
			'usam_multisite'            => usam_is_multisite() && !is_main_site(),			
			'screen_id'                 => !empty($screen)?$screen->id:0,
			'notice_delete'       => __('Удалено', 'usam'),
			'notice_save'       => __('Сохранено', 'usam'),
			'notice_send'       => __('Сообщение отправлено', 'usam'),
			'notice_not_send'   => __('Сообщение не отправлено', 'usam'),
			'notice_add_event'   => __('Задача добавлена', 'usam'),
			'notice_not_add_event'   => __('Задача не добавлена', 'usam'),
			'notice_ready'   => __('Выполнено', 'usam'),
			'notice_not_ready'   => __('Не выполнено', 'usam'),
		];
		if ( !usam_is_multisite() || is_main_site() )
		{
			$args['action_message'] = ['import_products' => __('Импорт', 'usam'), 'export_products' => __('Экспорт', 'usam')];
			if ( current_user_can('import') )
				$args['action_urls']['import_products'] = admin_url('admin.php?page=exchange&tab=product_importer');
			if ( current_user_can('import') )
				$args['action_urls']['export_products'] = admin_url('admin.php?page=exchange&tab=product_exporter');	
		}			
		wp_enqueue_script( 'usam-admin' );			
		wp_localize_script( 'usam-admin', 'USAM_Admin', $args);
		wp_enqueue_style( 'usam-admin');
		wp_enqueue_style( 'datetimepicker' );
		wp_enqueue_script( 'bootstrap' );
		if ( isset($screen->id) )
		{
			switch ( $screen->id ) 
			{						
				case 'dashboard':				
					wp_enqueue_style( 'usam-dashboard', USAM_URL .'/admin/assets/css/dashboard.css', false, USAM_VERSION_ASSETS, 'all' );	
				break;	
				case 'usam-product':					
					wp_enqueue_script( 'knob' );				
					wp_enqueue_script( 'iframe-transport' );	
					wp_enqueue_script( 'fileupload' );				
					wp_enqueue_style( 'usam-product-metabox', USAM_URL . '/admin/assets/css/product-metabox.css', false, USAM_VERSION_ASSETS, 'all' );	
					wp_enqueue_script( 'usam-edit-product', USAM_URL . '/admin/assets/js/edit_product.js', array( 'jquery' ), USAM_VERSION_ASSETS );						
					wp_localize_script( 'usam-edit-product', 'USAM_Edit_Product', array(
						'product_id' => isset($_GET['post'])?$_GET['post']:0,
						'loading_information_nonce' => usam_create_ajax_nonce( 'loading_information' ),					
						'save_variant_set_table_product_nonce' => usam_create_ajax_nonce( 'save_variant_set_table_product' ),							
						'message_no_link' => __('Ссылка не указана', 'usam'),		
						'message_upload_file' => __('Скачать', 'usam'),			
						'text_published' => __('Опубликовано', 'usam'),	
						'text_no_published' => __('Не опубликовано', 'usam'),	
						'button_text' => __('Установить миниатюру', 'usam'), 
						'set_variation_thumbnail_nonce' => usam_create_ajax_nonce( 'set_variation_thumbnail' ),						
					));							
				break;						
				case 'edit-usam-product':
					wp_enqueue_media();
					wp_enqueue_script('usam-products_list', USAM_URL . '/admin/assets/js/products_list.js', array( 'jquery' ), USAM_VERSION_ASSETS);	
					$args = [			
						'get_products_table' => usam_create_ajax_nonce('get_products_table'), 				
						'screen_id'          => !empty($screen)?$screen->id:0,	
					];
					wp_localize_script('usam-products_list', 'USAM_Products', $args);					
				break;		
				case 'edit-category':		
					wp_enqueue_script( 'usam-sortable-table' );						
					wp_enqueue_script( 'usam-term' );			
				break;
				case 'edit-usam-category':		
					wp_enqueue_script( 'usam-sortable-table' );	
					wp_enqueue_style( 'usam-admin-taxonomies');	
					wp_enqueue_style( 'usam-progress-form' );							
					wp_enqueue_script( 'usam-term' );		
				break;
				case 'edit-usam-product_attributes':			
					wp_enqueue_script( 'usam-sortable-table' );
					wp_enqueue_style( 'usam-admin-taxonomies');		
					wp_enqueue_script( 'usam-product_attributes', USAM_URL . '/admin/assets/js/taxonomy/product_attributes.js', array( 'jquery' ), USAM_VERSION_ASSETS  );
					wp_localize_script( 'usam-product_attributes', 'USAM_Product_Attributes',					
						array(
						'term_id'  => !empty($_GET['tag_ID'])?$_GET['tag_ID']:0,
						'display_category_in_attributes_product_nonce'  => usam_create_ajax_nonce( 'display_category_in_attributes_product' ),
						'add_category_in_attributes_product_nonce'  => usam_create_ajax_nonce( 'add_category_in_attributes_product' ),
						'delete_category_in_attributes_product_nonce'  => usam_create_ajax_nonce( 'delete_category_in_attributes_product' ),				
						'text_delete' => __('Удалить', 'usam'),								
						)
					);		
					wp_enqueue_script( 'usam-term' );	
				break;			
				case 'edit-usam-variation':						
					wp_enqueue_script( 'usam-product_variation', USAM_URL . '/admin/assets/js/taxonomy/product_variation.js', array( 'jquery' ), USAM_VERSION_ASSETS  );							
					wp_localize_script( 'usam-product_variation', 'USAM_Variation', array(
						'variant_management_nonce' => usam_create_ajax_nonce( 'variant_management' ),
					));	
					wp_enqueue_script( 'usam-sortable-table' );				
					wp_enqueue_style( 'usam-admin-taxonomies');

					wp_enqueue_script( 'usam-term' );	
						
					wp_enqueue_script( 'wp-color-picker' );
					wp_enqueue_style( 'wp-color-picker' );			
				break;	
				case 'edit-usam-brands':
					wp_enqueue_script( 'usam-sortable-table' );				
					wp_enqueue_style( 'usam-admin-taxonomies');
					wp_enqueue_style( 'usam-progress-form' );	
			
					wp_enqueue_script( 'usam-term' );									
				break;	
				case 'edit-usam-selection':
				case 'edit-product_tag':
				case 'edit-usam-category_sale':
				case 'edit-usam-catalog':
					wp_enqueue_script( 'usam-sortable-table' );				
					wp_enqueue_style( 'usam-admin-taxonomies');
			
					wp_enqueue_script( 'usam-term' );									
				break;							
				case 'usam-product-variations-iframe':			// Фрейм вариаций на странице редактирования товара
					wp_enqueue_media();
					
					$product_id = !empty($_REQUEST['product_id'])?absint($_REQUEST['product_id']):0;
					wp_enqueue_style( 'usam-product-metabox', USAM_URL . '/admin/assets/css/product-metabox.css', false, USAM_VERSION_ASSETS, 'all' );	
					wp_enqueue_script( 'usam-product-variations', USAM_URL . '/admin/assets/js/product-variations.js', array( 'jquery', 'media-views' ), USAM_VERSION_ASSETS, true );
					wp_localize_script( 'usam-product-variations', 'USAM_Product_Variations', array(
						'product_id'          => $product_id,
						'add_variation_nonce' => usam_create_ajax_nonce( 'add_variation' ),				
					) );					
					wp_enqueue_script( 'usam-edit-product', USAM_URL . '/admin/assets/js/edit_product.js', array( 'jquery' ), USAM_VERSION_ASSETS );
					wp_localize_script( 'usam-edit-product', 'USAM_Edit_Product', array(
						'product_id' => $product_id,
						'bulkactions_nonce' => usam_create_ajax_nonce( 'bulkactions' ),		
						'save_variation_nonce' => usam_create_ajax_nonce( 'save_variation' ),								
						'get_list_table_nonce' => usam_create_ajax_nonce( 'get_list_table' ),							
						'save_variant_set_table_product_nonce' => usam_create_ajax_nonce( 'save_variant_set_table_product' ),							
						'message_no_link' => __('Ссылка не указана', 'usam'),						
					));				
				break;					
				case 'media-upload-popup':					
					if ( !empty($_REQUEST['post_id']) )
					{
						$post = get_post( absint($_REQUEST['post_id']) );
						if ( $post->post_type == 'usam-product' && $post->post_parent ) 
						{ // Установка фотографий для вариаций 
							wp_enqueue_script( 'set-post-thumbnail' );							
						}
					}
				break;											
				case 'feedback_reviews_table':				
					wp_enqueue_script('edit-data-admin');
				break;					
			}
			$post_types = get_post_types(['public' => true], 'objects');
			foreach( $post_types as $post_type ) 
			{
				if ( $screen->id == $post_type->name )
				{
					wp_enqueue_script('usam-post-meta-tags');
					break;
				}
			}
		}
		if ( !empty($_REQUEST['page']) && $_REQUEST['page'] == 'reports' )	
		{ 
			USAM_Admin_Assets::set_graph();
		}		
	}	
	
	public static function set_graph( ) 
	{	
		$anonymous_function = function() 
		{ 
			wp_enqueue_script('d3');					
			wp_enqueue_script( 'usam-graph' );	
		};
		add_action('admin_footer', $anonymous_function );	
	}
	
	// Установка миниатюры
	public static function set_thumbnails( ) 
	{			
		wp_enqueue_script( 'usam-set_thumbnails');					
		wp_enqueue_media();		
		wp_localize_script( 'usam-set_thumbnails', 'USAM_Thumbnail', ['no_image' => USAM_CORE_IMAGES_URL . '/no-image-uploaded-100x100.png']);			
	}
	
	public static function sort_fields( $action ) 
	{		
		$anonymous_function = function() use ( $action ) 
		{ 
			wp_enqueue_script( 'usam-sortable-table' );	
			wp_enqueue_script( 'jquery-ui-draggable' );	
			wp_enqueue_script( 'jquery-ui-droppable' );	
			wp_enqueue_script( 'usam-sort_fields' );
			wp_localize_script( 'usam-sort_fields', 'USAM_Sort', ['sort_fields_nonce' => usam_create_ajax_nonce( "update_{$action}_sort_fields" ), 'page' => $action]);
		};
		add_action('admin_footer', $anonymous_function );		
	}
	
	public static function work_email( ) 
	{		
		wp_enqueue_script( 'usam-work-email', USAM_URL . '/admin/assets/js/work-email.js', array( 'jquery-query' ), USAM_VERSION_ASSETS );
		wp_localize_script( 'usam-work-email', 'USAM_Work_Email', array(						
			'change_importance_email_nonce'       => usam_create_ajax_nonce( 'change_importance_email' ),		
			'spam_email_nonce'                    => usam_create_ajax_nonce( 'spam_email' ),
			'display_email_message_nonce'         => usam_create_ajax_nonce( 'display_email_message' ),	
			'display_email_form_nonce'            => usam_create_ajax_nonce( 'display_email_form' ),				
			'previous_email_message_nonce'        => usam_create_ajax_nonce( 'previous_email_message' ),	
			'delete_message_email_nonce'          => usam_create_ajax_nonce( 'delete_message_email' ),
			'not_read_message_email_nonce'        => usam_create_ajax_nonce( 'not_read_message_email' ),	
			'add_contact_from_email_nonce'        => usam_create_ajax_nonce( 'add_contact_from_email' ),				
			'read_message_email_nonce'            => usam_create_ajax_nonce( 'read_message_email' ),	
			'next_email_message_nonce'            => usam_create_ajax_nonce( 'next_email_message' ),	
			'add_email_object_nonce'              => usam_create_ajax_nonce( 'add_email_object' ),	
		));
	}
	
	public static function basket_conditions( ) 
	{	
		$anonymous_function = function() 
		{ 
			wp_enqueue_script( 'usam-basket_conditions' );
			wp_localize_script( 'usam-basket_conditions', 'Basket_Conditions',					
				[
				'text_and'    => __('И','usam'),
				'text_or'     => __('ИЛИ','usam'),		
				'text_expression'  => __('равно','usam'),
				'text_product_property' => ['sku' => __('Артикул','usam'), 'barcode' => __('Штрихкод','usam')],
				'text_add_item' => __('Добавлено','usam'),	
				'text_product' => __('Выбрать товары корзины, которые удовлетворяют условиям','usam'),
				'text_group' => __('Группа условий','usam'),
				'text_add_conditions' => __('Добавить условие','usam'),				
				'images_url'  => USAM_CORE_IMAGES_URL,	
				]
			);		
		}; 
		add_action('admin_footer', $anonymous_function );	
	}
	
	public static function work_manager( ) 
	{
		$anonymous_function = function()
		{ 				
			wp_enqueue_script( 'usam-work-manager', USAM_URL . '/admin/assets/js/work-manager.js', array( 'jquery-query' ), USAM_VERSION_ASSETS, false );
			wp_localize_script( 'usam-work-manager', 'USAM_Work_Manager', array(					
				'change_task_participants_nonce'  => usam_create_ajax_nonce( 'change_task_participants' ),						
				'add_participant_nonce'  => usam_create_ajax_nonce( 'add_participant' ),
				'delete_event_participant_nonce'  => usam_create_ajax_nonce( 'delete_event_participant' ),					
				'id' => isset($_GET['id'])?$_GET['id']:0,	
			) );	
		};
		add_action('admin_footer', $anonymous_function );		}
}
new USAM_Admin_Assets();
?>