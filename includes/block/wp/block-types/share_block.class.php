<?php
namespace usam\Blocks\WP;

require_once( USAM_FILE_PATH . "/includes/block/wp/block.class.php" );
class Share extends Block
{		
	protected $enqueued_assets = true;	
	protected $block_name = 'share';		
		
	public function render( $attributes = [], $content = '' ) 
	{ 
		global $post;
		ob_start();		
		$product_link = usam_product_url();
		?>
		<span class="share_buttons">
			<a href="https://twitter.com/intent/tweet?text=<?php echo urlencode(get_the_title( $post->ID )); ?>&url=<?php echo urlencode($product_link); ?>" rel="nofollow" target="_blank"><?php usam_svg_icon("twitter");?></a>
			<a href="https://vk.com/share.php?url=<?php echo urlencode($product_link); ?>" rel="nofollow" target="_blank"><?php usam_svg_icon("vk"); ?></a>
		</span>
		<?php
		return ob_get_clean();
	}
	
	protected function get_attributes() {
		$file_url = USAM_CORE_THEME_URL.'assets/sprite.svg';	
		if ( USAM_DEBUG_THEME )
			$file_url = str_replace(plugins_url('universam/theme'), WP_HOME, $file_url);		
		return [	
			'code'      => $this->get_schema_string( ),
			'twitter'  => $this->get_schema_string( $file_url.'?#twitter' ),
			'vk'  => $this->get_schema_string( $file_url.'?#vk' )
		];
	}
}