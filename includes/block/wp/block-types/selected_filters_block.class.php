<?php
namespace usam\Blocks\WP;

require_once( USAM_FILE_PATH . "/includes/block/wp/block.class.php" );
class Selected_Filters extends Block
{		
	protected $enqueued_assets = true;	
	protected $block_name = 'selected-filters';		
	
	public function render( $instance = [], $content = '' ) 
	{ 
		add_action( 'wp_footer', ['USAM_Assets', 'product_filter']);	
		ob_start();	
		?>
		<div class='selected_catalog_filters' v-cloak>
			<div class='selected_catalog_filters__item' v-for="(attr, k) in attributes">
				<span v-html="attr.name"></span>
				<?php usam_svg_icon("close", ["@click" => "del(k)"]); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}