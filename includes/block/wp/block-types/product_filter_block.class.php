<?php
namespace usam\Blocks\WP;

require_once( USAM_FILE_PATH . "/includes/block/wp/block.class.php" );
class Product_Filter extends Block
{		
	protected $enqueued_assets = true;	
	protected $block_name = 'product-filter';		
	
	protected function get_default_option( ) 
	{
		return ['storages' => 0, 'individual_price' => 0, 'range_price' => 0, 'categories' => 'no_hierarchy', 'product_rating' => 0, 'filter_activation' => 'button'];
	}
	
	public function render( $instance = [], $content = '' ) 
	{ 
		add_action( 'wp_footer', ['USAM_Assets', 'product_filter']);
		$instance = wp_parse_args((array)$instance, $this->get_default_option());
		ob_start();	
		?>
		<div id='product_filters' class='filter_activation_<?php echo $instance['filter_activation']; ?>' v-cloak>
			<div class='filters_form_button'>
				<span class='filters_form_button__title' @click="click_tab('filter')" v-if="filter_display"><?php _e("Фильтры","usam") ?></span>
				<span class='filters_form_button__title' @click="click_tab('category')"><?php _e("Категории","usam")  ?></span>
			</div>	
			<div class='filters_form filters_form_panel' :class="[tab=='filter'?'active':'']" v-show="filter_display"><?php include( usam_get_template_file_path('product-filter') ); ?></div>
			<div class="categories_form filters_form_panel" :class="[tab=='category'?'active':'']" v-html="categories"></div>
		</div>
		<?php
		return ob_get_clean();
	}
	
	protected function get_attributes() {
		return array(		
			'filter_activation' => $this->get_schema_string( ),
			'storages' => $this->get_schema_boolean( ),			
			'individual_price' => $this->get_schema_boolean( ),		
			'range_price' => $this->get_schema_boolean( ),	
			'product_rating' => $this->get_schema_boolean( ),
			'categories' => $this->get_schema_string( ),
			'filter_activation' => $this->get_schema_string( ),			
		);
	}
}