<?php
/**
 	Name: Wildberries
	Description: Выгрузка товаров
	Group: storage
	Icon: wildberries
 */
require_once( USAM_FILE_PATH . '/includes/application.class.php' );		
class USAM_Application_wildberries extends USAM_Application
{	
	protected $API_URL = "https://suppliers-api.wildberries.ru";
		
	public function get_categories( $query_vars = [] )
	{ 
		$args = $this->get_args( 'POST', $query_vars );
		return $this->send_request( "v2/category/tree", $args );			
	}
	
	public function update_products( $query_vars = [] )
	{ 
		$query_vars['posts_per_page'] = 20;
		$query_vars['orderby'] = 'ID';
		$query_vars['order'] = 'ASC';
		if ( !isset($query_vars['post_status']) )
			$query_vars['post_status'] = 'any';
		
		$products = usam_get_products( $query_vars );	
		$items = [];
		foreach( $products as $key => $product )	
			$items[] = $this->get_wildberries_product( $product );
		$result = false;
		if ( $items )
		{			
			$args = $this->get_args( 'POST', [ $items ] );
			$result = $this->send_request( "content/v1/cards/upload", $args );	
			if ( !empty($result['error']) && $result['error'] )
			{
				foreach( $products as $key => $product )
					usam_update_product_meta( $product->ID, 'wildberries_id', 0 );
				$result = true;
			}
		} 	
		return $result;
	}	
	
	private function get_wildberries_product( $product )
	{ 
		if ( is_numeric($product) )
			$product = get_post( $product );	
				
		$sku = usam_get_product_meta( $product->ID, 'sku' );						
		$barcode = usam_get_product_meta($product->ID, 'barcode');
		$price = usam_get_product_price( $product->ID, $this->option['type_price'] );
			
		$insert = ['vendorCode' => (string)$sku, 'characteristics' => []];	
		$insert['sizes'] = ['price' => $price*100, 'skus' => [(string)$barcode]];	
		$urls = usam_get_product_images_urls( $product->ID );		
		return $insert;
	}
	
	public function update_stock( $query_vars = [] )
	{ 
		$query_vars = [] ;
		$query_vars['posts_per_page'] = 20;
		if ( !isset($query_vars['post_status']) )
			$query_vars['post_status'] = 'any';	
		$query_vars['fields'] = 'ids';
		$query_vars['productmeta_query'] = [['key' => 'wildberries_id', 'compare' => 'EXISTS']];	
		$query_vars['stocks_cache'] = true;
		$query_vars['prices_cache'] = false;		
		
		$products = usam_get_products( $query_vars );
		
		$items = [];
		foreach( $products as $key => $product_id )	
			$items[] = ['offer_id' => (string)$product_id, 'stock' => (int)usam_product_remaining_stock( $product_id ) ];
		$result = false;
		if ( $items )
		{
			$args = $this->get_args( 'POST', ['stocks' => $items] );
			$result = $this->send_request( "v1/product/import/stocks", $args );	
		} 	
		return count($items);
	}	
	
	public function update_prices( $query_vars = [] )
	{ 
		$query_vars['posts_per_page'] = 20;
		if ( !isset($query_vars['post_status']) )
			$query_vars['post_status'] = 'any';
		$query_vars['fields'] = 'ids';
		$query_vars['productmeta_query'] = [['key' => 'wildberries_id', 'compare' => 'EXISTS']];
		$query_vars['prices_cache'] = true;		
		$query_vars['stocks_cache'] = false;
		
		$currency = usam_get_currency_price_by_code( $this->option['type_price'] );
		
		$products = usam_get_products( $query_vars );		
		$items = [];
		foreach( $products as $key => $product_id )	
		{
			$price = usam_get_product_price( $product_id, $this->option['type_price'] );
			$items[] = ['nmId' => (string)$product_id, 'price' => $price];			
		}
		$result = false;
		if ( $items )
		{			
			$args = $this->get_args( 'POST', ['items' => $items] );
			$result = $this->send_request( "public/api/v1/prices", $args );	
		} 	
		return count($items);
	}
	
