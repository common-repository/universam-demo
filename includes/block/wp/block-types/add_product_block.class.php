<?php
namespace usam\Blocks\WP;

require_once( USAM_FILE_PATH . "/includes/block/wp/block.class.php" );
class Add_Product extends Block
{		
	protected $enqueued_assets = true;	
	protected $block_name = 'add-product';			
	
	public function render( $attributes = [], $content = '' ) 
	{ 
		ob_start();
		?>
		<div id="add_product">
			<?php usam_include_template_file('add_product', 'template-parts'); ?>
		</div>
		<?php
		$output = ob_get_clean();
		return $output;
	}	
}