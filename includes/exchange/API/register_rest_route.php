<?php
new USAM_Rest_API();
class USAM_Rest_API
{	
	protected $namespace = 'usam/v1';
	
	function __construct( )
	{	
		add_action('rest_api_init', [$this,'register_routes'] );
	//	add_filter('rest_request_before_callbacks' ,  [$this,'rest_request_before_callbacks'], 10 , 3); 
	//	add_filter('rest_url_prefix' ,  [$this,'change_rest_api_prefix'], 10);  ## Изменяет префикс REST API с `wp-json` на `api`	
	}
	
	public function change_rest_api_prefix($slug)   
	{
		return 'api';
	}
	
	public function rest_request_before_callbacks($response, $handler, $request)   
	{
	//	$contactguid = $request->get_header('X-USAM-ContactGuid');
		return $response; 
	}
	
	public function include_api()
    {
		require_once( USAM_FILE_PATH . '/includes/exchange/API/rest_route/API.php' );
		require_once( USAM_FILE_PATH . '/includes/exchange/API/rest_route/oauth.php' );
		require_once( USAM_FILE_PATH . '/includes/exchange/API/rest_route/chat_API.class.php' );
		require_once( USAM_FILE_PATH . '/includes/exchange/API/rest_route/newsletter_API.class.php' );
		require_once( USAM_FILE_PATH . '/includes/exchange/API/rest_route/basket_API.class.php' );				
		require_once( USAM_FILE_PATH . '/includes/exchange/API/rest_route/profile_API.class.php' );		
		require_once( USAM_FILE_PATH . '/includes/exchange/API/rest_route/crm_API.class.php' );
	}	
			
