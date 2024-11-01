<?php
abstract class USAM_Trading_Platforms_Exporter
{		
	protected $rule;
	protected $url;
	protected $max_size = 500;
	protected $product_attributes = [];
	protected $file_type = 'xml';
	
	public function __construct( $rule ) 
	{			
		$this->rule = $rule;
		$metas = usam_get_feed_metadata( $rule['id'] );
		foreach($metas as $metadata )
			$this->rule[$metadata->meta_key] = maybe_unserialize($metadata->meta_value);
		$this->rule = array_merge(['campaign' => 0], $this->rule);
		$this->rule = array_merge($this->get_default_option(), $this->rule);
		$this->rule = array_merge(['product_description' => '', 'product_characteristics' => []], $this->rule);
		$this->rule['currency'] = usam_get_currency_price_by_code( $this->rule['type_price'] );	
		$this->url = home_url('trading-platform/feed/'.$this->rule['id']);
	}
	
	protected function get_default_option( ) 
	{
		return [];
	}
	
	public function get_args( ) 
	{
		$per_page = !empty($this->rule['limit'])?$this->rule['limit']:$this->max_size;	
		$args = ['product_attribute_cache' => true, 'orderby' => $this->rule['orderby'], 'post_status' => 'publish', 'order' => $this->rule['order'], 'paged' => 1, 'posts_per_page' => $per_page, 'meta_query' =>[], 'tax_query' => []];
		
		foreach( ['category', 'brands', 'category_sale', 'catalog', 'selection'] as $key )
		{
			if ( !empty($this->rule[$key]) )
				$args['tax_query'][] = ['taxonomy' => 'usam-'.$key, 'field' => 'id', 'terms' => $this->rule[$key], 'operator' => 'IN'];		
		}	
		if ( !empty($this->rule['product_tag']) )
			$args['tax_query'][] = ['taxonomy' => 'product_tag', 'field' => 'id', 'terms' => $this->rule['product_tag'], 'operator' => 'IN'];			
		if ( !empty($this->rule['contractors']) )
			$args['productmeta_query'] = [['key' => 'contractor', 'value' => $this->rule['contractors'], 'compare' => 'IN']];		
		if ( !empty($this->rule['from_price']) )
		{			
			$args['from_price'] = $this->rule['from_price'];
			$args['type_price'] = $this->rule['type_price'];
		}
		if ( !empty($this->rule['to_price']) )
		{			
			$args['to_price'] = $this->rule['to_price'];
			$args['type_price'] = $this->rule['type_price'];
		}
		if ( !empty($this->rule['from_day']) )
			$args['date_query']['after'] = $this->rule['from_day']." day ago";
		if ( !empty($this->rule['to_day']) )
			$args['date_query']['before'] = $this->rule['to_day']." day ago";
		if ( !empty($this->rule['from_stock']) )
			$args['from_stock'] = $this->rule['from_stock'];
		if ( !empty($this->rule['to_stock']) )
			$args['to_stock'] = $this->rule['to_stock'];
		if ( !empty($this->rule['from_views']) )
			$args['from_views'] = $this->rule['from_views'];
		if ( !empty($this->rule['to_views']) )
			$args['to_views'] = $this->rule['to_views'];
		$args['feed'] = true;		
		return $args;
	}
	
	protected function get_export_file( $data ) 
	{	
		return $data;	
	}
	
	public function start() 
	{			
		set_time_limit(1800);
		
		if ( usam_is_license_type('FREE') )
			return [];
			
		$this->product_attributes = get_terms(['hide_empty' => 0, 'orderby' => 'sort', 'taxonomy' => 'usam-product_attributes', 'term_meta_cache' => true]);		
		$args = $this->get_args();	
		if ( $this->file_type == 'xml' )
			$data = '';
		else
			$data = [];
		$limit = $this->rule['limit'];		
		$this->before_calculations();
		do 
		{		
			$products = usam_get_products( $args, true );			
			foreach ( $products as $product )
			{			
				$result = $this->get_export_product( $product );	
				$result = apply_filters( 'usam_export_product_trading_platform', $result, $this->rule, $product );
				if ( $this->file_type == 'xml' )
					$data .= $result;
				else
					$data[] = $result;				
				usam_clean_product_cache( $product->ID );
			}						
			if ( $limit )
			{
				$limit = $limit - count($products);	
				if ( $limit < 1 )
					break;
			}
			$args['paged']++;	
		} 
		while ( count($products) );
		$results = $this->get_export_file( $data );	
		return $results;
	}	
	