	private function get_category_wildberries( $product_id )
	{
		$category_wildberries_id = (int)usam_get_product_meta( $product_id, 'category_wildberries' ); 
		if ( $category_wildberries_id > 0 )		
			$category_id = $category_wildberries_id;	
		else
		{
			$terms = get_the_terms( $product_id, 'usam-category');				
			$product_term_ids = [];
			foreach( $terms as $term )
			{
				$wildberries_category = (int)usam_get_term_metadata($term->term_id, 'wildberries_category');
				if ( $wildberries_category )
				{
					$category_id = $wildberries_category;
					break;
				}
				else
				{					
					$ancestors = usam_get_ancestors( $term->term_id, 'usam-category' );
					foreach( $ancestors as $term_id ) 
					{
						$wildberries_category = (int)usam_get_term_metadata($term_id, 'wildberries_category');
						if ( !$wildberries_category )
						{
							$category_id = $wildberries_category;
							break;
						}
					}			
				}			
			}			
		}	
		return $category_id;
	}
	
	protected function get_args( $method = 'GET', $params = [] )
	{ 
		$headers["Content-type"] = 'application/json';
		$headers["Authorization"] = $this->token;
		$args = [
			'method' => $method,
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => $headers
		];	
		if ( $params )
		{
			if ( $method == 'GET' )
				$args['body'] = $params;
			else
				$args['body'] = json_encode($params);
		}
		return $args;
	}
	
	protected function get_default_option( ) 
	{
		return ['login' => '', 'token' => '', 'type_price' => ''];
	}
		
