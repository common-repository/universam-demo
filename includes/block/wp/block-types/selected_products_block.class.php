<?php
namespace usam\Blocks\WP;

require_once( USAM_FILE_PATH . "/includes/block/wp/block.class.php" );
class Selected_Products extends Block
{		
	protected $enqueued_assets = true;	
	protected $block_name = 'selected-products';		
	public function register_block_type() 
	{ 
		register_block_type(
			$this->namespace . '/' . $this->block_name,
			[
				'render_callback' => array( $this, 'render' ),
				'editor_script'   => "usam-{$this->block_name}-block",		
				'attributes'      => $this->get_attributes(), 
			]
		);
	}
	
	public function render( $attributes = [], $content = '' ) 
	{ 
		$output = '';
		if ( !empty($attributes['selected']) )
		{ 
			global $post, $lazy_loading;
			$lazy_loading = 0;		
						
			require_once( USAM_FILE_PATH . '/includes/theme/theme.functions.php'   );	
			require_once( USAM_FILE_PATH .'/includes/feedback/webform.php'  );
			require_once( USAM_FILE_PATH .'/includes/feedback/webforms_query.class.php'  );
			$type_price = usam_get_customer_price_code();	
			$args = ['post_type' => 'usam-product', 'type_price' => $type_price];				
			if ( isset($attributes['from_price']) && $attributes['from_price'] !== '' )
				$args['from_price'] = $attributes['from_price'];
			if ( !empty($attributes['to_price']) )
				$args['to_price'] = $attributes['to_price'];
			if ( isset($attributes['from_stock']) && $attributes['from_stock'] !== '' )
				$args['from_stock'] = $attributes['from_stock'];
			if ( !empty($attributes['to_stock']) )
				$args['to_stock'] = $attributes['to_stock'];		
			foreach( ["brands", "category", "category_sale", 'catalog', 'selection'] as $taxonomy)
			{
				if( !empty($attributes[$taxonomy]) )
					$args['usam-'.$taxonomy] = $attributes[$taxonomy];	
			}				
			if ( isset($attributes['orderby']) )		
				$args['orderby'] = $attributes['orderby'];
			else 			
				$args = usam_get_default_catalog_sort( $args, 'array' );   // сортировка по умолчанию
						
			$args = array_merge( $args, usam_product_sort_order_query_vars( $args['orderby'] ) );
			if( !empty($attributes['order']) )
				$args['order'] = $attributes['order'];
			
			if(!empty($attributes['limit']) )
			{
				$args['posts_per_page'] = $attributes['limit'];
			}				
			global $product_limit;
			$product_limit = !empty($attributes['column']) ? $attributes['column'] : 4;	
			query_posts( $args );						
			ob_start();		
			?>
			<div class="products_grid">
				<?php			
				while (usam_have_products()) :  			
					usam_the_product(); 			
					include( usam_get_template_file_path( $attributes['view_type'].'_product' ) );
				endwhile; 
				?>	
			</div>
			<?php			
			$output = ob_get_clean();
						
			wp_reset_query();
			wp_reset_postdata();
		}	
		return $output;
	}
	
	public function sorting_options( )	
	{ 
		$options = [];
		foreach( usam_get_product_sorting_options() as $key => $name)
			$options[] = ['id' => $key, 'name' => $name];
		return $options;
	}
	
	protected function get_attributes() {
		$sort = explode('-', get_option('usam_product_sort_by', 'date-desc') );
		return [
			'loaded'      => $this->get_schema_number(),
			'column'      => $this->get_schema_number( 4 ),			
			'to_price'    => $this->get_schema_number( ),
			'from_price'  => $this->get_schema_number( ),
			'to_stock'    => $this->get_schema_number( ),			
			'from_stock'  => $this->get_schema_number( ),			
			'orderby'     => $this->get_schema_string( $sort[0] ),	
			'order'       => $this->get_schema_string( isset($sort[1])?$sort[1]:'' ),			
			'limit'       => $this->get_schema_number( 4 ),
			'product_tag' => $this->get_schema_string( ),
			'category_sale' => $this->get_schema_string( ),
			'category'    => $this->get_schema_string( ),
			'brands'      => $this->get_schema_string( ),
			'selection'   => $this->get_schema_string( ),
			'catalog'     => $this->get_schema_string( ),
			'selected'    => $this->get_schema_number( 1 ),	
			'view_type'   => $this->get_schema_string( 'grid' ),		
			'sorting_options' => $this->get_schema_array( $this->sorting_options( ) ),			
		];		
	}
}