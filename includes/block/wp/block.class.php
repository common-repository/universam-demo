<?php
namespace usam\Blocks\WP;

abstract class Block
{				
	protected $namespace = 'usam';
	protected $block_name = '';	
	protected $enqueued_assets = false;
	public function __construct( ) 
	{
		add_action( 'enqueue_block_editor_assets', [$this, 'enqueue_editor_assets'] );	
		add_filter( 'block_categories_all', [ __CLASS__, 'block_categories_all' ], 10, 2 );		
	}	
	
	public function register_block_type() 
	{ 
		register_block_type($this->namespace.'/'.$this->block_name, 
		[
			'render_callback' => [$this, 'render'], 
			'editor_script' => "usam-{$this->block_name}-block", 
			'attributes' => $this->get_attributes()
		]);
	}
		
	public static function block_categories_all( $block_categories, $block_editor_context ) 
	{
	/*	if ( $post->post_type !== 'post' ) {
			return $categories;
		}*/
		return array_merge($block_categories, [['slug' => 'usam', 'title' => __('Универсам', 'usam'), 'icon'  => '']]);
	}	
	
	public function enqueue_editor_assets()
	{
		wp_enqueue_style( 'usam-global-style' ); 
		$anonymous_function = function() { 
			$site_style = usam_get_site_style();
			?>
			<style id="tmpl-color-scheme-css">		
				:root{			
					<?php foreach( $site_style as $key => $style ){ echo "--$key: ".get_theme_mod( $key, $style['default'] ).";"; } ?>
				}		
			</style>
			<?php
		}; 
		add_action('admin_footer', $anonymous_function, 111);	
	//	wp_enqueue_style( 'usam-bloks-style' );
		if ( $this->enqueued_assets )
		{
			wp_enqueue_script("usam-{$this->block_name}-block", USAM_URL ."/admin/assets/js/blocks/{$this->block_name}/block.js", array( 'wp-blocks', 'wp-element' ), USAM_VERSION_ASSETS );
			if ( file_exists(USAM_FILE_PATH ."/admin/assets/css/blocks/{$this->block_name}.css") )
				wp_enqueue_style("usam-{$this->block_name}-block", USAM_URL ."/admin/assets/css/blocks/{$this->block_name}.css", true, USAM_VERSION_ASSETS );	
			$this->enqueue_editor();
		}
	}
	
	public function enqueue_editor( ) {  }
	
	protected function get_schema_number( $default = 0 ) 
	{
		return ['type' => 'number', 'default' => $default];
	}
	
	protected function get_schema_string( $default = '' ) 
	{
		return ['type' => 'string', 'default' => $default];
	}
	
	protected function get_schema_array( $default = [] ) 
	{
		return ['type' => 'array', 'default' => $default, 'items' => ['type' => 'string']];
	}
	
	protected function get_schema_object( $default = null ) 
	{
	//	if ( $default === null )
	//		$default = new stdClass();
		return ['type' => 'object', 'default' => $default];
	}
	
	protected function get_schema_boolean( $default = true ) 
	{
		return ['type' => 'boolean', 'default' => $default];
	}
	
	protected function get_schema_align() 
	{
		return ['type' => 'string', 'enum' => ['left', 'center', 'right', 'wide', 'full']];
	}
	
	protected function get_attributes() {
		return [];
	}
}