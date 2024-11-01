<?php
new USAM_CRM_Rest_API();
class USAM_CRM_Rest_API
{	
	protected $namespace = 'usam/v1';
	
	function __construct( )
	{	
		add_action('rest_api_init', [$this,'register_routes'] );	
	}
			
	public function register_routes()
    {				
		require_once( USAM_FILE_PATH . '/includes/exchange/API/rest_route/seo_API.class.php' );
		register_rest_route( $this->namespace, '/bonus/cards', [
			[
				'methods'  => 'GET,POST',
				'callback' => ['USAM_CRM_API', 'get_bonus_cards'],	
				'args' => [
					'fields' => ['type' => 'string,array', 'required' => false],
					'add_fields' => ['type' => 'string,array', 'required' => false],
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'search_columns' => ['type' => 'string,array', 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('sanitize_title', (array)$param); } ],
					'status' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('view_bonus_cards');
				}
			],
		]);		
		register_rest_route( $this->namespace, '/bonus/card/(?P<number>\S+)', [		
			[
				'methods'  => 'GET',
				'callback' => array('USAM_CRM_API', 'get_bonus_card'),					
				'permission_callback' => function( $request ){	
					return current_user_can('universam_api') || current_user_can('view_bonus_cards');
				}
			],					
		]);
		register_rest_route( $this->namespace, '/bonus/transaction', [				
			[				
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'insert_bonus_by_user_id'],	
				'args' => [					
					'user_id' => ['type' => 'number', 'required' => true],
					'order_id' => ['type' => 'number', 'required' => false, 'sanitize_callback' => 'absint'],
					'object_id' => ['type' => 'number', 'required' => false, 'sanitize_callback' => 'absint'],
					'object_type' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'type_transaction' => ['type' => 'number', 'required' => true],
					'description' => ['type' => 'string', 'required' => true, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],
					'bonus' => ['type' => 'number', 'required' => true],
				],
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('edit_bonus_card');
				}
			],			
		]);
		register_rest_route( $this->namespace, '/bonus/transactions', [				
			[				
				'methods'  => 'GET,POST',
				'callback' => ['USAM_CRM_API', 'get_bonus_transactions'],	
				'args' => [					
					'code' => ['type' => 'number', 'required' => false],
					'type_transaction' => ['type' => 'number', 'required' => false],
					'order_id' => ['type' => 'number', 'required' => false, 'sanitize_callback' => 'absint'],
				],
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('view_bonus_cards');
				}
			]		
		]);		
		register_rest_route( $this->namespace, '/bonus/transaction/(?P<number>\S+)', [			
			[				
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'insert_bonus'],	
				'args' => [					
					'type_transaction' => ['type' => 'number', 'required' => true],
					'description' => ['type' => 'string', 'required' => true, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],
					'order_id' => ['type' => 'number', 'required' => false, 'sanitize_callback' => 'absint'],
					'bonus' => ['type' => 'number', 'required' => true],
				],
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('edit_bonus_card');
				}
			],
			[				
				'methods'  => 'DELETE',
				'callback' => ['USAM_CRM_API', 'delete_bonus'],					
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('delete_bonus_card');
				}
			]			
		]);	
		register_rest_route( $this->namespace, '/accounts', [
			[				
				'permission_callback' => false,
				'methods'  => 'GET,POST',
				'callback' => array('USAM_CRM_API', 'get_accounts'),	
				'args' => [
					'search' => array('type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text'] ),
					'paged' => array('type' => 'integer', 'required' => false ),
					'status' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'count' => array('type' => 'integer', 'required' => false ),
					'fields' => ['type' => ['string','array'], 'required' => false],
					'company' => ['type' => 'integer', 'required' => false],				
				],
				'permission_callback' => function( $request ){	
					return current_user_can('universam_api') || current_user_can('view_customer_accounts');
				}
			],
			[
				'methods'  => 'PUT',
				'callback' => ['USAM_CRM_API', 'save_accounts'],				
				'args' => [					
					'items' => ['type' => 'array', 'required' => true],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('edit_company') || current_user_can('add_company');
				},
			],	
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_CRM_API', 'delete_accounts'],					
				'args' => [
					'items' => ['type' => 'array', 'required' => true],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('delete_company');
				},
			],	
		]);
		register_rest_route( $this->namespace, '/account', [	
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'add_account'],	
				'args' => [					
					'name' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'status' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],					
					'user_id' => ['type' => 'integer', 'required' => false],	
					'properties' => ['type' => ['object'], 'required' => false],					
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],			
		]);	
		register_rest_route( $this->namespace, '/account/(?P<id>\d+)', [		
			[ 
				'methods'  => 'GET',
				'callback' => ['USAM_CRM_API', 'get_account'],				
				'permission_callback' => function( $request ){	
					return current_user_can('universam_api') || current_user_can('view_customer_accounts');
				}
			],
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'save_account'],	
				'args' => [					
					'status' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'name' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'user_id' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'user_ids' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],		
					'connection_id' => ['type' => 'array', 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('absint', $param); } ],	
					'properties' => ['type' => ['object'], 'required' => false],						
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_CRM_API', 'delete_account'],					
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('delete_customer_account');
				},
			],		
		]);
		register_rest_route( $this->namespace, '/account/transactions', [				
			[				
				'methods'  => 'GET,POST',
				'callback' => ['USAM_CRM_API', 'get_account_transactions'],	
				'args' => [					
					'account_id' => ['type' => 'number', 'required' => false],
					'type_transaction' => ['type' => 'number', 'required' => false],
					'order_id' => ['type' => 'number', 'required' => false, 'sanitize_callback' => 'absint'],
				],
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('edit_bonus_card');
				}
			]		
		]);		
		register_rest_route( $this->namespace, '/account/transaction/(?P<id>\d+)', [			
			[				
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'insert_account_transaction'],	
				'args' => [					
					'type_transaction' => ['type' => 'number', 'required' => true],
					'description' => ['type' => 'string', 'required' => true, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],
					'order_id' => ['type' => 'number', 'required' => false, 'sanitize_callback' => 'absint'],
					'sum' => ['type' => 'number', 'required' => true, 'sanitize_callback' => 'absint'],
				],
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('view_customer_accounts');
				}
			],
			[				
				'methods'  => 'DELETE',
				'callback' => ['USAM_CRM_API', 'delete_account_transaction'],					
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('edit_customer_account');
				}
			]			
		]);
		register_rest_route( $this->namespace, '/users', [
			[
				'methods'  => 'GET,POST',
				'callback' => ['USAM_CRM_API', 'get_users'],	
				'args' => array(
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'company_id' => ['type' => 'integer', 'required' => false],	
					'paged' => ['type' => 'integer', 'required' => false],					
					'count' => ['type' => 'integer', 'required' => false],
					'fields' => ['type' => ['string','array'], 'required' => false],
				),
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('view_contacts');
				}
			],			
		]);		
		register_rest_route( $this->namespace, '/user/(?P<id>\d+)', [	
			[
				'methods'  => 'GET',
				'callback' => ['USAM_CRM_API', 'get_user'],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('view_contact');
				}
			],
		]);
		register_rest_route( $this->namespace, '/user/password', [	
			[
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'save_password'],
				'args' => [
					'pass' => ['type' => 'string', 'required' => true],
				],
				'permission_callback' => function( $request ){
					return is_user_logged_in();
				}
			],
		]);
		register_rest_route( $this->namespace, '/comments', [
			[ 
				'methods'  => 'GET,POST',
				'callback' => ['USAM_CRM_API', 'get_comments'],	
				'args' => [
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'user_id' => ['type' => 'integer', 'required' => false],
					'object_id' => ['type' => 'integer', 'required' => false],
					'object_type' => ['type' => 'string', 'required' => false],
					'status' => ['type' => 'string', 'required' => false],
					'orderby' => ['type' => 'string', 'required' => false, 'default' => 'id'],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],
					'count' => ['type' => 'integer', 'required' => false], 
					'paged' => ['type' => 'integer', 'required' => false],
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || usam_check_is_employee();
				}
			]		
		]);		
		register_rest_route( $this->namespace, '/comment', [
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'insert_comment'],	
				'args' => [
					'user_id' => ['type' => 'integer', 'required' => false],
					'object_id' => ['type' => 'integer', 'required' => true],
					'object_type' => ['type' => 'string', 'required' => true],
					'message' => ['type' => 'string', 'required' => true],					
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || usam_check_is_employee();
				}
			],				
		]);	
		register_rest_route( $this->namespace, '/comment/(?P<id>\d+)', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_CRM_API', 'get_comment'],				
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || usam_check_is_employee();
				}
			],
			[ // Сохранить
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'update_comment'],	
				'args' => [					
					'object_id' => ['type' => 'integer', 'required' => false],					
					'object_type' => ['type' => 'string', 'required' => false],
					'message' => ['type' => 'string', 'required' => false],
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || usam_check_is_employee();
				}
			],
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_CRM_API', 'delete_comment'],	
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || usam_check_is_employee();
				}			
			]				
		]);	
		register_rest_route( $this->namespace, '/livefeed', [
			[ 
				'methods'  => 'GET,POST',
				'callback' => ['USAM_CRM_API', 'get_livefeed'],	
				'args' => [
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'user_id' => ['type' => 'integer', 'required' => false],
					'object_id' => ['type' => 'integer', 'required' => false],
					'object_type' => ['type' => 'string', 'required' => false],
					'status' => ['type' => 'string', 'required' => false],
					'orderby' => ['type' => 'string', 'required' => false, 'default' => 'date'],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],
					'count' => ['type' => 'integer', 'required' => false], 
					'paged' => ['type' => 'integer', 'required' => false],
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || usam_check_is_employee();
				}
			]		
		]);
		register_rest_route( $this->namespace, '/reviews', [		
			[ // Получить
				'methods'  => 'GET,POST',
				'callback' => ['USAM_CRM_API', 'get_reviews'],				
				'args' => [		
					'status' => ['type' => 'string,number', 'required' => false],	
					'add_fields' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],	
					'meta_query' => ['type' => 'array', 'required' => false],
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],			
		]);
		register_rest_route( $this->namespace, '/review/(?P<id>\d+)', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_CRM_API', 'get_review'],				
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],			
		]);
		register_rest_route( $this->namespace, '/knowledge_base', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_CRM_API', 'get_knowledge_base'],				
				'args' => [		
					'search' => ['type' => 'string', 'required' => true],				
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || USAM_Request_Processing::verify_nonce( $request ) && is_user_logged_in();
				}
			],			
		]);
		register_rest_route( $this->namespace, '/companies', [
			[				
				'permission_callback' => false,
				'methods'  => 'GET,POST',
				'callback' => array('USAM_CRM_API', 'get_companies'),	
				'args' => [
					'add_fields' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'search' => array('type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text'] ),
					'paged' => array('type' => 'integer', 'required' => false ),
					'status' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'count' => array('type' => 'integer', 'required' => false ),
					'fields' => ['type' => ['string','array'], 'required' => false],
					'connection' => ['type' => ['string','array'], 'required' => false],					
					'user_id' => ['type' => 'integer', 'required' => false],				
				]
			],	
		]);
		register_rest_route( $this->namespace, '/companies/search', [	
			[
				'methods'  => 'GET,POST',
				'callback' => ['USAM_CRM_API', 'search_companies'],	
				'args' => [
					'search' => array('type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text'] ),
					'inn' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text'] ],
					'ppc' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text'] ],
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],			
		]);	
		register_rest_route( $this->namespace, '/directory/companies', [	
			[
				'methods'  => 'GET',
				'callback' => ['USAM_CRM_API', 'search_directory_companies'],	
				'args' => [
					'search' => array('type' => 'string', 'required' => true, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text'] ),
				],
				'permission_callback' => function( $request ){	
					return current_user_can('universam_api') || current_user_can('edit_company');
				}
			],			
		]);		
		register_rest_route( $this->namespace, '/company/search', [	
			[			
				'methods'  => 'GET',
				'callback' => ['USAM_CRM_API', 'search_company'],	
				'args' => [
					'inn' => ['type' => 'string', 'required' => true, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text'] ],
					'ppc' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text'] ],
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],			
		]);	
		register_rest_route( $this->namespace, '/company', [	
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'add_company'],	
				'args' => [					
					'name' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'status' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],					
					'user_id' => ['type' => 'integer', 'required' => false],
					'revenue' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'usam_string_to_float'],					
					'employees' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],	
					'code' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'logo' => ['type' => 'integer', 'required' => false],		
					'description' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],
					'type_price' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'properties' => ['type' => ['object'], 'required' => false],					
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],		
		]);					
		register_rest_route( $this->namespace, '/company/(?P<id>\d+)', [		
			[ 
				'methods'  => 'GET',
				'callback' => ['USAM_CRM_API', 'get_company'],				
				'permission_callback' => function( $request ){	
					return current_user_can('universam_api') || current_user_can('view_company');
				}
			],
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'save_company'],	
				'args' => [				
					'revenue' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'usam_string_to_float'],
					'type_price' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'code' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'employees' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],					
					'description' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],
					'status' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'name' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'user_id' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'logo' => ['type' => 'integer', 'required' => false],		
					'user_ids' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],		
					'connection_id' => ['type' => 'array', 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('absint', $param); } ],	
					'properties' => ['type' => ['object'], 'required' => false],						
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_CRM_API', 'delete_company'],					
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('delete_company');
				},
			],		
		]);		
		register_rest_route( $this->namespace, '/profile', [		
			[ 
				'methods'  => 'GET',
				'callback' => ['USAM_CRM_API', 'get_profile'],				
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],
			[
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'save_profile'],	
				'args' => [					
					'sex' => ['type' => 'string', 'required' => false],
					'status' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'lastname' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'firstname' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'patronymic' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'email' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_email'],	
					'birthday' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			]
		]);			
		register_rest_route( $this->namespace, '/contacts', [
			[
				'methods'  => 'GET,POST',
				'callback' => ['USAM_CRM_API', 'get_contacts'],	
				'args' => [
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'paged' => ['type' => 'integer', 'required' => false],
					'gender' => ['type' => 'string', 'required' => false],					
					'status' => ['type' => ['array', 'string'], 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string'] ],
					'status__not_in' => ['type' => ['array', 'string'], 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'source' => ['type' => ['array', 'string'], 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'source__not_in' => ['type' => ['array', 'string'], 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'role__in' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'count' => ['type' => 'integer', 'required' => false],					
					'fields' => ['type' => ['string','array'], 'required' => false],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('view_contacts') || current_user_can('store_section');
				}
			],			
		]);		
		register_rest_route( $this->namespace, '/contact/(?P<id>\d+)', [
			[ 
				'methods'  => 'GET',
				'callback' => ['USAM_CRM_API', 'get_contact'],				
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'save_contact'],	
				'args' => [					
					'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
					'sex' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'post' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'type_price' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'status' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'lastname' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'firstname' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'patronymic' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'birthday' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
					'email' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_email'],	
					'email' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_email'],	
					'department' => ['type' => 'integer', 'required' => false],	
					'company_id' => ['type' => 'integer', 'required' => false],		
					'notifications_sms' => ['type' => 'integer,boolean', 'required' => false, 'sanitize_callback' => 'absint'],		
					'notifications_email' => ['type' => 'integer,boolean', 'required' => false, 'sanitize_callback' => 'absint'],		
					'foto' => ['type' => 'integer,boolean', 'required' => false, 'sanitize_callback' => 'absint'],					
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('view_contacts');
				},
			],
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_CRM_API', 'delete_contact'],					
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('delete_contact');
				},
			],		
		]);		
		register_rest_route( $this->namespace, '/contact', [
			[ 
				'methods'  => 'GET',
				'callback' => ['USAM_CRM_API', 'get_contact'],				
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'insert_contact'],	
				'args' => [					
					'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
					'sex' => ['type' => 'string', 'required' => false],
					'status' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'lastname' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'firstname' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'patronymic' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],					
					'email' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_email'],	
					'birthday' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('add_contact');
				},
			]		
		]);			
		register_rest_route( $this->namespace, '/addresses', [	
			[
				'methods'  => 'GET,POST',
				'callback' => array('USAM_CRM_API', 'get_addresses'),	
				'args' => array(
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'paged' => ['type' => 'integer', 'required' => false],					
					'count' => ['type' => 'integer', 'required' => false],
					'contact_id' => ['type' => 'integer,string', 'required' => false],
				),
				'permission_callback' => false,
			]		
		]);		
		register_rest_route( $this->namespace, '/address', [	
			[
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'insert_address'],
				'args' => [
					'index' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'street' => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],					
					'house' => ['type' => 'string,integer', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],
					'flat' => ['type' => 'string,integer', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'floor' => ['type' => 'integer', 'required' => false],
					'frame' => ['type' => 'string,integer', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],					
					'contact_id' => ['type' => 'integer', 'required' => false],
					'location_id' => ['type' => 'integer', 'required' => false],
					'main' => ['type' => 'integer,boolean', 'required' => false],
				],
				'permission_callback' => false,
			]		
		]);		
		register_rest_route( $this->namespace, '/address/(?P<id>\d+)', [
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_CRM_API', 'get_address'],
				'permission_callback' => false,
			],
			[ // Сохранить
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'save_address'],	
				'args' => [					
					'index' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'street' => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],					
					'house' => ['type' => 'string,integer', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],
					'flat' => ['type' => 'string,integer', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'floor' => ['type' => 'integer', 'required' => false],
					'frame' => ['type' => 'string,integer', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],					
					'contact_id' => ['type' => 'integer', 'required' => false],
					'location_id' => ['type' => 'integer', 'required' => false],
					'main' => ['type' => 'integer,boolean', 'required' => false],	
				],
				'permission_callback' => false,
			],
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_CRM_API', 'delete_address'],				
				'permission_callback' => function( $request ){	
					return is_user_logged_in();
				}
			],			
		]);
		if ( get_option('usam_website_type', 'store' ) == 'marketplace' )
		{
			register_rest_route( $this->namespace, '/sellers', [	
				[
					'methods'  => 'GET,POST',
					'callback' => ['USAM_CRM_API', 'get_sellers'],	
					'args' => [
						'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
						'paged' => ['type' => 'integer', 'required' => false],
						'gender' => ['type' => 'string', 'required' => false],					
						'status' => ['type' => ['array', 'string'], 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('sanitize_title', (array)$param); } ],
						'status__not_in' => ['type' => ['array', 'string'], 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('sanitize_title', (array)$param); } ],
						'source' => ['type' => ['array', 'string'], 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('sanitize_title', (array)$param); } ],
						'source__not_in' => ['type' => ['array', 'string'], 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('sanitize_title', (array)$param); } ],
						'role__in' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
						'count' => ['type' => 'integer', 'required' => false],
						'fields' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
						'add_fields' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					],
					'permission_callback' => function( $request ){
						return current_user_can('universam_api') || current_user_can('view_contacts');
					}
				],			
			]);
		}		
		register_rest_route( $this->namespace, '/coupons', array(		
			[
				'methods'  => 'GET,POST',
				'callback' => array('USAM_CRM_API', 'get_coupons'),	
				'args' => array(
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'paged' => ['type' => 'integer', 'required' => false],					
					'count' => ['type' => 'integer', 'required' => false],
					'user_id' => ['type' => 'integer,string', 'required' => false],
					'orderby' => ['type' => 'string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],	
				),
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || is_user_logged_in();
				}				
			]	
		));
		register_rest_route( $this->namespace, '/coupon', [			
			[
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'insert_coupon'],	
				'args' => [
					'coupon_code' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'description' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'start_date' => ['type' => 'string', 'required' => false],
					'end_date' => ['type' => 'string', 'required' => false],
					'amount_bonuses_author' => ['type' => 'integer', 'required' => false],
					'max_is_used' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'active' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api');
				}
			],			
		]);
		register_rest_route( $this->namespace, '/coupon/(?P<id>\d+)', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_CRM_API', 'get_coupon'],
				'permission_callback' => false,
			],
			[ // Сохранить
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'save_coupon'],	
				'args' => [					
					'user_id' => ['type' => 'integer', 'required' => false],
					'description' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'start_date' => ['type' => 'string', 'required' => false],
					'end_date' => ['type' => 'string', 'required' => false],
					'amount_bonuses_author' => ['type' => 'integer', 'required' => false],
					'max_is_used' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'active' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
				],
				'permission_callback' => function( $request ){	
					return current_user_can('universam_api');
				}
			],
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_CRM_API', 'delete_coupon'],				
				'permission_callback' => function( $request ){	
					return current_user_can('universam_api');
				}
			],			
		]);
		register_rest_route( $this->namespace, '/couriers', [
			[
				'methods'  => 'GET,POST',
				'callback' => ['USAM_CRM_API', 'get_couriers'],	
				'args' => [
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'paged' => ['type' => 'integer', 'required' => false],
					'status' => ['type' => ['array', 'string'], 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('sanitize_title', (array)$param); } ],
					'status__not_in' => ['type' => ['array', 'string'], 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('sanitize_title', (array)$param); } ],
					'source' => ['type' => ['array', 'string'], 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('sanitize_title', (array)$param); } ],
					'source__not_in' => ['type' => ['array', 'string'], 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('sanitize_title', (array)$param); } ],
					'count' => ['type' => 'integer', 'required' => false],
					'fields' => ['type' => ['string','array'], 'required' => false],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('view_employees');
				}
			],			
		]);
		register_rest_route( $this->namespace, '/employees', [
			[
				'methods'  => 'GET,POST',
				'callback' => ['USAM_CRM_API', 'get_employees'],	
				'args' => [
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'paged' => ['type' => 'integer', 'required' => false],
					'status' => ['type' => ['array', 'string'], 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('sanitize_title', (array)$param); } ],
					'status__not_in' => ['type' => ['array', 'string'], 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('sanitize_title', (array)$param); } ],
					'source' => ['type' => ['array', 'string'], 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('sanitize_title', (array)$param); } ],
					'source__not_in' => ['type' => ['array', 'string'], 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('sanitize_title', (array)$param); } ],
					'count' => ['type' => 'integer', 'required' => false],
					'fields' => ['type' => ['string','array'], 'required' => false],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('view_employees') || current_user_can('store_section');
				}
			],			
		]);		
		register_rest_route( $this->namespace, '/employee/(?P<id>\d+)', [		
			[ 
				'methods'  => 'GET',
				'callback' => ['USAM_CRM_API', 'get_employee'],				
				'args' => [				
					'add_fields' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
				],
				'permission_callback' => function( $request ){	
					return current_user_can('universam_api') || current_user_can('view_employees') || current_user_can('store_section');
				}
			],
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'save_employee'],	
				'args' => [					
					'status' => ['type' => 'string', 'required' => false],
					'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
					'start_work_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],				
					'department' => ['type' => 'integer', 'required' => false],	
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('edit_employee');
				},
			],
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_CRM_API', 'delete_employee'],					
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('delete_employee');
				},
			],		
		]);
		register_rest_route( $this->namespace, '/employee/dismissal/(?P<id>\d+)', [					
			[ 
				'methods'  => 'GET',
				'callback' => ['USAM_CRM_API', 'dismissal_employee'],					
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('edit_employee');
				},
			],		
		]);		
		register_rest_route( $this->namespace, '/employee', [		
			[ 
				'methods'  => 'GET',
				'callback' => ['USAM_CRM_API', 'get_employee'],				
				'args' => [				
					'add_fields' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
				],
				'permission_callback' => function( $request ){	
					return current_user_can('universam_api') || current_user_can('view_employees') || current_user_can('store_section');
				}
			],	
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'insert_employee'],				
				'args' => [				
					'status' => ['type' => 'string', 'required' => false],
					'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
					'start_work_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],				
					'department' => ['type' => 'integer', 'required' => false],	
				],
				'permission_callback' => function( $request ){	
					return current_user_can('universam_api') || current_user_can('view_employees') || current_user_can('store_section');
				}
			],				
		]);
		register_rest_route( $this->namespace, '/department', [
			[
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'insert_department'],	
				'args' => [
					'name' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'chief' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],			
					'company' => ['type' => 'integer', 'required' => false],	
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('edit_department');
				}
			],			
		]);	
		register_rest_route( $this->namespace, '/department/(?P<id>\d+)', [		
			[ 
				'methods'  => 'GET',
				'callback' => ['USAM_CRM_API', 'get_department'],				
				'permission_callback' => function( $request ){	
					return current_user_can('universam_api') || current_user_can('edit_department');
				}
			],
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'update_department'],	
				'args' => [					
					'name' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'chief' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],			
					'company' => ['type' => 'integer', 'required' => false],	
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('edit_department');
				},
			],
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_CRM_API', 'delete_department'],					
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('delete_department');
				},
			],		
		]);
		register_rest_route( $this->namespace, '/calendars', [		
			[
				'methods'  => 'GET,POST',
				'callback' => ['USAM_CRM_API', 'get_calendars'],
				'args' => [
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'paged' => ['type' => 'integer', 'required' => false],						
					'count' => ['type' => 'integer', 'required' => false],
					'fields' => ['type' => ['string','array'], 'required' => false],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || is_user_logged_in();
				}
			]	
		]);		
		register_rest_route( $this->namespace, '/calendars/user', [		
			[
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'save_user_calendar'],
				'args' => [
					'calendars' => ['type' => ['array'], 'required' => true],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || is_user_logged_in();
				}
			]	
		]);
		register_rest_route( $this->namespace, '/events', [		
			[
				'methods'  => 'GET,POST',
				'callback' => ['USAM_CRM_API', 'get_events'],
				'args' => [
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'paged' => ['type' => 'integer', 'required' => false],				
					'user_work' => ['type' => 'array,integer', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
					'author' => ['type' => 'array,integer,string', 'required' => false],
					'role' => ['type' => 'array,string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],					
					'reminder' => ['type' => 'integer', 'required' => false],
					'group' => ['type' => 'array,integer', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
					'calendar' => ['type' => 'array,integer', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
					'webform' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'company' => ['type' => 'array,integer', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
					'contact' => ['type' => 'array,integer', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
					'status' => ['type' => ['array', 'string'], 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('sanitize_title', (array)$param); } ],
					'status__not_in' => ['type' => ['array', 'string'], 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('sanitize_title', (array)$param); } ],
					'status_type' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'type' => ['type' => ['array', 'string'], 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('sanitize_title', (array)$param); } ],
					'type__not_in' => ['type' => ['array', 'string'], 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('sanitize_title', (array)$param); } ],
					'count' => ['type' => 'integer', 'required' => false],
					'fields' => ['type' => ['string','array'], 'required' => false],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || is_user_logged_in();
				}
			]	
		]);		
		register_rest_route( $this->namespace, '/event', [
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'insert_event'],	
				'args' => [				
					'title' => ['type' => 'string', 'required' => true, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'description' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],
					'request_solution' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],
					'type' => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_title'],	
					'status' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_title', 'default' => 'started'],	
					'calendar' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
					'importance' => ['type' => 'integer,boollean', 'required' => false],	
					'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],	
					'start' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],	
					'end' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
					'reminder_date' => ['type' => 'string,integer', 'required' => false],	
					'observer' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
					'participant' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
					'groups' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],		
					'files' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
					'links' => ['type' => 'array', 'required' => false],
				],
				'permission_callback' => function( $request ){
					$parameters = $request->get_json_params();		
					if ( !$parameters )
						$parameters = $request->get_body_params();
					return current_user_can('universam_api') || current_user_can('add_'.$parameters['type']);
				},
			]
		]);	
		register_rest_route( $this->namespace, '/event/(?P<id>\d+)', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_CRM_API', 'get_event'],			
				'args' => [				
					'add_fields' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],		
			[
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'update_event'],			
				'args' => [					
					'title' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'description' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],			
					'request_solution' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],
					'status' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_title'],	
					'calendar' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],	
					'importance' => ['type' => 'integer,boollean', 'required' => false],
					'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],	
					'start' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],	
					'end' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
					'reminder_date' => ['type' => 'string,integer', 'required' => false],						
					'observer' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
					'participant' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
					'groups' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
					'files' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
					'links' => ['type' => 'array', 'required' => false],
				],
				'permission_callback' => function( $request ){
					$parameters = $request->get_json_params();		
					if ( !$parameters )
						$parameters = $request->get_body_params();
					return current_user_can('universam_api') || current_user_can('edit_'.$parameters['type']);
				},
			],	
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_CRM_API', 'delete_event'],					
				'permission_callback' => function( $request ){
					$id = $request->get_param( 'id' );
					return current_user_can('universam_api') || usam_check_event_access($id, 'delete');
				},
			],			
		]);		
		register_rest_route( $this->namespace, '/event/actions', [		
			[
				'methods'  => 'GET',
				'callback' => ['USAM_CRM_API', 'get_event_actions'],				
				'args' => [					
					'event_id' => ['type' => 'integer', 'required' => true],			
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],	
			[
				'methods'  => 'PUT',
				'callback' => ['USAM_CRM_API', 'save_event_actions'],				
				'args' => [					
					'items' => ['type' => 'array', 'required' => true],
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],				
		]);
		register_rest_route( $this->namespace, '/event/action', [		
			[
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'add_event_action'],				
				'args' => [					
					'name' => ['type' => 'string', 'required' => true],			
					'event_id' => ['type' => 'integer', 'required' => true],		
					'status' => ['type' => 'integer', 'required' => false],	
					'sort' => ['type' => 'integer', 'required' => false],
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			]						
		]);
		register_rest_route( $this->namespace, '/event/link/(?P<id>\d+)', [		
			[ // Получить
				'methods'  => 'DELETE',
				'callback' => ['USAM_CRM_API', 'delete_link_event'],				
				'args' => [
					'object_id' => ['type' => 'integer', 'required' => false],
					'object_type' => ['type' => 'string', 'required' => false],			
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || usam_check_is_employee();
				}
			]		
		]);			
		register_rest_route( $this->namespace, '/event/action/(?P<id>\d+)', [		
			[
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'save_event_action'],				
				'args' => [					
					'name' => ['type' => 'string', 'required' => false],		
					'status' => ['type' => 'integer', 'required' => false],	
					'sort' => ['type' => 'integer', 'required' => false],
					'event_id' => ['type' => 'integer', 'required' => true],					
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],			
		]);			
		register_rest_route( $this->namespace, '/contactings', [		
			[
				'methods'  => 'GET,POST',
				'callback' => ['USAM_CRM_API', 'get_contactings'],
				'args' => [
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'paged' => ['type' => 'integer', 'required' => false],
					'author' => ['type' => 'array,integer,string', 'required' => false],					
					'group' => ['type' => 'array,integer', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],			
					'webform' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'company' => ['type' => 'array,integer', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
					'contact' => ['type' => 'array,integer', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
					'status' => ['type' => ['array', 'string'], 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('sanitize_title', (array)$param); } ],
					'status__not_in' => ['type' => ['array', 'string'], 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('sanitize_title', (array)$param); } ],
					'status_type' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],				
					'count' => ['type' => 'integer', 'required' => false],
					'fields' => ['type' => ['string','array'], 'required' => false],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || is_user_logged_in();
				}
			]	
		]);		
		register_rest_route( $this->namespace, '/contacting', [
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'insert_contacting'],	
				'args' => [								
					'request_solution' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],					
					'status' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_title', 'default' => 'started'],						
					'importance' => ['type' => 'integer,boollean', 'required' => false],	
					'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],						
					'groups' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],		
					'files' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
					'links' => ['type' => 'array', 'required' => false],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('add_contacting');
				},
			]
		]);	
		register_rest_route( $this->namespace, '/contacting/(?P<id>\d+)', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_CRM_API', 'get_contacting'],			
				'args' => [				
					'add_fields' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],		
			[
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'update_contacting'],			
				'args' => [						
					'request_solution' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],
					'status' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_title'],	
					'importance' => ['type' => 'integer,boollean', 'required' => false],
					'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],					
					'groups' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
					'files' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('edit_contacting');
				},
			],	
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_CRM_API', 'delete_contacting'],					
				'permission_callback' => function( $request ){
					$id = $request->get_param( 'id' );
					return current_user_can('universam_api') || current_user_can('delete_contacting');
				},
			],			
		]);	
		register_rest_route( $this->namespace, '/contacting/(?P<id>\d+)/order', [		
			[
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'add_order_contacting'],					
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('add_order');
				}
			]						
		]);	
		register_rest_route( $this->namespace, '/email/(?P<id>\d+)', [		
			[ 
				'methods'  => 'GET',
				'callback' => ['USAM_CRM_API', 'get_email'],			
				'args' => [				
					'add_fields' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_email');
				}
			],		
			[
				'methods'  => 'POST',
				'callback' => ['USAM_CRM_API', 'update_email'],			
				'args' => [					
					'to_email' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_email'],					
					'to_name' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],	
					'from_email' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_email'],		
					'from_name' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],					
					'subject' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],						
					'title' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],				
					'body' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_textarea_field'],			
					'read' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],		
					'mailbox_id' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],		
					'user_id' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],		
					'importance' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],			
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_email');
				}
			],	
			[				
				'methods'  => 'DELETE',
				'callback' => ['USAM_CRM_API', 'delete_email'],					
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('view_email');
				}
			]			
		]);		
		register_rest_route( $this->namespace, '/email/(?P<id>\d+)/files/download', [		
			[
				'methods'  => 'GET',
				'callback' => ['USAM_CRM_API', 'email_download_files'],					
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('add_order');
				}
			]						
		]);
		register_rest_route( $this->namespace, '/mailboxes', array(		
			[ 
				'methods'  => 'GET,POST',
				'callback' => ['USAM_CRM_API', 'get_mailboxes'],	
				'args' => [
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],	
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
		register_rest_route( $this->namespace, '/signatures', array(		
			[ 
				'methods'  => 'GET,POST',
				'callback' => ['USAM_CRM_API', 'get_signatures'],	
				'args' => [
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],	
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
		register_rest_route( $this->namespace, '/email/send', array(		
			[
				'methods'  => 'GET,POST',
				'callback' => ['USAM_CRM_API', 'send_email'],	
				'args' => [
					'mailbox_id' => ['type' => 'integer', 'required' => true, 'sanitize_callback' => 'absint'],		
					'email' => ['type' => 'integer,string', 'required' => false],					
					'subject' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],						
					'message' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],		
					'object_id' => ['type' => 'integer', 'required' => false],
					'object_type' => ['type' => 'string', 'required' => false],	
					'files' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
					'to_contacts' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
					'to_companies' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
					'to_orders' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
					
				],
				'permission_callback' => function( $request ){	
					return current_user_can('universam_api') || current_user_can('send_email');
				}
			]
		));	
		register_rest_route( $this->namespace, '/sms/send', array(		
			[
				'methods'  => 'GET,POST',
				'callback' => ['USAM_CRM_API', 'send_sms'],	
				'args' => [
					'message' => ['type' => 'string', 'required' => true, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],
					'phone' => ['type' => 'integer', 'required' => true, 'sanitize_callback' => 'absint'],
					'object_id' => ['type' => 'integer', 'required' => false],
					'object_type' => ['type' => 'string', 'required' => false],		
				],
				'permission_callback' => function( $request ){	
					return current_user_can('universam_api') || current_user_can('send_sms');
				}
			]
		));		
		register_rest_route( $this->namespace, '/phone/call', array(		
			[
				'methods'  => 'GET,POST',
				'callback' => ['USAM_CRM_API', 'phone_call'],	
				'args' => [
					'phone' => ['type' => 'integer', 'required' => true, 'sanitize_callback' => 'absint'],
					'object_id' => ['type' => 'integer', 'required' => false],
					'object_type' => ['type' => 'string', 'required' => false],		
				],
				'permission_callback' => function( $request ){	
					return current_user_can('universam_api') || current_user_can('send_sms');
				}
			]
		));	
		register_rest_route( $this->namespace, '/phone/cancel', array(		
			[
				'methods'  => 'GET,POST',
				'callback' => ['USAM_CRM_API', 'phone_cancel'],	
				'args' => [
					'id' => ['type' => 'integer', 'required' => true, 'sanitize_callback' => 'absint'],
					'gateway' => ['type' => 'integer', 'required' => true, 'sanitize_callback' => 'sanitize_textarea_field'],
				],
				'permission_callback' => function( $request ){	
					return current_user_can('universam_api') || current_user_can('send_sms');
				}
			]
		));			
			
	}
}
?>