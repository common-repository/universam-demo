<?php				
new USAM_Autocomplete_Forms();
class USAM_Autocomplete_Forms
{		
	private $limit = 15;
	function __construct( )
	{	
		add_action('wp_ajax_autocomplete', array($this,'handler'), 5 );
		add_action('wp_ajax_nopriv_autocomplete', array($this,'handler'),5 );	

		add_action('wp_footer',array('USAM_Autocomplete_Forms','add_frontend_script_style'), 1);
		add_action('admin_footer',array('USAM_Autocomplete_Forms','add_frontend_script_style'), 1);
	}	
	
	public static function add_frontend_script_style() 
	{	
		wp_enqueue_script('jquery');	
		wp_enqueue_script( 'jquery-ui-autocomplete' );
	}
	
	public function handler() 
	{	
		if ( isset($_REQUEST['term']) && isset($_REQUEST['get']) )
		{				
			usam_set_nocache_constants();
			nocache_headers();
		
			check_ajax_referer( $_REQUEST['get'], 'security' );	
			$search = sanitize_text_field($_REQUEST['term']);				
			$result = array();			
			$callback = 'controller_'.sanitize_title($_REQUEST['get']);
			if ( method_exists( $this, $callback )) 
				$result = $this->$callback( $search );	

			echo json_encode( $result );			
		}
		exit;			
	}
	
	private function get_url( $method ) 
	{
		return add_query_arg( ['action' => 'autocomplete', 'get' => $method, 'security' => wp_create_nonce( $method )], admin_url( 'admin-ajax.php', 'relative' ) );
	}
			
	public static function controller_search_company( $search_keyword ) 
	{	
		$results = usam_find_company_in_directory(['search' => $search_keyword, 'search_type' => 'full']);			
		$items = array();
		if ( !empty($results) )
		{
			foreach ( $results as $result ) 
			{							
				$items[] = array('text' => $result['company_name'], 'label' => "<div>".$result['company_name']."</div><div>{$result['inn']} {$result['_name_legallocation']} {$result['legaladdress']}</div>", 'value' => $result['inn']);
			}	
		}
		else
			$items[] = array('text' => '', 'label' => __('Компании не найдены','usam'), 'value' => '');		
		return $items;	
	}
		
	public static function controller_customer_account( $search_keyword ) 
	{	
		global $wpdb;
	
		$items = array();	
		require_once( USAM_FILE_PATH . '/includes/customer/customer_accounts_query.class.php'  );
		$results = usam_get_customer_accounts( ['fields' => 'account_id', 'search' => $search_keyword.'*', 'number' => 50] );
		if ( !empty($results) )
		{
			foreach ( $results as $result ) 
			{							
				$items[] = array('text' => $result, 'value' => $result );
			}	
		}
		else
			$items[] = array('text' => '', 'label' => '', 'value' => '');	
		return $items;	
	}
			
	// Определение позиции
	public function controller_position_location( $search ) 
	{		
		$items = array();			
		$query = array( 'search' => $search.'*', 'number' => $this->limit );	
		if ( !empty($_REQUEST['code']))
		{
			$code = sanitize_title($_REQUEST['code']);					
			if ( $code != 'all' )
				$query['code'] = $code;		
		}
		else
			$query['code'] = array( 'subregion', 'city', 'village', 'urban_area', 'street' );
		
		$found_locations = usam_get_locations( $query );
		if ( !empty($found_locations) )
		{
			$cache = array();
			$ids = array();
			foreach ( $found_locations as $location ) 
			{
				$cache[$location->id] = usam_get_address_locations( $location->id, 'id' );	
				$ids = array_merge( $ids, array_values($cache[$location->id]) );	
			}
			$ids = array_unique($ids);				
			$locations = usam_get_locations(['include' => $ids]);	
			foreach ( $cache as $location_id => $ids)
			{
				$str = array();
				foreach ( $ids as $id )
				{
					foreach ( $locations as $location )
					{
						if ( $location->id == $id )
						{
							$str[] = $location->name;
							break;
						}
					}
				}
				$items[] = array('text' => implode(', ', $str), 'label' => implode(', ', $str), 'value' => $location_id);
			}	
			if ( $this->limit == count($locations) )
				$items[] = array( 'text' => '', 'label' => __('...','usam'), 'value' => 0 );
		}
		else
			$items[] = array('text' => '', 'label' => __('Местоположение не найдено.','usam'), 'value' => '');	
		return $items;	
	}

