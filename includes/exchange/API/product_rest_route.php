<?php
new USAM_Product_Rest_API();
class USAM_Product_Rest_API
{	
	protected $namespace = 'usam/v1';
	
	function __construct( )
	{	
		add_action('rest_api_init', [$this,'register_routes'] );	
	}
			
	public function register_routes()
    {				
		require_once( USAM_FILE_PATH . '/includes/exchange/API/rest_route/products_API.class.php' );
		register_rest_route( $this->namespace, '/products', [	
			[
				'methods'  => 'GET,POST',
				'callback' => ['USAM_Products_API', 'get_products'],	
				'args' => [					
					'seller' => ['type' => 'integer,array', 'required' => false],				
					'user_list' => ['type' => 'string', 'required' => false],		
					'type_price' => ['type' => 'string', 'required' => false],						
					'category' => ['type' => 'integer,array', 'required' => false],
					'brands' => ['type' => 'integer,array', 'required' => false],
					'category_sale' => ['type' => 'integer,array', 'required' => false],
					'tax_query' => ['type' => 'array', 'required' => false],					
					'from_price' => array('type' => 'string', 'required' => false ),
					'to_price' => array('type' => 'string', 'required' => false ),
					'from_stock' => array('type' => 'integer', 'required' => false ),
					'to_stock' => array('type' => 'integer', 'required' => false ),				
					'from_views' => ['type' => 'integer', 'required' => false],
					'to_views' => array('type' => 'integer', 'required' => false ),
					'status' => ['type' => ['string','array'], 'required' => false],					
					'product_type' => array('type' => 'string', 'required' => false ),
					'fields' => ['type' => ['string','array'], 'required' => false],
					'add_fields' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'post__in' => ['type' => 'array', 'required' => false],					
					'productmeta' => ['type' => 'array', 'required' => false],
					'pricemeta' => ['type' => 'array', 'required' => false],	
					'associated_product' => ['type' => 'array', 'required' => false],					
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'orderby' => ['type' => 'string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc', 'default'], 'required' => false],
					'count' => ['type' => 'integer', 'required' => false],
					'paged' => ['type' => 'integer', 'required' => false],
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
			],
			[
				'methods'  => 'PUT',
				'callback' => array('USAM_Products_API', 'insert_products'),	
				'args' => [								
					'items' => ['type' => 'array', 'required' => true],
				],
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('edit_product');
				}
			],
			[ // удалить
				'methods'  => 'DELETE',
				'callback' => ['USAM_Products_API', 'delete_products'],
				'args' => [					
					'external_code' => ['type' => 'integer,string,array', 'required' => false],
					'post__in' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('delete_product');
				}
			],				
		]);	
		$product_args = [								
			'post_title' => ['type' => 'string', 'required' => true],
			'post_content' => ['type' => 'string', 'required' => false],	
			'thumbnail_id' => ['type' => 'integer', 'required' => false],
			'image_gallery' => ['type' => 'array', 'required' => false],		
			'attributes' => ['type' => 'object', 'required' => false],				
			'price' => ['type' => 'integer', 'required' => false],	
			'category' => ['type' => 'integer', 'required' => false],	
			'not_limited' => ['type' => 'integer,boolean', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_bollean']],				
			'productmeta' => ['type' => 'object', 'required' => false],					
		];
		register_rest_route( $this->namespace, '/product', [
			[
				'methods'  => 'POST',
				'callback' => ['USAM_Products_API', 'insert_product'],	
				'args' => $product_args,
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('edit_product');
				}
			]		
		]);
		$product_args['post_title']['required'] = false;
		register_rest_route( $this->namespace, '/product/(?P<product_id>\d+)', [	
			[
				'methods'  => 'GET',
				'callback' => ['USAM_Products_API', 'get_product'],				
				'args' => [
					'add_fields' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
			],
			[
				'methods'  => 'POST',
				'callback' => ['USAM_Products_API', 'update_product'],				
				'args' => $product_args,
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('edit_product');
				}
			],
			[
				'methods'  => 'DELETE',
				'callback' => ['USAM_Products_API', 'delete_product'],				
				'args' => $product_args,
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('delete_product');
				}
			],
		]);	
		register_rest_route( $this->namespace, '/product/(?P<product_id>\d+)/rating', [
			[
				'methods'  => 'POST',
				'callback' => ['USAM_Products_API', 'update_product_rating'],				
				'args' => [
					'rating' => ['type' => 'string,integer', 'required' => true, 'sanitize_callback' => 'absint'],
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
			]		
		]);		
		register_rest_route( $this->namespace, '/product/(?P<product_id>\d+)/images', [
			[
				'methods'  => 'POST',
				'callback' => ['USAM_Products_API', 'upload_product_images'],				
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('upload_files');
				}
			]		
		]);		
		register_rest_route( $this->namespace, '/image/(?P<id>\d+)', [
			[
				'methods'  => 'POST',
				'callback' => ['USAM_Products_API', 'image_editor'],				
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('upload_files');
				}
			]		
		]);
		register_rest_route( $this->namespace, '/images', [
			[
				'methods'  => 'POST',
				'callback' => ['USAM_Products_API', 'upload_product_images'],				
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('upload_files');
				}
			]		
		]);	
		register_rest_route( $this->namespace, '/products/price', [			
			[ // Обновить цены
				'methods'  => 'PUT',
				'callback' => ['USAM_Products_API', 'save_products_prices'],
				'args' => [					
					'items' => ['type' => 'array', 'required' => true, 
						'validate_callback' => function( $param, $request, $key ) 
						{							
							$result = true;
							foreach( $param as $data )
							{
								if ( !isset($data['code']) )
								{
									$result = false;
									break;
								}
								if ( !isset($data['price']) )
								{
									$result = false;
									break;
								}
								if ( !isset($data['code_price']) && !isset($data['external_code_price']) )
								{
									$result = false;
									break;
								}
							}
							return $result;
						}],				
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api');
				},		
			],				
		]);
		register_rest_route( $this->namespace, '/product/price/(?P<product_id>\d+)', [	
			[
				'methods'  => 'POST',
				'callback' => ['USAM_Products_API', 'save_prices'],				
				'args' => [
					'prices' => ['type' => 'object', 'required' => true],
				],
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('edit_product');
				}
			]
		]);	
		register_rest_route( $this->namespace, '/products/filters', array(		
			[
				'methods'  => 'POST',
				'callback' => ['USAM_Products_API', 'get_filters'],	
				'args' => [
					'query' => ['required' => false],
					'returned' => ['required' => true],
					'type_price' => ['type' => 'string', 'required' => false]
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission'],				
			],			
		));
		register_rest_route( $this->namespace, '/products/components', [	
			array(
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
				'methods'  => 'GET,POST',
				'callback' => ['USAM_Products_API', 'get_product_components'],	
				'args' => [
					'search' => ['type' => 'integer,string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']]
				]
			),	
		]);			
		register_rest_route( $this->namespace, '/products/attributes', [	
			array(
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
				'methods'  => 'GET,POST',
				'callback' => ['USAM_Products_API', 'get_product_attributes'],	
				'args' => [
					'slug' => ['type' => 'string', 'required' => false],
					'search' => ['type' => 'integer,string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']]
				]
			),	
		]);	
		register_rest_route( $this->namespace, '/attribute_values', [		
			[
				'methods'  => 'GET,POST', 
				'args' => [
					'attribute_id' => ['type' => 'integer,array', 'required' => false],	
					'code' => ['type' => 'string,array', 'required' => false],	
					'attribute_external_code' => ['type' => 'integer,string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'include' => ['type' => 'array', 'required' => false],		
					'search' => ['type' => 'integer,string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'paged' => ['type' => 'integer', 'required' => false],
					'orderby' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_title'],	
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],	
					'count' => ['type' => 'integer', 'required' => false],						
				],	
				'callback' => ['USAM_Products_API', 'get_attribute_values'],		
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
			],			
		]);	
		register_rest_route( $this->namespace, '/attribute_values/combine', [		
			[
				'methods'  => 'POST', 
				'args' => [
					'attribute_id' => ['type' => 'integer,array', 'required' => true],	
					'ids' => ['type' => 'array', 'required' => true],					
					'main' => ['type' => 'integer', 'required' => true],
				],	
				'callback' => ['USAM_Products_API', 'combine_attribute_values'],		
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('delete_product_attribute');
				}
			],			
		]);		
		register_rest_route( $this->namespace, '/products/filter_categories', [		
			[
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
				'methods'  => 'POST',
				'callback' => ['USAM_Products_API', 'get_filter_categories'],	
				'args' => [
					'show_active' => ['type' => 'integer', 'required' => true],
					'take_menu' => ['type' => 'integer', 'required' => true],
				]		
			],			
		]);			
		register_rest_route( $this->namespace, '/product/(?P<product_id>\d+)/variation', [	
			[
				'methods'  => 'GET,POST',
				'callback' => ['USAM_Products_API', 'get_product_variation'],				
				'args' => [
					'variations' => ['type' => 'object', 'required' => true],
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
			],
		]);	
		register_rest_route( $this->namespace, '/product/tabs', [	
			[
				'methods'  => 'GET,POST',
				'callback' => ['USAM_Products_API', 'get_product_tabs'],				
				'args' => [
					'global' => ['type' => 'integer', 'required' => false],
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
			],
		]);
		register_rest_route( $this->namespace, '/product/tab', [	
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_Products_API', 'insert_product_tab'],	
				'args' => [					
					'title' => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],
					'name' => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],
					'code' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'description' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'active' => ['type' => 'integer', 'required' => false],
					'global' => ['type' => 'integer', 'required' => false],
				],
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('edit_product');
				}
			]			
		]);		
		register_rest_route( $this->namespace, '/product/tab/(?P<id>\d+)', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_Products_API', 'get_product_tab'],				
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
			],
			[ // Сохранить
				'methods'  => 'POST',
				'callback' => ['USAM_Products_API', 'update_product_tab'],	
				'args' => [					
					'title' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'name' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'code' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'description' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'active' => ['type' => 'integer', 'required' => false],
					'global' => ['type' => 'integer', 'required' => false],
				],
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('edit_product');
				}			
			],
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_Products_API', 'delete_product_tab'],	
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('edit_product');
				}		
			]				
		]);			
		register_rest_route( $this->namespace, '/product/(?P<product_id>\d+)/balances', [	
			[
				'methods'  => 'GET,POST',
				'callback' => ['USAM_Products_API', 'get_balances_stock'],				
				'args' => [
					'location' => ['type' => 'integer,boolean', 'required' => false],
					'issuing' => ['type' => 'integer,boolean', 'required' => false],
					'in_stock' => ['type' => 'integer,boolean', 'required' => false],					
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
			],
		]);			
		register_rest_route( $this->namespace, '/product/(?P<product_id>\d+)/generated/sku', [	
			[
				'methods'  => 'GET',
				'callback' => ['USAM_Products_API', 'get_generated_sku'],	
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
			],
		]);		
		register_rest_route( $this->namespace, '/product/(?P<product_id>\d+)/generated/barcode', [	
			[
				'methods'  => 'GET',
				'callback' => ['USAM_Products_API', 'get_generated_sku'],	
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
			],
		]);	
		register_rest_route( $this->namespace, '/searching_results', [		
			[
				'methods'  => 'GET,POST',
				'args' => [				
					'conditions' => ['type' => 'array,object,string', 'required' => false],
					'search' => ['type' => 'integer,string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'paged' => ['type' => 'integer', 'required' => false],
					'orderby' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],	
					'order' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_title'],	
					'count' => ['type' => 'integer', 'required' => false],							
					'groupby' => ['type' => 'string,array', 'required' => false],
					'fields' => ['type' => 'string,array', 'required' => false],
					'date_query' => ['type' => 'object', 'required' => false],			
					'second' => ['type' => 'integer', 'required' => false],					
					'minute' => ['type' => 'integer', 'required' => false],					
					'hour' => ['type' => 'integer', 'required' => false],					
					'day' => ['type' => 'integer', 'required' => false],	
					'monthnum' => ['type' => 'integer', 'required' => false],	
					'year' => ['type' => 'integer', 'required' => false],	
					'w' => ['type' => 'integer', 'required' => false],			
				],	
				'callback' => ['USAM_Products_API', 'get_popular_search_terms'],		
				'permission_callback' => ['USAM_Request_Processing', 'permission']			
			],			
		]);
		register_rest_route( $this->namespace, '/product/reputation/items', [		
			[
				'methods'  => 'GET',
				'args' => [
					'status' => ['type' => 'integer,array', 'required' => false],
					'product_id' => ['type' => 'integer,array', 'required' => false],
				],	
				'callback' => ['USAM_Products_API', 'get_reputation_items'],		
				'permission_callback' => ['USAM_Request_Processing', 'permission']			
			],			
		]);
		register_rest_route( $this->namespace, '/product/reputation/item/(?P<id>\d+)', [	
			[
				'methods'  => 'GET',
				'callback' => ['USAM_Products_API', 'get_product'],				
				'args' => [
					'fields' => ['type' => ['string','array'], 'required' => false],
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
			],
			[
				'methods'  => 'POST',
				'callback' => ['USAM_Products_API', 'update_product_reputation_item'],				
				'args' => $product_args,
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('edit_product');
				}
			],
			[
				'methods'  => 'DELETE',
				'callback' => ['USAM_Products_API', 'delete_product_reputation_item'],				
				'args' => $product_args,
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('delete_product');
				}
			],
		]);	
		register_rest_route( $this->namespace, '/category_sales', [
			[
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
				'methods'  => 'GET,POST',
				'callback' => ['USAM_Products_API', 'get_term_category_sales'],	
				'args' => [
					'hide_empty' => ['type' => 'integer', 'required' => false],	
					'object_ids' => ['type' => 'array', 'required' => false],					
					'external_code' => ['type' => ['string','array'], 'required' => false],
					'slug' => ['type' => ['string','array'], 'required' => false],
					'include' => ['type' => 'array', 'required' => false],					
					'meta_query' => ['type' => 'array', 'required' => false],	
					'meta_key' => ['type' => 'string', 'required' => false],
					'meta_value' => ['type' => 'string', 'required' => false],				
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'fields' => ['type' => 'string', 'required' => false],
					'add_fields' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'paged' => ['type' => 'integer', 'required' => false],
					'orderby' => ['type' => 'string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],
				],				
			],	
			[ // Обновить
				'methods'  => 'PUT',
				'callback' => ['USAM_Products_API', 'save_category_sales'],
				'args' => [					
					'items' => ['type' => 'array', 'required' => true],					
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api');
				}
			],
			[ // удалить
				'methods'  => 'DELETE',
				'callback' => ['USAM_Products_API', 'delete_category_sales'],
				'args' => [					
					'external_code' => ['type' => 'integer,string,array', 'required' => false],
					'include' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api');
				}
			],			
		]);	
		register_rest_route( $this->namespace, '/brands', [
			[
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
				'methods'  => 'GET,POST',
				'callback' => ['USAM_Products_API', 'get_term_brands'],	
				'args' => [
					'hide_empty' => ['type' => 'integer', 'required' => false],	
					'object_ids' => ['type' => 'array', 'required' => false],					
					'external_code' => ['type' => ['string','array'], 'required' => false],
					'slug' => ['type' => ['string','array'], 'required' => false],
					'include' => ['type' => 'array', 'required' => false],					
					'meta_query' => ['type' => 'array', 'required' => false],	
					'meta_key' => ['type' => 'string', 'required' => false],
					'meta_value' => ['type' => 'string', 'required' => false],				
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'fields' => ['type' => 'string', 'required' => false],
					'add_fields' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'paged' => ['type' => 'integer', 'required' => false],
					'orderby' => ['type' => 'string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],
				],				
			],	
			[ // Обновить
				'methods'  => 'PUT',
				'callback' => ['USAM_Products_API', 'save_brands'],
				'args' => [					
					'items' => ['type' => 'array', 'required' => true],					
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api');
				}
			],
			[ // удалить
				'methods'  => 'DELETE',
				'callback' => ['USAM_Products_API', 'delete_brands'],
				'args' => [					
					'external_code' => ['type' => 'integer,string,array', 'required' => false],
					'include' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api');
				}
			],			
		]);	
		register_rest_route( $this->namespace, '/brand/(?P<id>\d+)', [					
			[ // Обновить
				'methods'  => 'POST',
				'callback' => ['USAM_Products_API', 'update_brand'],
				'args' => [					
					'name' => ['type' => 'integer,string', 'required' => false],	
					'status' => ['type' => 'string', 'required' => false],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('edit_product_attribute');
				}
			],						
		]);
		register_rest_route( $this->namespace, '/variations', [	
			[
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
				'methods'  => 'GET',
				'callback' => ['USAM_Products_API', 'get_term_variations'],	
				'args' => [
					'hide_empty' => ['type' => 'integer', 'required' => false],	
					'object_ids' => ['type' => 'array', 'required' => false],					
					'external_code' => ['type' => ['string','array'], 'required' => false],
					'slug' => ['type' => ['string','array'], 'required' => false],
					'include' => ['type' => 'array', 'required' => false],					
					'meta_query' => ['type' => 'array', 'required' => false],	
					'meta_key' => ['type' => 'string', 'required' => false],
					'meta_value' => ['type' => 'string', 'required' => false],				
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'fields' => ['type' => 'string', 'required' => false],
					'add_fields' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'paged' => ['type' => 'integer', 'required' => false],
					'orderby' => ['type' => 'string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],
				],				
			],	
			[ // Обновить
				'methods'  => 'PUT',
				'callback' => ['USAM_Products_API', 'save_variations'],
				'args' => [					
					'items' => ['type' => 'array', 'required' => true],					
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api');
				}
			],
			[ // удалить
				'methods'  => 'DELETE',
				'callback' => ['USAM_Products_API', 'delete_variations'],
				'args' => [					
					'external_code' => ['type' => 'integer,string,array', 'required' => false],
					'include' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api');
				}
			],			
		]);	
		register_rest_route( $this->namespace, '/catalogs', array(		
			[
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
				'methods'  => 'GET,POST',
				'callback' => array('USAM_Products_API', 'get_term_catalogs'),	
				'args' => [
					'hide_empty' => ['type' => 'integer', 'required' => false],	
					'object_ids' => ['type' => 'array', 'required' => false],					
					'include' => ['type' => 'array', 'required' => false],
					'external_code' => ['type' => ['string','array'], 'required' => false],
					'exclude_tree' => ['type' => 'array', 'required' => false],
					'exclude' => ['type' => 'array', 'required' => false],
					'parent' => ['type' => 'integer', 'required' => false],
					'meta_query' => ['type' => 'array', 'required' => false],	
					'meta_key' => ['type' => 'string', 'required' => false],
					'meta_value' => ['type' => 'string', 'required' => false],	
					'childless' => ['type' => 'integer', 'required' => false],
					'hierarchical' => ['type' => 'integer', 'required' => false],	
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'fields' => ['type' => 'string', 'required' => false],
					'add_fields' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'paged' => ['type' => 'integer', 'required' => false],
					'orderby' => ['type' => 'string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],
				],							
			],	
			[ // Обновить
				'methods'  => 'PUT',
				'callback' => ['USAM_Products_API', 'save_catalogs'],
				'args' => [					
					'items' => ['type' => 'array', 'required' => true],					
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api');
				}
			],
			[ // удалить
				'methods'  => 'DELETE',
				'callback' => ['USAM_Products_API', 'delete_catalogs'],
				'args' => [					
					'external_code' => ['type' => 'integer,string,array', 'required' => false],
					'include' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('delete_product_catalog');
				}
			],				
		));	
		register_rest_route( $this->namespace, '/selections', array(		
			[
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
				'methods'  => 'GET,POST',
				'callback' => array('USAM_Products_API', 'get_term_selections'),	
				'args' => [
					'hide_empty' => ['type' => 'integer', 'required' => false],	
					'object_ids' => ['type' => 'array', 'required' => false],					
					'include' => ['type' => 'array', 'required' => false],
					'external_code' => ['type' => ['string','array'], 'required' => false],
					'exclude_tree' => ['type' => 'array', 'required' => false],
					'exclude' => ['type' => 'array', 'required' => false],
					'parent' => ['type' => 'integer', 'required' => false],
					'meta_query' => ['type' => 'array', 'required' => false],	
					'meta_key' => ['type' => 'string', 'required' => false],
					'meta_value' => ['type' => 'string', 'required' => false],	
					'childless' => ['type' => 'integer', 'required' => false],
					'hierarchical' => ['type' => 'integer', 'required' => false],	
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'fields' => ['type' => 'string', 'required' => false],
					'add_fields' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'paged' => ['type' => 'integer', 'required' => false],
					'orderby' => ['type' => 'string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],
				]								
			],
			[ // Обновить
				'methods'  => 'PUT',
				'callback' => ['USAM_Products_API', 'save_selections'],
				'args' => [					
					'items' => ['type' => 'array', 'required' => true],					
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api');
				}
			],
			[ // удалить
				'methods'  => 'DELETE',
				'callback' => ['USAM_Products_API', 'delete_selections'],
				'args' => [					
					'external_code' => ['type' => 'integer,string,array', 'required' => false],
					'include' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('delete_product_selection');
				}
			],			
		));		
		register_rest_route( $this->namespace, '/categories', array(		
			[
			//	'permission_callback' => ['USAM_Request_Processing', 'permission'],
				'permission_callback' => function( $request ){
					return true;
				},
				'methods'  => 'GET,POST',
				'callback' => ['USAM_Products_API', 'get_term_categories'],
				'args' => [							
					'hide_empty' => ['type' => 'integer', 'required' => false],
					'object_ids' => ['type' => 'array', 'required' => false],					
					'include' => ['type' => 'array', 'required' => false],
					'external_code' => ['type' => ['string','array'], 'required' => false],
					'exclude_tree' => ['type' => 'array', 'required' => false],
					'exclude' => ['type' => 'array', 'required' => false],
					'parent' => ['type' => 'integer', 'required' => false],
					'meta_query' => ['type' => 'array', 'required' => false],	
					'meta_key' => ['type' => 'string', 'required' => false],					
					'meta_value' => ['type' => 'string', 'required' => false],	
					'status' => ['type' => 'string', 'required' => false],
					'childless' => ['type' => 'integer', 'required' => false],
					'hierarchical' => ['type' => 'integer', 'required' => false],					
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'fields' => ['type' => ['string','array'], 'required' => false],
					'add_fields' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'paged' => ['type' => 'integer', 'required' => false],
					'count' => ['type' => 'integer', 'required' => false],
					'orderby' => ['type' => 'string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],
				],				
			],	
			[ // Обновить
				'methods'  => 'PUT',
				'callback' => ['USAM_Products_API', 'save_categories'],
				'args' => [					
					'items' => ['type' => 'array', 'required' => true],	
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('edit_product_category');
				}
			],
			[ // удалить
				'methods'  => 'DELETE',
				'callback' => ['USAM_Products_API', 'delete_categories'],
				'args' => [					
					'external_code' => ['type' => 'integer,string,array', 'required' => false],
					'include' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('delete_product_category');
				}
			]		
		));
		register_rest_route( $this->namespace, '/category/(?P<id>\d+)', [					
			[ // Обновить
				'methods'  => 'POST',
				'callback' => ['USAM_Products_API', 'update_product_category'],
				'args' => [					
					'name' => ['type' => 'integer,string', 'required' => false],	
					'status' => ['type' => 'string', 'required' => false],
					'category' => ['type' => 'integer,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('edit_product_attribute');
				}
			],						
		]);
		register_rest_route( $this->namespace, '/product_tags', array(		
			[
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
				'methods'  => 'GET,POST',
				'callback' => array('USAM_Products_API', 'get_term_product_tags'),	
				'args' => [
					'hide_empty' => ['type' => 'integer', 'required' => false],	
					'object_ids' => ['type' => 'array', 'required' => false],					
					'include' => ['type' => 'array', 'required' => false],
					'external_code' => ['type' => ['string','array'], 'required' => false],
					'exclude_tree' => ['type' => 'array', 'required' => false],
					'exclude' => ['type' => 'array', 'required' => false],
					'parent' => ['type' => 'integer', 'required' => false],
					'meta_query' => ['type' => 'array', 'required' => false],	
					'meta_key' => ['type' => 'string', 'required' => false],
					'meta_value' => ['type' => 'string', 'required' => false],	
					'childless' => ['type' => 'integer', 'required' => false],
					'hierarchical' => ['type' => 'integer', 'required' => false],	
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'add_fields' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'fields' => ['type' => 'string', 'required' => false],
					'paged' => ['type' => 'integer', 'required' => false],
					'orderby' => ['type' => 'string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],
				],				
			],	
			[ // Обновить
				'methods'  => 'PUT',
				'callback' => ['USAM_Products_API', 'save_product_tags'],
				'args' => [					
					'items' => ['type' => 'array', 'required' => true],					
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api');
				}
			],
			[ // удалить
				'methods'  => 'DELETE',
				'callback' => ['USAM_Products_API', 'delete_product_tags'],							
				'args' => [					
					'external_code' => ['type' => 'integer,string,array', 'required' => false],
					'include' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api');
				}
			],			
		));
		register_rest_route( $this->namespace, '/product_attributes', [		
			[
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
				'methods'  => 'GET,POST',
				'callback' => ['USAM_Products_API', 'get_term_product_attributes'],	
				'args' => [
					'hide_empty' => ['type' => 'integer', 'required' => false],										 
					'status' => ['type' => 'string', 'required' => false],
					'object_ids' => ['type' => 'array', 'required' => false],					
					'include' => ['type' => 'array', 'required' => false],
					'external_code' => ['type' => ['string','array'], 'required' => false],
					'exclude_tree' => ['type' => 'array', 'required' => false],
					'exclude' => ['type' => 'array', 'required' => false],
					'parent' => ['type' => 'integer', 'required' => false],
					'meta_query' => ['type' => 'array', 'required' => false],	
					'meta_key' => ['type' => 'string', 'required' => false],
					'meta_value' => ['type' => 'string', 'required' => false],	
					'childless' => ['type' => 'integer', 'required' => false],
					'hierarchical' => ['type' => 'integer', 'required' => false],	
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'fields' => ['type' => 'string', 'required' => false],
					'field_type' => ['type' => 'string,array', 'required' => false],
					'paged' => ['type' => 'integer', 'required' => false],
					'orderby' => ['type' => 'string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],
				],				
			],				
			[ // Обновить
				'methods'  => 'PUT',
				'callback' => ['USAM_Products_API', 'save_product_attributes'],
				'args' => [					
					'items' => ['type' => 'array', 'required' => true],					
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('edit_product_attribute') && current_user_can('add_product_attribute');
				}
			],	
			[ // удалить
				'methods'  => 'DELETE',
				'callback' => ['USAM_Products_API', 'delete_product_attributes'],
				'args' => [					
					'external_code' => ['type' => 'integer,string,array', 'required' => false],
					'include' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('delete_product_attribute');
				}
			],			
		]);
		register_rest_route( $this->namespace, '/product_attribute', [					
			[ // Обновить
				'methods'  => 'POST',
				'callback' => ['USAM_Products_API', 'insert_product_attribute'],
				'args' => [					
					'name' => ['type' => 'integer,string', 'required' => true],	
					'status' => ['type' => 'string', 'required' => false],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('add_product_attribute');
				}
			],					
		]);
		register_rest_route( $this->namespace, '/product_attribute/(?P<id>\d+)', [					
			[ // Обновить
				'methods'  => 'POST',
				'callback' => ['USAM_Products_API', 'update_product_attribute'],
				'args' => [					
					'name' => ['type' => 'integer,string', 'required' => false],	
					'status' => ['type' => 'string', 'required' => false],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('edit_product_attribute');
				}
			],						
		]);
		register_rest_route( $this->namespace, '/taxonomies', [
			[
				'methods'  => 'GET,POST',
				'callback' => ['USAM_Products_API', 'get_taxonomies'],
				'args' => [
					'output' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'object_type' => ['type' => 'array,string', 'required' => false],		
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			]			
		]);			
		register_rest_route( $this->namespace, '/terms', [
			[
				'methods'  => 'GET,POST',
				'callback' => ['USAM_Products_API', 'get_list_terms'],
				'args' => [
					'taxonomy' => ['type' => 'array,string', 'required' => false],		
					'taxonomy_object' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'hide_empty' => ['type' => 'integer', 'required' => false],		
									],
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			]			
		]);	
		register_rest_route( $this->namespace, '/term/(?P<taxonomy>\S+)/(?P<id>\d+)', array(		
			[
				'methods'  => 'GET',
				'callback' => ['USAM_Products_API', 'get_term'],	
				'args' => [				
					'add_fields' => ['type' => 'string,array', 'required' => false],
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
			],
			[
				'methods'  => 'POST',
				'callback' => ['USAM_Products_API', 'save_term'],	
				'args' => [				
					'status' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
				],
				'permission_callback' => ['USAM_Request_Processing', 'permission'],
			],
			[ // удалить
				'methods'  => 'DELETE', 
				'callback' => ['USAM_Products_API', 'delete_term'],				
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('delete_term');
				}
			],			
		));			
		register_rest_route( $this->namespace, '/showcases', [		
			[ 
				'methods'  => 'GET,POST',
				'callback' => ['USAM_Products_API', 'get_showcases'],				
				'args' => [
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'paged' => ['type' => 'integer', 'required' => false],
					'orderby' => ['type' => 'integer,string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],	
					'include' => ['type' => 'array', 'required' => false],
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_showcases');
				}
			]
		]);
		register_rest_route( $this->namespace, '/showcase', [	
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_Products_API', 'insert_showcase'],	
				'args' => [					
					'domain' => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],
					'access_token' => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],
					'status' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('view_showcases');
				}
			]			
		]);		
		register_rest_route( $this->namespace, '/showcase/(?P<id>\d+)', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_Products_API', 'get_showcase'],				
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_showcases');
				}
			],
			[ // Сохранить
				'methods'  => 'POST',
				'callback' => ['USAM_Products_API', 'update_showcase'],	
				'args' => [					
					'domain' => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],
					'access_token' => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],
					'status' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_showcases');
				}				
			],
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_Products_API', 'delete_showcase'],	
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_showcases');
				}			
			]				
		]);	
		register_rest_route( $this->namespace, '/showcases/check/available/products', [		
			[ 
				'methods'  => 'GET',
				'callback' => ['USAM_Products_API', 'showcases_check_available_products'],				
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_showcases');
				}
			]
		]);		
		register_rest_route( $this->namespace, '/showcases/update/prices/products', [		
			[ 
				'methods'  => 'GET',
				'callback' => ['USAM_Products_API', 'showcases_update_prices_products'],				
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_showcases');
				}
			]
		]);	
		register_rest_route( $this->namespace, '/showcases/synchronization/products', [		
			[ 
				'methods'  => 'GET',
				'callback' => ['USAM_Products_API', 'showcases_synchronization_products'],				
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_showcases');
				}
			]
		]);
		register_rest_route( $this->namespace, '/showcase/(?P<id>\d+)/synchronization/products', [		
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_Products_API', 'showcase_delete_not_synchronization_products'],	
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_showcases');
				}			
			]	
		]);		
		register_rest_route( $this->namespace, '/showcase/(?P<id>\d+)/remove/products/link', [		
			[ 
				'methods'  => 'GET',
				'callback' => ['USAM_Products_API', 'showcase_remove_products_link'],	
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_showcases');
				}			
			]	
		]);	

		register_rest_route( $this->namespace, '/product/day', [	
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_Products_API', 'insert_product_day'],	
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
					return current_user_can('universam_api') || current_user_can('view_product_day');
				}
			]			
		]);		
		register_rest_route( $this->namespace, '/product/day/(?P<id>\d+)', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_Products_API', 'get_product_day'],				
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_product_day');
				}
			],
			[ // Сохранить
				'methods'  => 'POST',
				'callback' => ['USAM_Products_API', 'update_product_day'],	
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
					return current_user_can('universam_api') || current_user_can('view_product_day');
				}				
			],
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_Products_API', 'delete_product_day'],	
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_product_day');
				}			
			]				
		]);		
	}
}
?>