	protected function before_calculations( ) {}
	
	protected function get_product_title( $post ) 
	{ 
		if ( !empty($this->rule['product_title']) )
			$post_title = usam_get_product_attribute_display( $post->ID, $this->rule['product_title'] );
		else
			$post_title = $post->post_title;
		return $this->text_decode( $post_title );
	}	
	
	protected function get_product_attribute( $attribute ) 
	{
		return isset($this->product_attributes[$this->rule[$attribute]])?$this->product_attributes[$this->rule[$attribute]]['value']:'';
	}
	
	protected function get_attribute( $product_id, $property ) 
	{
		if ( isset($this->rule['columns'][$property]) )
			return usam_get_product_attribute_display( $product_id, $this->rule['columns'][$property] );
		else
			return '';
	}
	
	protected function get_product_url( $product_id ) 
	{
		return htmlspecialchars(usam_get_url_utm_tags( $this->rule['campaign'], usam_product_url($product_id) ));
	}	
	
	protected function get_availability( $product_id )
	{
		if( usam_is_product_under_order( $product_id ) )
			$result = 'available for order';
		elseif ( usam_product_has_stock() )
			$result = 'in stock';
		else
			$result = 'out of stock';		
		return $result;
	}	
	
	protected function text_decode( $text ) 
	{
		$text = usam_remove_emoji( $text );
		$text = strip_shortcodes( $text );		
		$text = html_entity_decode( $text );
		$text = strip_tags( $text );		
		$text = preg_replace("/[\t\r\n]+/",' ', trim($text) ); // Переносы строк заменить на пробелы
		$text = preg_replace("| +|", " ", $text);	// Несколько пробелов заменить на один		
		$text = str_replace( "&", " ", $text );		
		$text = str_replace( "x", " ", $text );			
		return $text;		
	}			
			
	public function upload_file( ) 
	{ 	
		if ( usam_validate_rule( $this->rule ) || current_user_can("view_platforms") )
		{
			$data = $this->start();
			if ( !$data )
				return false;
			
			if ( $this->file_type == "xml" )
			{						 
				header("Content-Type: application/xml; charset=UTF-8");
				header('Content-Disposition: inline; filename="Product_List.xml"');					
			}			
			elseif ( $this->file_type == 'xls' || $this->file_type == 'xlsx' )
			{ 
				header('Content-Type: application/vnd.ms-excel');
				header('Content-Disposition: attachment;filename="'.$this->rule['name'].'.'.$this->file_type.'"');
				if ( $data )
				{ 
					$writer = usam_write_exel_file( $data, ['file_type' => $this->file_type] );
					ob_start();	
					$writer->save('php://output');
					$data = ob_get_clean();	
				}
				else
					$data = '';
			}	
			else
			{ 
				header("Content-Type: text/html; charset=UTF-8");
				header('Content-Disposition: attachment;filename="'.$this->rule['name'].'.'.$this->file_type.'"');	
				$data = $this->get_file_content( $data );				
			}			
			header('Cache-Control: max-age=0');	
			echo $data;				
			exit();
		}
	}	
	
	function get_file_content( $data ) 
	{
		$delimiter = usam_get_type_file_exchange( $this->file_type, 'delimiter' );			
		$rows = [];
		foreach($data as $row)
		{		
			$values = [];				
			foreach($row as $column => $value)
			{						
				if ( isset($row[$column]) )
					$values[] = $row[$column];		
			}
			$rows[] = implode( $delimiter, $values );		
		}
		if ( !$rows )
			return '';
		if ( stripos($delimiter, '"') !== false )
			$output = '"'.implode( "\"\n\"", $rows ).'"';
		else
			$output = implode( "\n", $rows );
		
		return $output;
	}
		
	public function get_data( ) 
	{
		return $this->rule;
	}
	
	public function get_js_form_data( ) 
	{
		return [];
	}		
	public function get_form( ) {}		
	function save_form( ) 
	{	
		return [];
	}
	
	protected function display_form_campaign( ) 
	{		
		?>		
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php esc_html_e( 'Рекламная компания', 'usam'); ?>:</div>
			<div class ="edit_form__item_option">
				<autocomplete :uniqueid="data.campaign" @change="data.campaign=$event.id" :request="'campaigns'" :query="{fields:'id=>title'}" :none="'<?php _e('Нет данных','usam'); ?>'" :placeholder="'<?php _e('Поиск','usam'); ?>'"></autocomplete>
				<input type="hidden" name="campaign" v-model="data.campaign">	
			</div>
		</div>
		<?php
	}	
}
?>