	public function controller_user( $search ) 
	{					
		$search = "*".$search;		

		$items = array();			
		$query = ['search' => $search, 'number' => $this->limit, 'source' => 'all', 'user_id__not_in' => 0];	
		$contacts = usam_get_contacts( $query );			
		if ( !empty($contacts) )
		{
			foreach ( $contacts as $contact ) 
			{					
				$items[] = ['text' => $contact->appeal, 'label' => $contact->appeal, 'value' => $contact->user_id];
			}
			if ( $this->limit == count($contacts) )
				$items[] = array('text' => '', 'label' => __('...','usam'), 'value' => 0);
		}
		else
			$items[] = array('text' => '', 'label' => __('Ничего не найдено','usam'), 'value' => '');	
		return $items;	
	}	
		
	public function controller_company( $search ) 
	{			
		$search = "*".$search."*";	
		$items = array();			
		$query = array( 'search' => $search, 'cache_bank_accounts' => true, 'number' => $this->limit );	
		if ( !empty($_REQUEST['type']) )
			$query['type'] = sanitize_title($_REQUEST['type']);
		$companies = usam_get_companies( $query );			
		if ( !empty($companies) )
		{
			foreach ( $companies as $company ) 
			{							
				$items[] = array('text' => $company->name, 'label' => $company->name, 'value' => $company->id );
			}
			if ( $this->limit == count($companies) )
				$items[] = array('text' => '', 'label' => __('...','usam'), 'value' => 0);
		}
		else
			$items[] = array('text' => '', 'label' => __('Компании не найдены','usam'), 'value' => '');
		return $items;	
	}

	function get_form_user( $user_id = null, $args = array() ) 
	{					
		$name = isset($args['name'])?$args['name']:'user';
		$text_default = empty($args['text'])?__("Введите имя...", "usam"):$args['text'];				
	
		if ( $user_id )
		{
			$contact = usam_get_contact( $user_id, 'user_id' );		
			$display_text = !empty($contact)?$contact['appeal']:'';	
		}		
		else
			$display_text = '';
		$url = $this->get_url( 'user' );				
		?>			       	
		<div class="text_search_block">
			<input type="hidden" name="<?php echo $name; ?>" value="<?php echo $user_id; ?>" class="js-autocomplete-user">
			<input autocomplete="off" type="text" placeholder='<?php echo $text_default; ?>' value="<?php echo esc_html($display_text); ?>" class="js-autocomplete" data-url="<?php echo $url; ?>"/>
		</div>
		<?php
	}	
		
	function get_form_company( $company_id = null, $args = array() ) 
	{							
		$name = empty($args['name'])?'company':$args['name'];
		$text_default = empty($args['text'])?__("Введите название компании или ИНН...", "usam"):$args['text'];
						
		$company = usam_get_company( $company_id );
		$display_text = !empty($company)?$company['name']:'';	
					
		$company_type = '';	
		$url = $this->get_url( 'company' );			
		if ( !empty($args['query']) )
			$url = add_query_arg( $args['query'], $url );		
		?>		
		<div class="text_search_block">
			<input type="hidden" name="<?php echo $name; ?>" value="<?php echo $company_id; ?>" class="js-autocomplete-company" >
			<input autocomplete="off" type="text" placeholder='<?php echo $text_default; ?>' value="<?php echo esc_html($display_text); ?>" class="js-autocomplete" data-url="<?php echo $url; ?>"/>
		</div>
		<?php
	}		
			
	// Определение позиции
	function get_form_position_location( $location_id = 0, $args = array() ) 
	{					
		$name = empty($args['name'])?'location':$args['name'];
		$text_default = empty($args['text'])?__("Введите название и выберете из списка...", "usam"):$args['text'];	
		$display_text = usam_get_full_locations_name( $location_id );	//'%street% %village% %urban_area% %city% %region% %country%'
	
		$url = $this->get_url( 'position_location' );
		if ( !empty($args['code']) )
			$url = add_query_arg( array( 'code' => $args['code'] ), $url );	
		?>		
		<div class="text_search_block">
			<input type="hidden" name="<?php echo $name; ?>" value="<?php echo $location_id; ?>" class="js-autocomplete-location">
			<input autocomplete="off" type="text" placeholder='<?php echo $text_default; ?>' value="<?php echo esc_html($display_text); ?>" class="js-autocomplete" data-url="<?php echo $url; ?>"/>
		</div>     
		<?php
	}
}
?>