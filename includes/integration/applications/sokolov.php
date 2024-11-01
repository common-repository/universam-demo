<?php 
/*
	Name: API сайта Соколов
	Description: Загружает товары с сайта Соколов на ваш сайт.
	Price: paid
	Group: import_products
*/
require_once( USAM_FILE_PATH . '/includes/application.class.php' );	
class USAM_Application_Sokolov extends USAM_Application
{	
	protected $API_URL = "https://api.b2b.sokolov.net/ru-ru";
	protected $expiration = 7200;
	
	protected function get_token( )
	{ 					
		$access_token = get_transient( 'sokolov_access_token' );			
		if ( !empty($access_token) )
			return $access_token;	
		
		$headers["Authorization"] = "Basic ".base64_encode("{$this->login}:{$this->password}"); 
		$args = array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => $headers,
		);					
		$result = $this->send_request( "login", $args );		
		if ( isset($result['access_token']) )
		{ 
			set_transient( 'sokolov_access_token', $result['access_token'], $this->expiration );
			return $result['access_token'];
		}
		return false;
	}	
	
	public function is_token( )
	{	
		return true;
	}

	public function get_products( $args )
	{ 	
		$args['page'] = !empty($args['page'])?$args['page']:1;
		$args['size'] = !empty($args['size'])?$args['size']:20;
		$args = $this->get_args( $args );
		$results = $this->send_request( "catalog/products", $args );	
		return ['items' => $products['data'], 'total_items' => $products['meta']['total-count']];
	}		
	
	public function insert_product( $args )
	{
		$sokolov_products = $this->get_products( $args );	
		$type_price = usam_get_application_metadata( $this->id, 'type_price' );							
		$i = 0;
		if ( !empty($sokolov_products['items']) )
		{							
			$attributes = usam_get_product_attributes();											
			$children = get_option( 'usam-product_attributes_children', array());
			$parent = key($children); 
			foreach ( $sokolov_products['items'] as $sokolov_product )
			{							
				$attribute_values = array();
				foreach ($sokolov_product['attributes']['props']['proportions'] as $proportion )
				{
					foreach ($attributes as $attribute )
					{
						if ( $proportion['name'] == $attribute->name )
						{
							$attribute_values[$attribute->slug] = $proportion['value'];	
							break;
						}
					}		
					if ( empty($attribute_values[$attribute->slug]) )
					{
						$slug = sanitize_title( $proportion['name'] );
						$term = wp_insert_term( $proportion['name'], 'usam-product_attributes', ['parent' => $parent, 'slug' => $slug]);		
						if ( !is_wp_error($term) ) 
							$attribute_values[$slug] = $proportion['value'];	
					}
				}
				foreach ($attributes as $attribute )
				{
					if ( in_array($attribute->slug, array('total-weight', 'metal-weight', 'material', 'material-color', 'material-plating', 'probe') ) )
						$attribute_values[$attribute->slug] = $sokolov_product['attributes'][$attribute->slug];	
				}								
				$post_id = usam_get_product_id_by_code( $sokolov_product['attributes']['article'] );
				$product = ['post_title' => $sokolov_product['attributes']['title'], 'post_excerpt' => $sokolov_product['attributes']['description'], 'productmeta' => ['sku' => $sokolov_product['attributes']['article'], 'weight' => $sokolov_product['attributes']['total-weight'], 'code' => $sokolov_product['attributes']['article']], 'prices' => ['price_'.$type_price => $sokolov_product['attributes']['recommended-retail-price']]];					
				if ( !$post_id )
				{					
					$product['thumbnail'] = $sokolov_product['attributes']['photo'];
					$terms = get_terms( array( 'hide_empty' => 0, 'taxonomy' => 'usam-category', 'name__like' => $sokolov_product['attributes']['category'] ) );	
					if ( !empty($terms[0]) )
					{
						$product['tax_input']['usam-category'] = array( $terms[0]->term_id );
					}
					else
					{
						$term = wp_insert_term( $sokolov_product['attributes']['category'], 'usam-category', array( 'parent' => 0 ) );		
						if ( !is_wp_error($term) ) 
							$product['tax_input']['usam-category'] = $term['term_id'];
					}						
					$_product = new USAM_Product( $product );	
					$product_id = $_product->insert_product( $attribute_values );
					$_product->insert_media();
					$i++;
				}
				else
				{
					$_product = new USAM_Product( $post_id );
					$_product->set( $product ); 
					$_product->update_product( );					
					$_product->calculate_product_attributes( $attribute_values, true );	
					$i++;
				}
			}
		}
		return $i;
	}
		
	protected function get_args( $params, $method = 'POST' )
	{ 
		$token = $this->get_token();		
		$headers["Authorization"] = "Bearer $token";
		$headers["Content-type"] = 'application/json; charset=UTF-8';
		$args = array(
			'method' => $method,
			'timeout' => 45,
			'redirection' => 5,
			'headers' => $headers,
			'data_format' => 'body',
		);	
		if ( !empty($params) )
		{						
			$args['body'] = json_encode($params);			
		} 
		return $args;
	}
		
	function display_form( ) 
	{				
		$type_price = usam_get_application_metadata( $this->id, 'type_price' );	
		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_login'><?php esc_html_e( 'Логин', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id="option_login" name="login" value="<?php echo $this->option['login']; ?>">
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='messenger_password'><?php esc_html_e( 'Пароль', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option"><?php usam_get_password_input( $this->option['password'], ['name' => 'password', 'id' => 'messenger_password']); ?></div>
			</div>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='type_price'><?php esc_html_e( 'Тип цены', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php echo usam_get_select_prices( $type_price, ['name' => 'type_price'] ); ?>
				</div>
			</div>
		</div>
		<?php
	}
	
	public function save_form( ) 
	{
		$metas['type_price'] = isset($_POST['type_price'])?sanitize_text_field($_POST['type_price']):'';
		foreach( $metas as $meta_key => $meta_value)
		{			
			usam_update_application_metadata($this->id, $meta_key, $meta_value);
		}
	}
	
	public function page_tabs( $tabs )
	{		
		$add = true;
		foreach( $tabs['exchange'] as $tab )
		{
			if ( $tab['id'] == 'external_api' )
			{
				$add = false;
				break;
			}			
		}
		if ( $add )
			$tabs['exchange'][] = ['id' => 'external_api',  'title' => __('Интеграции', 'usam'), 'capability' => 'view_product_importer'];
		return $tabs;
	}
	
	public function filter_tab_sections( $sections, $tab, $page )
	{
		if ( $page == 'exchange' && $tab == 'external_api' )
			$sections['import_products_'.$this->option['service_code']] = ['title' => 'Соколов', 'type' => 'table'];
		return $sections;
	}

	public function filter_actions( $result, $action, $records ) 
	{
		if ( current_user_can('view_product_importer') )
		{	
			switch( $action )
			{			
				case 'download':
					$i = $this->insert_product(['filter' => ['article' => ['in' => $records]] ]);
					return ['add_product' => $i]; 
				break;			
			}	
		}
	}	
	
	public function filter_table_data( $products, $query_vars ) 
	{
		$products = $this->get_products(['page' => $query_vars['paged'], 'size' => $query_vars['number']]);
		foreach ( $products['items'] as &$product )
		{
			$product['category'] = $product['attributes']['category'];
			$product['tradeprice'] = $product['attributes']['trade-price'];
			$product['recommendedretailprice'] = $product['attributes']['recommended-retail-price'];
			$product['probe'] = $product['attributes']['probe'];
			$product['sku'] = $product['attributes']['article'];
			$product['photo'] = $product['attributes']['photo'];
			$product['product_title'] = $product['attributes']['title'];			
			unset($product['attributes']);
		}
		return $products;		
	}	
	
	public function filter_table_columns( ) 
	{
		return [        
			'cb'             => '<input type="checkbox" />',			
			'product_title'  => __('Название товара', 'usam'),					
			'category'       => __('Категория', 'usam'),	
			'tradeprice'     => __('Цена покупки', 'usam'),
			'recommendedretailprice' => __('Рекомендованная цена продажи', 'usam'),				
			'probe'        => __('Проба', 'usam'),				
        ];
	}
	
	public function service_load()
	{ 
		add_filter( 'usam_page_tabs', [&$this, 'page_tabs']);
		add_filter( 'usam_tab_sections', [$this, 'filter_tab_sections'], 10, 3 );	
		add_filter( 'usam_import_products_sokolov_actions', [$this, 'filter_actions'], 10, 3);	
		add_filter( 'usam_application_import_products_data', [$this, 'filter_table_data'], 10, 2);
		add_filter( 'usam_application_import_products_columns', [$this, 'filter_table_columns']);				
	}	
}

require_once( USAM_FILE_PATH .'/admin/includes/list_table/application_import_products_list_table.php' );
class USAM_List_Table_import_products_sokolov extends USAM_Application_Import_Products_Table{ }
?>