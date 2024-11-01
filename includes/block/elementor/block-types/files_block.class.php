<?php
namespace usam\Blocks\WP;

require_once( USAM_FILE_PATH . "/includes/block/wp/block.class.php" );
class Files extends Block
{		
	protected $enqueued_assets = true;	
	protected $block_name = 'files';		
	public function register_block_type() 
	{ 
		register_block_type(
			$this->namespace . '/' . $this->block_name,
			array(
				'render_callback' => array( $this, 'render' ),
				'editor_script'   => "usam-{$this->block_name}-block",
			/*	'editor_style'    => 'usam-block-editor',
				'style'           => 'usam-block-style',
				'script'          => 'usam-' . $this->block_name . '-frontend',
				'supports'        => [],*/
				'attributes'      => $this->get_attributes(), 
			)
		);	
	}
	
	public function render( $attributes = [], $content = '' ) 
	{ 
		$output = '';
		if ( !empty($attributes['folder_id']) )
		{ 
			require_once( USAM_FILE_PATH . '/includes/files/files_query.class.php' );	
			ob_start();	
			$files = \usam_get_files(['folder_id' => $attributes['folder_id']]); 
			foreach ( $files as $file ) 
			{
				?><div class ="files__file"><a href="<?php echo \home_url('file/'.$file->code); ?>"><?php echo $file->title; ?></a></div><?php
			}					
			$output = ob_get_clean();
		}
		return "<div class='files'>$output</div>";
	}
	
	protected function get_attributes() {
		return array(		
			'folder_id' => $this->get_schema_number( ),
			'message'   => $this->get_schema_string( ),			
		);
	}
}