	public function register_routes()
    {			
		$this->include_api();
		register_rest_route( $this->namespace, '/mailing_lists', [		
			[
				'methods'  => 'GET,POST',
				'args' => [
					'not_added' => ['type' => 'integer', 'required' => false],	
					'view' => ['type' => 'integer', 'required' => false],		
					'subscribed' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],					
				],
				'callback' => ['USAM_API', 'get_mailing_lists'],				
				'permission_callback' => false,
			]		
		]);
		register_rest_route( $this->namespace, '/subscribe', [		
			[
				'methods'  => 'POST',
				'args' => [
					'email' => ['type' => 'string', 'required' => true],
					'lastname' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],	
					'firstname' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],						
					'lists' => ['type' => 'array', 'required' => false],
				],
				'callback' => ['USAM_API', 'new_subscribe'],				
				'permission_callback' => false,
			]
		]);		
		register_rest_route( $this->namespace, '/contact/subscriptions', [			
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'save_subscriptions'],	
				'args' => [					
					'lists' => ['type' => 'object', 'required' => true],
					'communication' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				],
				'permission_callback' => false,
			],			
		]);
		register_rest_route( $this->namespace, '/list/post', [			
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'add_post_userlist'],	
				'args' => [					
					'list' => ['type' => 'string', 'required' => true],
					'post_id' => ['type' => 'integer', 'required' => true],
				],
				'permission_callback' => false,				
			],
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_API', 'delete_post_userlist'],	
				'args' => [					
					'list' => ['type' => 'string', 'required' => true],
				],
				'permission_callback' => false,				
			]			
		]);
		register_rest_route( $this->namespace, '/list/seller', [			
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'add_seller_userlist'],	
				'args' => [					
					'list' => ['type' => 'string', 'required' => true],
					'seller_id' => ['type' => 'integer', 'required' => true],
				],
				'permission_callback' => false,				
			],
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_API', 'delete_seller_userlist'],	
				'args' => [					
					'list' => ['type' => 'string', 'required' => true],
				],
				'permission_callback' => false,				
			]			
		]);
		register_rest_route( $this->namespace, '/files', [
			[
				'methods'  => 'GET,POST',
				'args' => [
					'status' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],					
					'object_id' => ['type' => 'array,integer,string', 'required' => false],	
					'type' => ['type' => 'array,string', 'required' => false],	
					'user_id' => ['type' => 'array,integer,string', 'required' => false],
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'paged' => ['type' => 'integer', 'required' => false],
					'orderby' => ['type' => 'integer,string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'ASC', 'required' => false],	
					'folder' => ['type' => 'integer', 'required' => false],	
					'include' => ['type' => 'array', 'required' => false],						
				],
				'callback' => ['USAM_API', 'get_files'],				
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('view_all_files') || current_user_can('view_my_files');
				},
			],		
		]);		
		register_rest_route( $this->namespace, '/file/(?P<id>\d+)', [		
			[ 
				'methods'  => 'GET',
				'callback' => ['USAM_API', 'get_file'],				
				'permission_callback' => function( $request ){	
					return current_user_can('universam_api') || current_user_can('view_all_files') || current_user_can('view_my_files');
				}
			],
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'save_file'],	
				'args' => [					
					'status' => ['type' => 'string', 'required' => false],
					'title' => ['type' => 'string', 'required' => false],
					'folder_id' => ['type' => 'integer', 'required' => false],	
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('view_all_files') || current_user_can('view_my_files');
				},
			],
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_API', 'delete_file'],					
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('view_all_files') || current_user_can('view_my_files');
				},
			],		
		]);
		register_rest_route( $this->namespace, '/upload', [
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'upload_file'],				
				'args' => [	
					'title' => ['type' => 'string', 'required' => false],
					'type' => ['type' => 'string', 'required' => false],
					'property' => ['type' => 'integer', 'required' => false],					
				],
				'permission_callback' => false,
			],
		]);					
		register_rest_route( $this->namespace, '/no_image_uploaded', [
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'no_image_uploaded'],				
				'permission_callback' => false,
			],
		]);
		register_rest_route( $this->namespace, '/folders', [
			[
				'methods'  => 'GET,POST',
				'args' => [
					'status' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'slug' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],	
					'user_id' => ['type' => 'array', 'required' => false],	
					'include' => ['type' => 'array', 'required' => false],		
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'paged' => ['type' => 'integer', 'required' => false],
					'orderby' => ['type' => 'integer,string', 'required' => false],	
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'ASC', 'required' => false],	
					'parent' => ['type' => 'integer', 'required' => false],
					'breadcrumbs' => ['type' => 'boolean', 'required' => false],					
				],	
				'callback' => array('USAM_API', 'get_folders'),				
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('view_all_files') || current_user_can('view_my_files');
				},
			]						
		]);		
		register_rest_route( $this->namespace, '/folder', [			
			[
				'methods' => 'POST',
				'args' => [
					'name' => ['type' => 'string', 'required' => false],					
					'user_id' => ['type' => 'array', 'required' => false],						
					'parent' => ['type' => 'integer', 'required' => false],								
				],	
				'callback' => array('USAM_API', 'add_folder'),				
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('view_all_files') || current_user_can('view_my_files');
				},
			]				
		]);	
		register_rest_route( $this->namespace, '/folder/(?P<id>\d+)', [		
			[ 
				'methods'  => 'GET',
				'callback' => ['USAM_API', 'get_folder'],				
				'permission_callback' => function( $request ){	
					return current_user_can('universam_api') || current_user_can('view_all_files') || current_user_can('view_my_files');
				}
			],
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'save_folder'],	
				'args' => [					
					'status' => ['type' => 'string', 'required' => false],
					'name' => ['type' => 'string', 'required' => false],
					'parent' => ['type' => 'integer', 'required' => false],					
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('view_all_files') || current_user_can('view_my_files');
				},
			],
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_API', 'delete_folder'],					
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('view_all_files') || current_user_can('view_my_files');
				},
			],
		]);
		register_rest_route( $this->namespace, '/notifications', [
			[
				'methods'  => 'GET,POST',
				'args' => [
					'author' => ['type' => 'string,array', 'required' => false],
					'include' => ['type' => 'array', 'required' => false],		
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'paged' => ['type' => 'integer', 'required' => false],
					'orderby' => ['type' => 'integer,string', 'required' => false],	
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'ASC', 'required' => false],
				],	
				'callback' => ['USAM_API', 'get_notifications'],				
				'permission_callback' => function( $request ){
					return is_user_logged_in();
				},
			],
			[
				'methods'  => 'PUT',
				'callback' => ['USAM_API', 'update_notifications'],	
				'args' => [
					'items' => ['type' => 'array', 'required' => true],	
				],				
				'permission_callback' => function( $request ){
					return is_user_logged_in();
				},
			],
		]);			
		register_rest_route( $this->namespace, '/notifications/read', [
			[
				'methods'  => 'GET',			
				'callback' => ['USAM_API', 'read_notifications'],				
				'permission_callback' => function( $request ){
					return is_user_logged_in();
				},
			]	
		]);		
		register_rest_route( $this->namespace, '/admin/filters', [		
			[
				'methods'  => 'GET',
				'callback' => ['USAM_API', 'get_admin_filters'],	
				'args' => array(
					'screen_id' => ['type' => 'string', 'required' => true],
				),				
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || is_user_logged_in();
				},
			],
			[
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'save_admin_filter'],	
				'args' => [
					'name' => ['type' => 'string', 'required' => true],	
					'filters' => ['type' => 'object', 'required' => true],	
					'screen_id' => ['type' => 'string', 'required' => true],	
				],				
				'permission_callback' => function( $request ){
					return is_user_logged_in();
				},
			],
			[
				'methods'  => 'DELETE',
				'callback' => array('USAM_API', 'delete_admin_filter'),	
				'args' => array(
					'id' => array('type' => 'integer', 'required' => true ),	
				),				
				'permission_callback' => function( $request ){
					return is_user_logged_in();
				},
			],					
		]);
		// получить фильтры (используется также на форте)
		register_rest_route( $this->namespace, '/filters', [		
			[
				'permission_callback' => false,
				'methods'  => 'POST,GET',
				'callback' => ['USAM_API', 'get_interface_filters'],					
			],	
			[
				'permission_callback' => false,
				'methods'  => 'PUT',
				'args' => [
					'filters' => ['type' => 'array', 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('sanitize_title', (array)$param); } ],
					'screen_id' => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],
				],
				'callback' => ['USAM_API', 'save_interface_filters'],					
			],			
		]);				
		register_rest_route( $this->namespace, '/parser', [		
			[
				'methods'  => 'POST',
				'callback' => array('USAM_API', 'insert_parser'),	
				'args' => array(			
					'name' => ['type' => 'string', 'required' => true, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'domain' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'type_price' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'store' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'active' => ['type' => 'integer,boolean', 'required' => false, 'sanitize_callback' => 'absint'],
					'bypass_speed' => ['type' => 'string,integer,float', 'required' => false],
					'link_option' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],					
					'site_type' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'scheme' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'view_product' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'authorization' => ['type' => 'integer', 'required' => false],				
					'headers' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],		
				),
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('view_parser');
				},
			]		
		]);			
		register_rest_route( $this->namespace, '/parser/(?P<id>\d+)', [
			[
				'methods'  => 'GET',
				'callback' => array('USAM_API', 'get_parser'),					
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('view_parser');
				},
			],
			[
				'methods'  => 'POST',
				'callback' => array('USAM_API', 'save_parser'),	
				'args' => array(					
					'name' => ['type' => 'string', 'required' => true, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'domain' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'type_price' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'existence_check' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],					
					'store' => ['type' => 'integer', 'required' => false],
					'active' => ['type' => 'integer,boolean', 'required' => false, 'sanitize_callback' => 'absint'],
					'bypass_speed' => ['type' => 'string,integer,float', 'required' => false],
					'link_option' => ['type' => 'integer', 'required' => false],
					'parent_variation' => ['type' => 'integer', 'required' => false],
					'variations' => ['type' => 'object', 'required' => false],					
					'site_type' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'scheme' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'view_product' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'authorization' => ['type' => 'integer', 'required' => false],				
					'headers' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],					
				),
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('view_parser');
				},
			],	
			[
				'methods'  => 'DELETE',
				'callback' => array('USAM_API', 'delete_parser'),					
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('view_parser');
				}, 
			],			
		]);	
		register_rest_route( $this->namespace, '/parser/test/(?P<id>\d+)', [
			[
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'test_data_parser'],	
				'args' => [				
					'url' => ['type' => 'string', 'required' => true],
				],
				'permission_callback' => function( $request ){	
					return current_user_can('universam_api') || current_user_can('view_parser');
				}
			]
		]);
		register_rest_route( $this->namespace, '/parser/test/login/(?P<id>\d+)', [
			[
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'test_login_parser'],	
				'args' => [				
					'login_page' => ['type' => 'string', 'required' => true],
					'authorization_parameters' => ['type' => 'string', 'required' => true],
				],
				'permission_callback' => function( $request ){	
					return current_user_can('universam_api') || current_user_can('view_parser');
				}
			]
		]);
		register_rest_route( $this->namespace, '/notes', array(		
			array(
				'methods'  => 'GET,POST',
				'callback' => array('USAM_API', 'get_notes'),				
				'permission_callback' => function( $request ){
					return is_user_logged_in();
				},
			),		
		));		
		register_rest_route( $this->namespace, '/note', array(		
			array(
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'insert_note'],	
				'args' => array(
					'note' => array('type' => 'string', 'required' => true ),
				),
				'permission_callback' => function( $request ){			
					return is_user_logged_in();
				}, 
			)
		));
		register_rest_route( $this->namespace, '/note/(?P<id>\d+)', array(		
			array(
				'methods'  => 'POST',
				'callback' => array('USAM_API', 'save_note'),	
				'args' => array(					
					'note' => array('type' => 'string', 'required' => true ),
				),
				'permission_callback' => function( $request ){			
					return is_user_logged_in();
				},
			),	
			array(
				'methods'  => 'DELETE',
				'callback' => array('USAM_API', 'delete_note'),					
				'permission_callback' => function( $request ){			
					return is_user_logged_in();
				}, 
			),			
		));				
		register_rest_route( $this->namespace, '/chat/messages', array(		
			[
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
				'methods'  => 'GET,POST',
				'callback' => ['USAM_Chat_API', 'get_messages'],	
				'args' => [
					'dialog_id' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'to_id' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],	
					'from_id' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],	
					'read_ids' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
				]
			],			
		));
		register_rest_route( $this->namespace, '/chat/dialog', array(		
			[
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
				'methods'  => 'GET',
				'callback' => ['USAM_Chat_API', 'get_dialog'],	
				'args' => [
					'dialog_id' => ['type' => 'integer', 'required' => true, 'sanitize_callback' => 'absint'],
				]
			]			
		));
		register_rest_route( $this->namespace, '/chat/dialog/(?P<id>\d+)', [		
			[
				'methods'  => 'POST',
				'callback' => ['USAM_Chat_API', 'save_dialog'],	
				'args' => [
					'manager_id' => ['type' => 'integer', 'required' => true, 'sanitize_callback' => 'absint'],
				],
				'permission_callback' => function( $request ){	
					return current_user_can('view_chat');
				}
			],	
		]);
		register_rest_route( $this->namespace, '/chat/dialogs', array(		
			[
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
				'methods'  => 'GET,POST',
				'callback' => ['USAM_Chat_API', 'get_dialogs'],	
				'args' => [
					'add_fields' => ['type' => 'string,array', 'required' => false],
					'user' => ['type' => 'string', 'required' => false],					
					'include' => ['type' => 'array', 'required' => false],		
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'paged' => ['type' => 'integer', 'required' => false],
					'orderby' => ['type' => 'integer,string', 'required' => false],	
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'ASC', 'required' => false],
				],
			]			
		));		
		register_rest_route( $this->namespace, '/chat/message', array(		
			[
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
				'methods'  => 'POST',
				'callback' => ['USAM_Chat_API', 'add_message'],	
				'args' => array(
					'message' => ['type' => 'string', 'required' => true],
					'dialog_id' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'message_id' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'object_id' => ['type' => 'integer', 'required' => false],
					'object_type' => ['type' => 'string', 'required' => false],	
					'update' => ['type' => 'boolean', 'required' => false],					
				),
			/*	'permission_callback' => function( $request ){			
					return is_user_logged_in();
				}, */
			]			
		));
		register_rest_route( $this->namespace, '/chat/message/(?P<id>\d+)', array(		
			[
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
				'methods'  => 'POST',
				'callback' => array('USAM_Chat_API', 'save_message'),	
				'args' => [
					'message' => ['type' => 'string', 'required' => true]
				]	
			]			
		));
		register_rest_route( $this->namespace, '/chat/contactform', array(		
			array(
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
				'methods'  => 'POST',
				'callback' => ['USAM_Chat_API', 'save_contactform'],	
				'args' => array(
					'message' => array('type' => 'string', 'required' => true),
					'name' => array('type' => 'string', 'required' => true),
					'phone' => array('type' => 'string', 'required' => false),
					'email' => array('type' => 'string', 'required' => false),				
				)
			),			
		));	// Службы оплаты
		register_rest_route( $this->namespace, '/payment_services', array(		
			array(
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
				'methods'  => 'GET',
				'callback' => array('USAM_API', 'get_payment_services'),
			),			
		)); 
		register_rest_route( $this->namespace, '/types_payers', array(		
			array(
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
				'methods'  => 'GET,POST',
				'callback' => ['USAM_API', 'get_payers'],
				'args' => [
					'active' => ['type' => ['string','integer'], 'enum' => ['all',0,1,'0','1'], 'default' => 1, 'required' => false],	
					'orderby' => ['type' => 'string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],					
				]
			),			
		));		
		register_rest_route( $this->namespace, '/locations', array(		
			array(
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
				'methods'  => 'GET,POST',
				'callback' => array('USAM_API', 'get_locations'),	
				'args' => array(
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'paged' => ['type' => 'integer', 'required' => false],
					'orderby' => ['type' => 'string', 'required' => false],	
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],	
					'code' => ['type' => 'array,string', 'required' => false],
					'count' => ['type' => 'integer', 'required' => false],	
					'type' => ['type' => ['array', 'string'], 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('sanitize_title', (array)$param); } ],
					'fields' => ['type' => ['string','array'], 'required' => false],
				)
			),			
		));		
		register_rest_route( $this->namespace, '/currencies', array(		
			array(
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
				'methods'  => 'GET,POST',
				'callback' => array('USAM_API', 'get_currencies'),	
				'args' => array(
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'paged' => ['type' => 'integer', 'required' => false],
					'orderby' => ['type' => 'string', 'required' => false],	
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],	
					'count' => ['type' => 'integer', 'required' => false],	
					'fields' => ['type' => ['string','array'], 'required' => false],
				)
			),			
		));	
		register_rest_route( $this->namespace, '/departments', array(		
			array(
				'methods'  => 'GET,POST',
				'callback' => ['USAM_API', 'get_departments'],	
				'args' => array(
					'fields' => ['type' => 'string', 'required' => false],
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'paged' => ['type' => 'integer', 'required' => false],	
					'orderby' => ['type' => 'string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],					
				),
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('view_departments');
				}
			),			
		));
		register_rest_route( $this->namespace, '/campaigns', array(		
			array(
				'methods'  => 'GET,POST',
				'callback' => ['USAM_API', 'get_campaigns'],	
				'args' => array(
					'fields' => ['type' => 'string', 'required' => false],
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'paged' => ['type' => 'integer', 'required' => false],	
					'orderby' => ['type' => 'string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],					
				),
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('view_advertising_campaigns');
				}
			),			
		));							
		register_rest_route( $this->namespace, '/popups', [
			[
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
				'methods'  => 'GET',
				'callback' => ['USAM_API', 'get_popups'],
				'args' => [					
					'page_id' => ['type' => 'integer', 'required' => false],				
				],				
			],
		]);	
		register_rest_route( $this->namespace, '/banners', [
			[
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
				'methods'  => 'GET,POST',
				'callback' => ['USAM_API', 'get_banners'],	
				'args' => [
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'acting_now' => ['type' => 'integer', 'required' => false],
					'conditions' => ['type' => 'array,object,string', 'required' => false],					
				]
			],
		]);
		register_rest_route( $this->namespace, '/banner', [					
			[
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'insert_banner'],	
				'args' => [
					'name' => ['type' => 'string', 'required' => true, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'status' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'device' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'type' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'sort' => ['type' => 'integer', 'required' => false],
					'object_url' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'object_id' => ['type' => 'integer', 'required' => false],
					'actuation_time' => ['type' => 'integer', 'required' => false],					
					'html' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],
					'start_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
					'end_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_interface');
				}
			],			
		]);
		register_rest_route( $this->namespace, '/banner/(?P<id>\d+)', [		
			[
				'methods'  => 'GET',
				'callback' => ['USAM_CRM_API', 'get_banner'],					
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
			],
			[
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'update_banner'],	
				'args' => [
					'name' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'status' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'device' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'type' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'sort' => ['type' => 'integer', 'required' => false],
					'object_url' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'object_id' => ['type' => 'integer', 'required' => false],					
					'actuation_time' => ['type' => 'integer', 'required' => false],					
					'html' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],
					'start_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
					'end_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],	
			[
				'methods'  => 'DELETE',
				'callback' => ['USAM_CRM_API', 'delete_banner'],					
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_interface');
				}
			],			
		]);
		register_rest_route( $this->namespace, '/banners/location', [
			[				
				'methods'  => 'GET,POST',
				'callback' => ['USAM_API', 'get_location_banners'],	
				'args' => [				
					'conditions' => ['type' => 'array,object,string', 'required' => false],					
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_interface');
				}
			],
		]);
		register_rest_route( $this->namespace, '/webforms', [
			[
				'permission_callback' => false,
				'methods'  => 'GET,POST',
				'callback' => ['USAM_CRM_API', 'get_webforms'],	
				'args' => [
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'acting_now' => ['type' => 'integer', 'required' => false],
					'conditions' => ['type' => 'array,object,string', 'required' => false],					
				]
			],
		]);	
		register_rest_route( $this->namespace, '/webform/(?P<webform_code>\S+)', [		
			[
				'methods'  => 'GET',
				'callback' => ['USAM_CRM_API', 'get_webform'],					
				'args' => [					
					'page_id' => ['type' => 'integer', 'required' => false],				
				],
				'permission_callback' => false
			],
			[
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'send_webform'],	
				'args' => [
					'data' => ['type' => 'object', 'required' => true],
					'page_id' => ['type' => 'integer', 'required' => false],
					'object' => ['type' => 'object', 'required' => false],
					'quantity' => ['type' => 'integer', 'required' => false],					
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],
			[
				'methods'  => 'PUT',
				'callback' => ['USAM_CRM_API', 'save_webform'],	
				'args' => [
					'data' => ['type' => 'object', 'required' => true],							
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],
		]);
		register_rest_route( $this->namespace, '/sliders', array(		
			array(				
				'methods'  => 'GET,POST',
				'callback' => array('USAM_API', 'get_sliders'),	
				'args' => [
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
				],
				'permission_callback' => false,
			),			
		));	
		register_rest_route( $this->namespace, '/slider', [		
			[ // Получить
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'insert_slider'],				
				'args' => [					
					'name' => ['type' => 'string', 'required' => true],
					'slides' => ['type' => 'array', 'required' => false],
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_interface');
				}
			],
		]);
		register_rest_route( $this->namespace, '/slider/(?P<id>\d+)', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_API', 'get_slider'],				
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_interface');
				}
			],
			[ // Сохранить
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'update_slider'],	
				'args' => [					
					'name' => ['type' => 'string', 'required' => false],
					'slides' => ['type' => 'array', 'required' => false],
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_interface');
				}				
			],
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_API', 'delete_slider'],	
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_interface');
				}			
			]				
		]);	
		register_rest_route( $this->namespace, '/points_delivery', array(		
			array(				
				'methods'  => 'GET,POST',
				'callback' => ['USAM_API', 'get_points_delivery'],	
				'args' => [
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'location_id' => ['type' => 'integer', 'required' => false, 'default' => 0],
					'paged' => ['type' => 'integer', 'required' => false],	
					'owner' => ['type' => 'string', 'required' => false],		
					'count' => ['type' => 'integer', 'required' => false, 'default' => 1000],
					'issuing' => ['type' => 'integer', 'required' => false, 'default' => 1],					
					'product_id' => ['type' => 'integer', 'required' => false],		
				],
				'permission_callback' => false,
			),			
		));	
		register_rest_route( $this->namespace, '/storages', [
			[
				'methods'  => 'GET,POST',
				'callback' => ['USAM_API', 'get_storages'],	
				'args' => [
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'add_fields' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'product_id' => ['type' => 'array,integer', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
					'type' => ['type' => 'string,array', 'required' => false],						
					'location_id' => ['type' => 'integer', 'required' => false],
					'paged' => ['type' => 'integer', 'required' => false],	
					'owner' => ['type' => 'string', 'required' => false],	
					'count' => ['type' => 'integer', 'required' => false, 'default' => 1000],
					'issuing' => ['type' => 'integer', 'required' => false]
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],			
		]);			
		register_rest_route( $this->namespace, '/storage', [		
			[ // Получить
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'insert_storage'],				
				'args' => [					
					'title' => ['type' => 'string', 'required' => true, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'description' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'type' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'active' => ['type' => 'integer', 'required' => false],
					'sort' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'code' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'shipping' => ['type' => 'integer', 'required' => false],
					'issuing' => ['type' => 'integer', 'required' => false],
					'type_price' => ['type' => 'string,integer', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],	
					'sales_area' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
					'images' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
					'address' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],	
					'index' => ['type' => 'string,integer', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'image' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'schedule' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'address' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],	
					'email' => ['type' => 'string', 'required' => false, 'validate_callback' => 'is_email'],		
					'phone' => ['type' => 'string,integer', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'latitude' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'usam_string_to_float'],		
					'longitude' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'usam_string_to_float'],		
					'period_from' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'period_to' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'period_type' => ['type' => 'string', 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],					
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_interface');
				}
			],
		]);
		register_rest_route( $this->namespace, '/storage/(?P<id>\d+)', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_API', 'get_storage'],				
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_interface');
				}
			],
			[ // Сохранить
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'update_storage'],	
				'args' => [					
					'title' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'description' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'type' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'description' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'active' => ['type' => 'integer,boolean', 'required' => false, 'sanitize_callback' => 'absint'],
					'sort' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'code' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'shipping' => ['type' => 'integer,boolean', 'required' => false, 'sanitize_callback' => 'absint'],
					'issuing' => ['type' => 'integer,boolean', 'required' => false, 'sanitize_callback' => 'absint'],
					'type_price' => ['type' => 'string,integer', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],					
					'images' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
					'image' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'address' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],	
					'index' => ['type' => 'string,integer', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],					
					'schedule' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'address' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],	
					'email' => ['type' => 'string', 'required' => false],		
					'phone' => ['type' => 'string,integer', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'latitude' => ['type' => 'string,integer', 'required' => false, 'sanitize_callback' => 'usam_string_to_float'],		
					'longitude' => ['type' => 'string,integer', 'required' => false, 'sanitize_callback' => 'usam_string_to_float'],		
					'period_from' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'period_to' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'period_type' => ['type' => 'string', 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_interface');
				}				
			],
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_API', 'delete_storage'],	
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_interface');
				}			
			]				
		]);	
		register_rest_route( $this->namespace, '/points_partners', [
			[				
				'methods'  => 'GET,POST',
				'callback' => ['USAM_API', 'get_points_partners'],	
				'args' => [
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],					
					'paged' => ['type' => 'integer', 'required' => false],		
					'location_id' => ['type' => 'integer', 'required' => false, 'default' => 0],
					'count' => ['type' => 'integer', 'required' => false, 'default' => 1000],	
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],			
		]);
		register_rest_route( $this->namespace, '/point_your_company_map', [
			[
				'methods'  => 'GET',
				'callback' => ['USAM_API', 'get_point_your_company_map'],	
				'args' => [
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],			
		]);					
		register_rest_route( $this->namespace, '/type_prices', [		
			[
				'methods'  => 'GET,POST',
				'callback' => ['USAM_API', 'get_type_prices'],	
				'args' => [
					'currency' => ['type' => 'string', 'required' => false],
					'fields' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'type' => ['type' => 'string', 'required' => false, 'enum' => ['P','p','R','r']],
					'available' => ['type' => 'integer', 'required' => false, 'enum' => [1,0]],
					'code' => ['type' => 'string', 'required' => false],
					'orderby' => ['type' => 'string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],
				],
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('view_orders') || current_user_can('edit_product');
				}
			]		
		]);			
		register_rest_route( $this->namespace, '/type_price', [		
			[ // Получить
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'insert_type_price'],				
				'args' => [					
					'title' => ['type' => 'string', 'required' => true, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'code' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'type' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],					
					'currency' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],					
					'base_type' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'underprice' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'available' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'rounding' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'locations' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
					'roles' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
					'sort' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'external_code' => ['type' => 'string,integer', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],	
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('edit_product');
				}
			],
		]);
		register_rest_route( $this->namespace, '/type_price/(?P<id>\d+)', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_API', 'get_type_price'],				
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('edit_product');
				}
			],
			[ // Сохранить
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'update_type_price'],	
				'args' => [					
					'title' => ['type' => 'string', 'required' => true, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'code' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'type' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],					
					'currency' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],					
					'base_type' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'underprice' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'available' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'rounding' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'locations' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
					'roles' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
					'sort' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'external_code' => ['type' => 'string,integer', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],						
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('edit_product');
				}				
			],
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_API', 'delete_type_price'],	
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('edit_product');
				}			
			]				
		]);	
		register_rest_route( $this->namespace, '/roles', [		
			[
				'methods'  => 'GET,POST',
				'callback' => ['USAM_API', 'get_roles'],	
				'args' => [
					'currency' => ['type' => 'string', 'required' => false],
					'type' => ['type' => 'string', 'required' => false, 'enum' => ['P','p','R','r']],
					'available' => ['type' => 'integer', 'required' => false, 'enum' => [1,0]],
					'code' => ['type' => 'string', 'required' => false],
					'orderby' => ['type' => 'string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],
				],
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('manage_options');
				}
			],
		]);		
		register_rest_route( $this->namespace, '/user', [				
			[
				'methods'  => 'DELETE',
				'callback' => ['USAM_Profile_API', 'delete_user'],				
				'permission_callback' => function( $request ){
					return is_user_logged_in();
				},
			],
		]);		
		register_rest_route( $this->namespace, '/referral', [	
			[
				'methods'  => 'GET',
				'callback' => ['USAM_Profile_API', 'get_referral_url'],						
				'permission_callback' => function( $request ){
					return is_user_logged_in();
				},
			],
		]);				
		register_rest_route( $this->namespace, '/property_groups', [		
			[
				'permission_callback' => false,
				'methods'  => 'GET,POST',
				'callback' => ['USAM_API', 'get_property_groups'],	
				'args' => [
					'type' => ['type' => 'string,array', 'required' => true, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'code' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],	
					'fields' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
				]	
			],			
		]);	
		register_rest_route( $this->namespace, '/properties', [		
			[
				'methods'  => 'GET,POST',
				'callback' => ['USAM_API', 'get_properties'],	
				'args' => [
					'active' => ['type' => ['string','integer'], 'enum' => ['all',0,1,'0','1'], 'default' => 1, 'sanitize_callback' => 'absint', 'required' => false],
					'mandatory' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'type' => ['type' => 'string,array', 'required' => true, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'code' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],	
					'fields' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],				
					'meta_query' => ['type' => 'array', 'required' => false],	
					'meta_key' => ['type' => 'string', 'required' => false],
					'meta_value' => ['type' => ['string','integer'], 'required' => false],	
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],			
		]);	
		register_rest_route( $this->namespace, '/property', [		
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'insert_property'],				
				'args' => [					
					'name' => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],
					'type' => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],
					'field_type' => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_interface');
				}
			],
		]);
		register_rest_route( $this->namespace, '/property/(?P<id>\d+)', [		
			[ // Получить
				'permission_callback' => false,
				'methods'  => 'GET',
				'callback' => ['USAM_API', 'get_property']
			],
			[ // Сохранить
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'update_property'],	
				'args' => [					
					'name' => ['type' => 'string', 'required' => false],
					'slides' => ['type' => 'array', 'required' => false],
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_interface');
				}				
			],
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_API', 'delete_property'],	
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_interface');
				}			
			]				
		]);
		register_rest_route( $this->namespace, '/property/(?P<type>\S+)/(?P<code>\S+)', [		
			[ // Получить
				'permission_callback' => false,
				'methods'  => 'GET',
				'callback' => ['USAM_API', 'get_code_property']
			]	
		]);
		register_rest_route( $this->namespace, '/menu/(?P<menu_id>\S+)', [		
			[
				'permission_callback' => false,
				'methods'  => 'GET',
				'callback' => ['USAM_API', 'get_menu'],				
			],			
		]);	
		register_rest_route( $this->namespace, '/menus', [		
			[
				'methods'  => 'GET,POST',
				'callback' => ['USAM_API', 'get_menus'],		
				'args' => [
					'location' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'fields' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']				
			],			
		]);		
		register_rest_route( $this->namespace, '/basket/(?P<basket_id>\d+)',[
			[
				'methods'  => 'GET',
				'callback' => ['USAM_basket_API', 'get'],
				'permission_callback' => false,
			],
			[
				'methods'  => 'POST',
				'callback' => ['USAM_basket_API', 'save'],	
				'args' => [	
					'shipping' => ['type' => 'integer', 'required' => false],
					'bonuses' => ['type' => 'integer', 'required' => false],					
					'storage_pickup' => ['type' => 'integer', 'required' => false],
					'payment' => ['type' => 'integer', 'required' => false],				
					'coupon' => ['type' => 'string,integer', 'required' => false],
					'company' => ['type' => 'integer', 'required' => false],
					'type_payer' => ['type' => 'integer', 'required' => false],
					'checkout' => ['type' => 'object', 'required' => false],
					'address' => ['type' => 'integer', 'required' => false],					
					'buy' => ['type' => 'integer', 'required' => false],
				],
				'permission_callback' => false,
			]			
		]);		
		register_rest_route( $this->namespace, '/basket',[
			[
				'methods'  => 'GET',
				'callback' => ['USAM_basket_API', 'get'],	
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],
			[
				'methods'  => 'POST',
				'callback' => ['USAM_basket_API', 'save'],	
				'args' => [	
					'shipping' => ['type' => 'integer', 'required' => false],
					'bonuses' => ['type' => 'integer', 'required' => false],					
					'storage_pickup' => ['type' => 'integer', 'required' => false],
					'payment' => ['type' => 'integer', 'required' => false],				
					'coupon' => ['type' => 'string,integer', 'required' => false],
					'company' => ['type' => 'integer', 'required' => false],
					'type_payer' => ['type' => 'integer', 'required' => false],
					'checkout' => ['type' => 'object', 'required' => false],
					'address' => ['type' => 'integer', 'required' => false],					
					'buy' => ['type' => 'integer', 'required' => false],
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			]			
		]);		
		register_rest_route( $this->namespace, '/basket/product/(?P<basket_id>\d+)',[			
			[
				'methods'  => 'POST',
				'callback' => ['USAM_Basket_API', 'update_product'],	
				'args' => [					
					'id' => ['type' => 'integer', 'required' => true],
					'quantity' => ['type' => 'float', 'required' => true],						
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],
			[
				'methods'  => 'DELETE',
				'callback' => ['USAM_Basket_API', 'delete_product'],	
				'args' => [			
					'id' => ['type' => 'integer', 'required' => true],				
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],
		]);	
		register_rest_route( $this->namespace, '/basket/product',[			
			[
				'methods'  => 'POST',
				'callback' => ['USAM_Basket_API', 'update_product'],	
				'args' => [					
					'id' => ['type' => 'integer', 'required' => true],
					'quantity' => ['type' => 'float', 'required' => true],						
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],
			[
				'methods'  => 'DELETE',
				'callback' => ['USAM_Basket_API', 'delete_product'],	
				'args' => [			
					'id' => ['type' => 'integer', 'required' => true],				
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],
		]);			
		register_rest_route( $this->namespace, '/basket/order/(?P<order_id>\S+)', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_Basket_API', 'order_in_basket'],				
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],			
		]);
		register_rest_route( $this->namespace, '/basket/new_product/(?P<basket_id>\d+)',[			
			[
				'methods'  => 'POST',
				'callback' => ['USAM_basket_API', 'add_product'],	
				'args' => [					
					'product_id' => ['type' => 'integer', 'required' => true],					
					'quantity' => ['type' => 'float', 'required' => false],					
					'unit_measure' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'variations' => ['type' => 'object,array', 'required' => false],	
					'gift' => ['type' => 'integer', 'required' => false]
				],	
				'permission_callback' => ['USAM_Request_Processing', 'permission']				
			]
		]);
		register_rest_route( $this->namespace, '/basket/new_products/(?P<basket_id>\d+)',[
			[				
				'methods'  => 'POST',
				'callback' => ['USAM_basket_API', 'add_products'],	
				'args' => [					
					'items' => ['type' => 'array', 'required' => false],
					'clear' => ['type' => 'integer,boolean', 'required' => false],
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			]
		]);
		register_rest_route( $this->namespace, '/basket/new_product',[			
			[
				'methods'  => 'POST',
				'callback' => ['USAM_basket_API', 'add_product'],	
				'args' => [					
					'product_id' => ['type' => 'integer', 'required' => true],					
					'quantity' => ['type' => 'float', 'required' => false],					
					'unit_measure' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'variations' => ['type' => 'object,array', 'required' => false],	
					'gift' => ['type' => 'integer', 'required' => false]
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			]		
		]);
		register_rest_route( $this->namespace, '/basket/new_products',[				
			[
				'methods'  => 'POST',
				'callback' => ['USAM_basket_API', 'add_products'],	
				'args' => [					
					'items' => ['type' => 'array', 'required' => false],
					'clear' => ['type' => 'integer,boolean', 'required' => false],
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],
		]);
		register_rest_route( $this->namespace, '/basket/products/(?P<basket_id>\d+)',[			
			[
				'methods'  => 'POST',
				'callback' => ['USAM_Basket_API', 'update_products'],	
				'args' => [					
					'products' => ['type' => 'array', 'required' => false]				
				],	
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],			
			[
				'methods'  => 'DELETE',
				'callback' => ['USAM_Basket_API', 'delete_products'],	
				'args' => [					
					'items' => ['type' => 'array', 'required' => true, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],
		]);	
		register_rest_route( $this->namespace, '/basket/products',[			
			[
				'methods'  => 'POST',
				'callback' => ['USAM_Basket_API', 'update_products'],	
				'args' => [					
					'products' => ['type' => 'array', 'required' => false]				
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']				
			],			
			[
				'methods'  => 'DELETE',
				'callback' => ['USAM_Basket_API', 'delete_products'],	
				'args' => [					
					'items' => ['type' => 'array', 'required' => true, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],
		]);			
		register_rest_route( $this->namespace, '/basket/clear/(?P<basket_id>\d+)',[			
			[
				'methods'  => 'GET',
				'callback' => ['USAM_Basket_API', 'clear'],		
				'permission_callback' => false,			
			],			
		]);	
		register_rest_route( $this->namespace, '/basket/clear',[			
			[
				'methods'  => 'GET',
				'callback' => ['USAM_Basket_API', 'clear'],	
				'permission_callback' => ['USAM_Request_Processing', 'permission']				
			]
		]);			
		register_rest_route( $this->namespace, '/basket/cross_sells/(?P<basket_id>\d+)',[			
			[
				'permission_callback' => false,
				'methods'  => 'GET',
				'callback' => ['USAM_Basket_API', 'cross_sells']
			]
		]);
		register_rest_route( $this->namespace, '/basket/cross_sells',[			
			[
				'permission_callback' => false,
				'methods'  => 'GET',
				'callback' => ['USAM_Basket_API', 'cross_sells'],					
			]
		]);
		register_rest_route( $this->namespace, '/delivery/options/(?P<handler>\S+)', [
			[
				'methods'  => 'GET',
				'callback' => ['USAM_Basket_API', 'get_delivery_service_options'],					
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || usam_check_current_user_role('administrator') || usam_check_current_user_role('shop_manager');
				}				
			]	
		]);
		register_rest_route( $this->namespace, '/delivery/options', [
			[
				'methods'  => 'GET',
				'callback' => ['USAM_Basket_API', 'get_delivery_service_options'],					
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || usam_check_current_user_role('administrator') || usam_check_current_user_role('shop_manager');
				}				
			]	
		]);
		register_rest_route( $this->namespace, '/delivery', [		
			[
				'methods'  => 'POST',
				'callback' => array('USAM_Basket_API', 'insert_delivery'),	
				'args' => [		
					'name' => ['type' => 'string', 'required' => true, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'handler' => ['type' => 'string', 'required' => true, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'sort' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'tax_id' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],					
					'description' => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],
					'active' => ['type' => 'integer,boolean', 'required' => false, 'sanitize_callback' => 'absint'],
					'include_in_cost' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'storage_id' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'price' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'usam_string_to_float'],
					'img' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'courier_company' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'delivery_option' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'period_from' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'period_to' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],					
					'period_type' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				],
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('setting_document');
				},
			]		
		]);			
		register_rest_route( $this->namespace, '/delivery/(?P<id>\d+)', [
			[
				'methods'  => 'GET',
				'callback' => array('USAM_Basket_API', 'get_delivery'),					
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('setting_document');
				},
			],
			[
				'methods'  => 'POST',
				'callback' => ['USAM_Basket_API', 'update_delivery'],	
				'args' => array(					
					'name' => ['type' => 'string', 'required' => true, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'handler' => ['type' => 'string', 'required' => true, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'sort' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'tax_id' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],					
					'description' => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],
					'active' => ['type' => 'integer,boolean', 'required' => false, 'sanitize_callback' => 'absint'],
					'include_in_cost' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'storage_id' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'price' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'usam_string_to_float'],
					'img' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'courier_company' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'delivery_option' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'period_from' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'period_to' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],					
					'period_type' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				),
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can( 'setting_document' );
				},
			],	
			[
				'methods'  => 'DELETE',
				'callback' => ['USAM_Basket_API', 'delete_delivery'],					
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('setting_document');
				}, 
			],			
		]);	
		register_rest_route( $this->namespace, '/gifts',[			
			[
				'permission_callback' => false,
				'methods'  => 'GET',
				'callback' => ['USAM_Basket_API', 'gifts'],					
			]
		]);		
		register_rest_route( $this->namespace, '/statuses', array(		
			array(
				'permission_callback' => false,
				'methods'  => 'GET,POST',
				'callback' => array('USAM_API', 'get_statuses'),	
				'args' => array(
					'type' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'code' => ['type' => 'array', 'required' => false],
					'fields' => ['type' => ['string','array'], 'required' => false],
				)	
			),			
		));				
		register_rest_route( $this->namespace, '/processes', array(		
			array(
				'methods'  => 'GET',
				'callback' => array('USAM_API', 'get_processes'),				
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || usam_check_current_user_role('administrator') || usam_check_current_user_role('shop_manager');
				}
			),		
		));		
		register_rest_route( $this->namespace, '/process/(?P<process_id>\S+)', [	
			[
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'save_process'],				
				'args' => [						
					'status' => ['type' => 'string', 'required' => true],
				],
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || usam_check_current_user_role('administrator') || usam_check_current_user_role('shop_manager');
				}
			],	
			[
				'methods'  => 'DELETE',
				'callback' => ['USAM_API', 'delete_process'],				
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || usam_check_current_user_role('administrator') || usam_check_current_user_role('shop_manager');
				}
			],			
		]);	
		register_rest_route( $this->namespace, '/importer', [
			[
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'start_importer'],	
				'args' => [
					'file' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'file_settings' => ['type' => 'object', 'required' => false],
					'rule' => ['type' => 'object', 'required' => false],
					'columns' => ['type' => 'array', 'required' => false],					
					'type' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'source' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],					
					'template_id' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'absint']
				],
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || usam_check_current_user_role('administrator') || current_user_can('view_product_importer') || current_user_can('contact_import') || current_user_can('order_import') || current_user_can('employee_import') || current_user_can('company_import') || current_user_can('subscription_import');
				}, 
			]
		]);
		register_rest_route( $this->namespace, '/importer/file/data', [
			[
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'get_importer_file_data'],	
				'args' => [
					'file' => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],
					'file_settings' => ['type' => 'object', 'required' => true],
					'count' => ['type' => 'integer', 'required' => false],
				],
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || usam_check_current_user_role('administrator') || current_user_can('view_product_importer') || current_user_can('contact_import') || current_user_can('order_import') || current_user_can('employee_import') || current_user_can('company_import') || current_user_can('subscription_import');
				}, 
			]
		]);
		register_rest_route( $this->namespace, '/importer/file/upload', [
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'importer_file_upload'],				
				'args' => [	
					'title' => ['type' => 'string', 'required' => false],
					'type' => ['type' => 'string', 'required' => false],
				],
				'permission_callback9' => function( $request ){			
					return current_user_can('universam_api') || usam_check_current_user_role('administrator') || current_user_can('view_product_importer') || current_user_can('contact_import') || current_user_can('order_import') || current_user_can('employee_import') || current_user_can('company_import') || current_user_can('subscription_import');
				}, 
				'permission_callback' => false,
			],
		]);				
		register_rest_route( $this->namespace, '/exchange_rule', [	
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'insert_exchange_rule'],	
				'args' => [					
					'name' => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],
					'type' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],					
					'type_file' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'orderby' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'order' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'headings' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'splitting_array' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'exchange_option' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'time' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'split_into_files' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'encoding' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],			
					'columns' => ['required' => false],					
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('view_product_exporter');
				}
			]			
		]);						
		register_rest_route( $this->namespace, '/exchange_rule/(?P<id>\d+)', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_API', 'get_exchange_rule'],				
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_product_exporter');
				}
			],
			[ // Сохранить
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'update_exchange_rule'],	
				'args' => [					
					'name' => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],
					'active' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'refill' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'type_prices' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'products' => ['type' => 'array', 'required' => false],					
					'start_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
					'end_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
					'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
					'conditions' => ['type' => 'object', 'required' => false],
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_product_exporter');
				}				
			],
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_API', 'delete_exchange_rule'],	
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_product_exporter');
				}			
			]				
		]);
		register_rest_route( $this->namespace, '/exchange_rule/download/(?P<id>\d+)', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_API', 'download_exchange_rule'],				
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_product_exporter');
				}
			]			
		]);		
		register_rest_route( $this->namespace, '/groups', array(		
			[ 
				'methods'  => 'GET,POST',
				'callback' => ['USAM_API', 'get_groups'],	
				'args' => [
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],		
					'id' => ['type' => 'integer', 'required' => false],					
					'type' => ['type' => 'string', 'required' => false],
					'orderby' => ['type' => 'string', 'required' => false, 'default' => 'sort'],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'ASC', 'required' => false],
					'count' => ['type' => 'integer', 'required' => false], 
					'paged' => ['type' => 'integer', 'required' => false],
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || usam_check_is_employee();
				}
			],				
			[ 
				'methods'  => 'PUT',
				'callback' => ['USAM_API', 'update_groups'],	
				'args' => [					
					'items' => ['type' => 'array', 'required' => true],	
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || usam_check_is_employee();
				}
			],				
		));		
		register_rest_route( $this->namespace, '/group', array(				
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'insert_group'],	
				'args' => [					
					'sort' => ['type' => 'integer', 'required' => false],
					'type' => ['type' => 'string', 'required' => true],
					'name' => ['type' => 'string', 'required' => false],
					'code' => ['type' => 'string', 'required' => false],
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || usam_check_is_employee();
				}
			]			
		));		
		register_rest_route( $this->namespace, '/group/(?P<id>\d+)', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_API', 'get_group'],				
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || usam_check_is_employee();
				}
			],
			[ // Сохранить
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'update_group'],	
				'args' => [					
					'sort' => ['type' => 'integer', 'required' => false],
					'name' => ['type' => 'string', 'required' => false],
					'code' => ['type' => 'string', 'required' => false],
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || usam_check_is_employee();
				}				
			],
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_API', 'delete_group'],	
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || usam_check_is_employee();
				}			
			]				
		]);	
		register_rest_route( $this->namespace, '/newsletters', array(		
			[ 
				'methods'  => 'GET,POST',
				'callback' => ['USAM_Newsletter_API', 'get_newsletters'],	
				'args' => [
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],	
					'class' => ['type' => 'array,object,string', 'required' => false],	
					'type' => ['type' => 'array,object,string', 'required' => false],
					'orderby' => ['type' => 'string', 'required' => false, 'default' => 'sort'],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'ASC', 'required' => false],
					'count' => ['type' => 'integer', 'required' => false], 
					'paged' => ['type' => 'integer', 'required' => false],					
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || usam_check_is_employee();
				}
			]				
		));		
		register_rest_route( $this->namespace, '/newsletter', [
			[
				'methods'  => 'POST',
				'callback' => ['USAM_Newsletter_API', 'insert_newsletter'],	
				'args' => [
					'subject' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'template' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'sent_at' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'status' => ['type' => 'integer', 'required' => false],
					'mailbox_id' => ['type' => 'integer', 'required' => false],
					'type' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'class' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'start_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'repeat_days' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				],
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('view_newsletter');
				}, 
			]
		]);			
		register_rest_route( $this->namespace, '/newsletter/(?P<id>\d+)', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_Newsletter_API', 'get_newsletter'],					
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_newsletter');
				}
			],
			[ // Сохранить
				'methods'  => 'POST',
				'callback' => ['USAM_Newsletter_API', 'update_newsletter'],	
				'args' => [
					'subject' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'template' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'sent_at' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'status' => ['type' => 'integer', 'required' => false],
					'mailbox_id' => ['type' => 'integer', 'required' => false],
					'type' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'class' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'start_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'repeat_days' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_newsletter');
				}
			],
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_Newsletter_API', 'delete_newsletter'],	
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_newsletter');
				}			
			]			
		]);	
		register_rest_route( $this->namespace, '/newsletter/(?P<id>\d+)/preview', [		
			[ // Получить
				'methods'  => 'GET',
				'args' => [					
					'email' => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_email'],
				],
				'callback' => ['USAM_Newsletter_API', 'sent_preview_newsletter'],				
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_newsletter');
				}
			],			
		]);			
		register_rest_route( $this->namespace, '/tracking/(?P<number>\S+)', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_API', 'get_tracking'],				
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],			
		]);			
		register_rest_route( $this->namespace, '/sets', [		
			[ // Получить
				'methods'  => 'GET,POST',
				'callback' => ['USAM_API', 'get_sets'],				
				'args' => [		
					'status' => ['type' => 'string', 'required' => false],
					'type_price' => ['type' => 'string', 'required' => false],					
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],			
		]);	
		register_rest_route( $this->namespace, '/set/(?P<id>\d+)', [
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_API', 'get_set'],				
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],			
		]);		
		register_rest_route( $this->namespace, '/support_messages', [		
			[ // Получить
				'methods'  => 'GET,POST',
				'callback' => ['USAM_API', 'get_support_messages'],				
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || USAM_Request_Processing::verify_nonce( $request ) && is_user_logged_in();
				}
			],	
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'add_support_message'],				
				'args' => [		
					'message' => ['type' => 'string', 'required' => true],
					'subject' => ['type' => 'string', 'required' => true],					
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || USAM_Request_Processing::verify_nonce( $request ) && is_user_logged_in();
				}
			],		
		]);	
		register_rest_route( $this->namespace, '/support_message/(?P<id>\d+)', [
			[
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'update_support_message'],				
				'args' => [		
					'read' => ['type' => 'integer', 'required' => false],			
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || USAM_Request_Processing::verify_nonce( $request ) && is_user_logged_in();
				}
			],			
		]);	
		register_rest_route( $this->namespace, '/visits', [		
			[ // Получить
				'methods'  => 'GET,POST',
				'callback' => ['USAM_API', 'get_visits'],				
				'args' => [
					'add_fields' => ['type' => 'string,array', 'required' => false],
					'contact_id' => ['type' => 'integer', 'required' => false],	
					'orderby' => ['type' => 'string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],	
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'paged' => ['type' => 'integer', 'required' => false],		
					'meta_query' => ['type' => 'array', 'required' => false],							
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_crm') || current_user_can('view_feedback');
				}
			]
		]);
		register_rest_route( $this->namespace, '/reports', [		
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'get_reports'],				
				'args' => [
					'type' => ['type' => 'string', 'required' => false],										
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_reports');
				}
			]
		]);
		register_rest_route( $this->namespace, '/tables/columns', [		
			[
				'methods'  => 'POST',
				'callback' => ['USAM_Documents_API', 'get_columns_documents'],	
				'args' => [					
					'types' => ['type' => 'array', 'required' => true],					
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],				
		]);
		register_rest_route( $this->namespace, '/table/columns', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_API', 'get_table_columns'],				
				'args' => [
					'type' => ['type' => 'string', 'required' => true],		
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || USAM_Request_Processing::verify_nonce( $request ) && is_user_logged_in();
				}
			],
			[
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'save_table_columns'],				
				'args' => [
					'type' => ['type' => 'string', 'required' => true],	
					'columns' => ['type' => 'array', 'required' => false],						
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || USAM_Request_Processing::verify_nonce( $request ) && is_user_logged_in();
				}
			]
		]);
		register_rest_route( $this->namespace, '/applications', [		
			[ 
				'methods'  => 'GET,POST',
				'callback' => ['USAM_API', 'get_applications'],				
				'args' => [
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],			
					'group' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'installed' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],					
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_applications');
				}
			]
		]);
		register_rest_route( $this->namespace, '/oauth/yandex', [		
			[ 
				'methods'  => 'GET,POST',
				'callback' => ['USAM_Oauth', 'yandex'],				
				'args' => [
					'access_token' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'refresh_token' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'token_type' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'expires_in' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'absint'],						
				],
				'permission_callback' => false
			]
		]);			
		register_rest_route( $this->namespace, '/gallery', [		
			[ 
				'methods'  => 'GET,POST',
				'callback' => ['USAM_API', 'get_gallery'],				
				'args' => [
					'fields' => ['type' => ['string','array'], 'required' => false],
					'category__in' => ['type' => 'array', 'required' => false],					
					'add_fields' => ['type' => 'string,array', 'required' => false],
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'category_name' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],	
					'paged' => ['type' => 'integer', 'required' => false],
					'count' => ['type' => 'integer', 'required' => false],
					'orderby' => ['type' => 'string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],
				],
				'permission_callback' => false
			]
		]);	
		register_rest_route( $this->namespace, '/pages', [		
			[ 
				'methods'  => 'GET,POST',
				'callback' => ['USAM_API', 'get_pages'],				
				'args' => [
					'fields' => ['type' => ['string','array'], 'required' => false],		
					'add_fields' => ['type' => 'string,array', 'required' => false],
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'paged' => ['type' => 'integer', 'required' => false],
					'count' => ['type' => 'integer', 'required' => false],
					'orderby' => ['type' => 'string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],
				],
				'permission_callback' => false
			]
		]);	
		register_rest_route( $this->namespace, '/agreements', [		
			[ 
				'methods'  => 'GET,POST',
				'callback' => ['USAM_API', 'get_agreements'],				
				'args' => [
					'fields' => ['type' => ['string','array'], 'required' => false],
					'category__in' => ['type' => 'array', 'required' => false],					
					'add_fields' => ['type' => 'string,array', 'required' => false],
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'category_name' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],	
					'paged' => ['type' => 'integer', 'required' => false],
					'count' => ['type' => 'integer', 'required' => false],
					'orderby' => ['type' => 'string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],
				],
				'permission_callback' => false
			]
		]);	
		register_rest_route( $this->namespace, '/posts', [		
			[ 
				'methods'  => 'GET,POST',
				'callback' => ['USAM_API', 'get_posts'],				
				'args' => [
					'fields' => ['type' => ['string','array'], 'required' => false],
					'category__in' => ['type' => 'array', 'required' => false],					
					'add_fields' => ['type' => 'string,array', 'required' => false],
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'category_name' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],	
					'paged' => ['type' => 'integer', 'required' => false],
					'count' => ['type' => 'integer', 'required' => false],
					'orderby' => ['type' => 'string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],
				],
				'permission_callback' => false
			]
		]);	
		register_rest_route( $this->namespace, '/post/(?P<id>\d+)', [		
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'save_post'],				
				'args' => [
					'post_status' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
				],
				'permission_callback' => false
			]
		]);
		register_rest_route( $this->namespace, '/verification/phone/(?P<number>\S+)', [		
			[ 
				'methods'  => 'GET',
				'callback' => ['USAM_Profile_API', 'verification_phone'],				
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || USAM_Request_Processing::verify_nonce( $request ) && is_user_logged_in();
				}
			],
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_Profile_API', 'check_phone_verification_code'],				
				'args' => [
					'code' => ['type' => 'integer', 'required' => true],
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || USAM_Request_Processing::verify_nonce( $request ) && is_user_logged_in();
				}
			]
		]);	
		register_rest_route( $this->namespace, '/theme', [		
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'save_theme_options'],				
				'args' => [
					'options' => ['type' => 'object', 'required' => true],
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('edit_theme_options');
				}
			]
		]);		
		register_rest_route( $this->namespace, '/theme/edit', [		
			[ 
				'methods'  => 'GET',
				'callback' => ['USAM_API', 'change_theme_edit'],		
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('edit_theme_options');
				}
			]
		]);
		register_rest_route( $this->namespace, '/trigger', [	
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'insert_trigger'],	
				'args' => [					
					'title' => ['type' => 'string', 'required' => true, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'description' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'sort' => ['type' => 'integer', 'required' => false],
					'active' => ['type' => 'integer', 'required' => false],
					'event' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'conditions' => ['type' => 'array,object,string', 'required' => false],	
					'actions' => ['type' => 'array,object,string', 'required' => false],				
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('view_triggers');
				}
			]			
		]);		
		register_rest_route( $this->namespace, '/trigger/(?P<id>\d+)', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_API', 'get_trigger'],				
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_triggers');
				}
			],
			[ // Сохранить
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'update_trigger'],	
				'args' => [					
					'title' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'description' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'sort' => ['type' => 'integer', 'required' => false],
					'active' => ['type' => 'integer', 'required' => false],
					'event' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'conditions' => ['type' => 'array,object,string', 'required' => false],	
					'actions' => ['type' => 'array,object,string', 'required' => false],		
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_triggers');
				}				
			],
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_API', 'delete_trigger'],	
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_triggers');
				}			
			]				
		]);	
		register_rest_route( $this->namespace, '/subscription', [	
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'insert_subscription'],	
				'args' => [					
					'customer_type' => ['type' => 'string', 'required' => true, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'status' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'customer_id' => ['type' => 'integer', 'required' => true],
					'products' => [
						'id' => ['type' => 'integer', 'required' => false],
						'product_id' => ['type' => 'integer', 'required' => false],
						'quantity' => ['type' => 'float', 'required' => false],	
						'price' => ['type' => 'float', 'required' => false],	
						'old_price' => ['type' => 'float', 'required' => false],	
						'unit_measure' => ['type' => 'string', 'required' => false],				
					],
					'days' => ['type' => 'integer', 'required' => false],					
					'start_date' => ['type' => 'string', 'required' => true, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
					'end_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('add_subscription');
				}
			]			
		]);				
		register_rest_route( $this->namespace, '/subscription/(?P<id>\d+)', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_API', 'get_subscription'],				
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_subscriptions');
				}
			],
			[ // Сохранить
				'methods'  => 'POST',
				'callback' => ['USAM_API', 'update_subscription'],	
				'args' => [					
					'customer_type' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'status' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'customer_id' => ['type' => 'integer', 'required' => false],
					'products' => [
						'id' => ['type' => 'integer', 'required' => false],
						'product_id' => ['type' => 'integer', 'required' => false],
						'quantity' => ['type' => 'float', 'required' => false],	
						'price' => ['type' => 'float', 'required' => false],	
						'old_price' => ['type' => 'float', 'required' => false],	
						'unit_measure' => ['type' => 'string', 'required' => false],				
					],
					'days' => ['type' => 'integer', 'required' => false],					
					'start_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
					'end_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
					'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],					
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('edit_subscription');
				}				
			],
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_API', 'delete_subscription'],	
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('edit_subscription');
				}			
			]				
		]);
		register_rest_route( $this->namespace, '/subscription/renew/(?P<id>\d+)', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_API', 'renew_subscription'],				
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('edit_subscription');
				}
			],			
		]);
		register_rest_route( $this->namespace, '/sites', [		
			[ // Получить
				'methods'  => 'GET,POST',
				'callback' => ['USAM_API', 'get_sites'],				
				'args' => [
					'mysite' => ['type' => 'integer', 'required' => false],
					'location_id' => ['type' => 'integer', 'required' => false],	
					'orderby' => ['type' => 'string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],	
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'paged' => ['type' => 'integer', 'required' => false],		
					'meta_query' => ['type' => 'array', 'required' => false],							
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_seo');
				}
			]
		]);
		register_rest_route( $this->namespace, '/sales_area', [
			[
				'methods'  => 'GET,POST',
				'args' => [					
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'paged' => ['type' => 'integer', 'required' => false],
					'orderby' => ['type' => 'integer,string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'ASC', 'required' => false],	
					'include' => ['type' => 'array', 'required' => false],						
				],
				'callback' => array('USAM_API', 'get_sales_area'),				
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('view_all_files') || current_user_can('view_my_files');
				},
			],		
		]);			
		register_rest_route( $this->namespace, '/template/(?P<name>\S+)', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_API', 'get_template'],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_interface');
				}
			]
		]);
		register_rest_route( $this->namespace, '/change_history', [
			[
				'methods'  => 'GET,POST',
				'args' => [					
					'object_type' => ['type' => 'integer,string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],
					'object_id' => ['type' => 'integer', 'required' => true],	
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'paged' => ['type' => 'integer', 'required' => false],
					'orderby' => ['type' => 'integer,string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],	
					'include' => ['type' => 'array', 'required' => false],											
				],
				'callback' => ['USAM_CRM_API', 'get_change_history'],				
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('store_section');
				},
			],		
		]);
		register_rest_route( $this->namespace, '/manager/affair/complete/(?P<id>\d+)', [
			[
				'methods'  => 'GET',				
				'callback' => ['USAM_CRM_API', 'affair_complete'],				
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || USAM_Request_Processing::verify_nonce( $request ) && is_user_logged_in();
				}
			],		
		]);		
		register_rest_route( $this->namespace, '/settings', [
			[
				'methods'  => 'POST',				
				'callback' => ['USAM_API', 'update_options'],				
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') ||  current_user_can('view_interface');
				}
			],		
		]);	
		register_rest_route( $this->namespace, '/htmlblock/(?P<id>\d+)', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_API', 'get_htmlblock'],				
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
			],				
		]);	
		register_rest_route( $this->namespace, '/htmlblocks', [		
			[ // Получить
				'methods'  => 'GET,POST',
				'callback' => ['USAM_API', 'get_htmlblocks'],				
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
			],				
		]);			
	}
}
?>