	public function display_form() 
	{	
		$prices_schedule = usam_get_application_metadata( $this->id, 'update_prices_schedule' );	
		$prices_time = usam_get_application_metadata( $this->id, 'update_prices_schedule_time' );
		if ( !$prices_time )
			$prices_time = '00:00';
		$stock_schedule = usam_get_application_metadata( $this->id, 'stock_schedule' );	
		$stock_time = usam_get_application_metadata( $this->id, 'stock_schedule_time' );
		if ( !$stock_time )
			$stock_time = '00:00';
		?>			
		<div class="edit_form" >		
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_token'><?php esc_html_e( 'API key', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option"><?php usam_get_password_input( $this->option['access_token'], ['name' => 'access_token', 'id' => 'option_token']); ?></div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Тип цены', 'usam'); ?>:</div>
				<div class ="edit_form__item_option"><?php echo usam_get_select_prices( $this->option['type_price'] ); ?></div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Автоматический обновление цен каждые' , 'usam'); ?>:</div>
				<div class ="edit_form__item_option">					
					<select name='prices_schedule'>						
						<option value=''><?php esc_html_e('Отключено' , 'usam'); ?></option>
						<?php
						foreach ( wp_get_schedules() as $cron => $schedule ) 
						{										
							?><option <?php selected( $prices_schedule, $cron ); ?> value='<?php echo $cron; ?>'><?php echo $schedule['display']; ?></option><?php
						}
						?>
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_prices_time'><?php esc_html_e( 'Начиная с' , 'usam'); ?>: </label></div>
				<div class ="edit_form__item_option">
					<input type="text" id = "option_prices_time" name="prices_time" value="<?php echo $prices_time; ?>"/>
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_catalog_schedule'><?php esc_html_e( 'Автоматическое обновление остатков каждые' , 'usam'); ?>: </label></div>
				<div class ="edit_form__item_option">					
					<select name='stock_schedule'>						
						<option value=''><?php esc_html_e('Отключено' , 'usam'); ?></option>
						<?php
						foreach ( wp_get_schedules() as $cron => $schedule ) 
						{										
							?><option <?php selected( $stock_schedule, $cron ); ?> value='<?php echo $cron; ?>'><?php echo $schedule['display']; ?></option><?php
						}
						?>
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_stock_time'><?php esc_html_e( 'Начиная с' , 'usam'); ?>: </label></div>
				<div class ="edit_form__item_option">
					<input type="text" id = "option_stock_time" name="stock_time" value="<?php echo $stock_time; ?>"/>
				</div>
			</div>				
		</div>
		<?php		
	}	
		
	public function save_form( ) 
	{ 
		$metas = [];	
		$metas['type_price'] = isset($_POST['type_price'])?sanitize_text_field($_POST['type_price']):'';
		$metas['update_prices_schedule'] = isset($_POST['prices_schedule'])?sanitize_text_field($_POST['prices_schedule']):'';
		$metas['update_prices_schedule_time'] = !empty($_POST['prices_time'])?sanitize_text_field($_POST['prices_time']):'00:00';
		$metas['update_stock_schedule'] = isset($_POST['stock_schedule'])?sanitize_text_field($_POST['stock_schedule']):'';
		$metas['update_stock_schedule_time'] = !empty($_POST['stock_time'])?sanitize_text_field($_POST['stock_time']):'00:00';		
		foreach( $metas as $meta_key => $meta_value)
		{	
			usam_update_application_metadata($this->id, $meta_key, $meta_value);
		}
		$this->remove_hook( 'update_stock' );			
		$this->remove_hook( 'update_prices' );	
		if ( $this->is_token() )
		{
			if ( $this->option['active'] )
			{
				$this->add_hook('update_stock');
				$this->add_hook('update_prices');
			}
		}
	}
	
	public function save_category_form( $term_id, $tt_id ) 
	{ 
		if ( isset($_POST['wildberries_category'] ) )
			usam_update_term_metadata($term_id, 'wildberries_category', absint($_POST['wildberries_category']));
	}	
	
	function edit_category_forms( $tag, $taxonomy ) 
	{				
		add_action( 'admin_footer',  function() use ($tag){			
			?>
			<script>			
				var application_id = <?php echo $this->id; ?>;			
				var wildberries_category = <?php echo (int)usam_get_term_metadata($tag->term_id, 'wildberries_category'); ?>;					
			</script>	
			<?php			
		});			
		?>		
		<tr id="wildberries_categories" class="form-field">
			<th scope="row" valign="top"><?php esc_html_e( 'Категория Wildberries', 'usam'); ?></th>
			<td>
				<wildberries-categories @change="category=$event" :lists="categories" :selected="category"/>
				<input type='hidden' name="wildberries_category" v-model="category">						
			</td>			
		</tr>			
		<?php
	}
	
	public function register_routes( ) 
	{ 		
		register_rest_route( $this->namespace, $this->route.'/categories', [
			[
				'methods'  => 'GET',
				'callback' => [$this, 'get_categories'],	
				'args' => [],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || current_user_can('manage_options') || current_user_can('edit_product');
				},
			],			
		]);	
	}
	
	function register_bulk_actions( $bulk_actions ) 
	{				
		$bulk_actions['wildberries_update_products'] = __('Выгрузить в Wildberries', 'usam');
		return $bulk_actions;
	}	

	function bulk_action_handler( $redirect_to, $action, $post_ids )
	{
		switch ( $action ) 
		{						
			case 'wildberries_update_products':				
				if ( $post_ids )
					$this->update_products(['post__in' => $post_ids]);
			break;			
		}		
		return $redirect_to;
	}

	function update_stock_from_wildberries($id, $number, $event) 
	{		
		$done = $this->update_stock(['paged' => $event['launch_number']]);
		return ['done' => $done];
	}
	
	function update_prices_from_wildberries($id, $number, $event) 
	{		
		$done = $this->update_prices(['paged' => $event['launch_number']]);
		return ['done' => $done];
	}
	
	function cron_update_stock() 
	{				
		$i = usam_get_total_products(['productmeta_query' => [['key' => 'wildberries_id', 'compare' => 'EXISTS']]]);		
		usam_create_system_process( __("Обновление остатков в Wildberries", "usam" ), $this->id, [&$this, 'update_stock_from_wildberries'], $i, 'wildberries_application_update_stock-'.$this->id );		
	}
	
	function cron_update_prices() 
	{				
		$i = usam_get_total_products(['productmeta_query' => [['key' => 'wildberries_id', 'compare' => 'EXISTS']]]);		
		usam_create_system_process( __("Обновление цен в Wildberries", "usam" ), $this->id, [&$this, 'update_prices_from_wildberries'], $i, 'wildberries_application_update_prices-'.$this->id );		
	}
	
	function filter_options_exporting_product_platforms( $platforms ) 
	{				
		$platforms[] = ['id' => 'wildberries', 'name' => 'Wildberries'];
 		return $platforms;	
	}
	
	public function service_load( ) 
	{	
		add_action('rest_api_init', [$this,'register_routes']);	
		add_action( 'usam-category_edit_form_fields', [&$this, 'edit_category_forms'], 11, 2 );
		add_action( 'edited_usam-category', [&$this, 'save_category_form'], 10 , 2 );
		add_filter( 'bulk_actions-edit-usam-product', [&$this, 'register_bulk_actions'] );	
		add_action( 'handle_bulk_actions-edit-usam-product', [&$this, 'bulk_action_handler'], 10, 3);
				
		add_action('usam_application_update_stock_schedule_'.$this->option['service_code'],  [$this, 'cron_update_stock']);
		add_action('usam_application_update_prices_schedule_'.$this->option['service_code'],  [$this, 'cron_update_prices']);
		
		add_action('usam_filter_options_exporting_product_platforms',  [$this, 'filter_options_exporting_product_platforms']);		
	}
}