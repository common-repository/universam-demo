<?php
new USAM_SEO_Rest_API();
class USAM_SEO_Rest_API
{	
	protected $namespace = 'usam/v1';
	
	function __construct( )
	{	
		add_action('rest_api_init', [$this,'register_routes'] );	
	}
			
	public function register_routes()
    {				
		require_once( USAM_FILE_PATH . '/includes/exchange/API/rest_route/seo_API.class.php' );
		register_rest_route( $this->namespace, '/seo/robots', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_SEO_API', 'get_robots'],				
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_seo');
				}
			],	
			[
				'methods'  => 'POST',
				'callback' => ['USAM_SEO_API', 'save_robots'],				
				'args' => [						
					'robots' => ['type' => 'string', 'required' => true],
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_seo');
				}
			],					
		]);
		register_rest_route( $this->namespace, '/seo/robots/default', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_SEO_API', 'get_default_robots'],				
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_seo');
				}
			]				
		]);		
		register_rest_route( $this->namespace, '/seo/metas', [	
			[
				'methods'  => 'GET',
				'callback' => ['USAM_SEO_API', 'get_metas'],					
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('view_seo');
				}
			],			
		]);
		register_rest_route( $this->namespace, '/update/seo/metas', [	
			[
				'methods'  => 'POST',
				'callback' => ['USAM_SEO_API', 'save_metas'],					
				'args' => [						
					'metas' => ['type' => 'object', 'required' => true],
				],
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('view_seo');
				}
			],			
		]);	
		register_rest_route( $this->namespace, '/post/metatags', [
			[
				'methods'  => 'GET',
				'callback' => ['USAM_SEO_API', 'get_post_metatags'],					
				'args' => [						
					'id' => ['type' => 'integer', 'required' => true],
				],
				'permission_callback' => function( $request ){			
					return current_user_can('universam_api') || current_user_can('view_seo');
				}
			],		
		]);	
		register_rest_route( $this->namespace, '/keyword', [		
			[ // Получить
				'methods'  => 'POST',
				'callback' => ['USAM_SEO_API', 'insert_keyword'],				
				'args' => [					
					'keyword' => ['type' => 'string', 'required' => true],
					'importance' => ['type' => 'integer', 'required' => false],
					'check' => ['type' => 'integer', 'required' => false],
					'parent' => ['type' => 'integer', 'required' => false],
					'yandex_hits' => ['type' => 'integer', 'required' => false],
					'source' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'link' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],					
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_seo');
				}
			],
		]);
		register_rest_route( $this->namespace, '/keyword/(?P<id>\d+)', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_SEO_API', 'get_keyword'],				
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_seo');
				}
			],
			[ // Сохранить
				'methods'  => 'POST',
				'callback' => ['USAM_SEO_API', 'update_keyword'],	
				'args' => [					
					'keyword' => ['type' => 'string', 'required' => true],
					'importance' => ['type' => 'integer', 'required' => false],
					'check' => ['type' => 'integer', 'required' => false],
					'parent' => ['type' => 'integer', 'required' => false],
					'yandex_hits' => ['type' => 'integer', 'required' => false],
					'source' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
					'link' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],	
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_seo');
				}				
			],
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_SEO_API', 'delete_keyword'],	
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('view_seo');
				}			
			]				
		]);		
	}
}
?>