<?php
namespace usam\Blocks\WP;

require_once( USAM_FILE_PATH . "/includes/block/wp/block.class.php" );
class ProductTag extends Block
{		
	protected $enqueued_assets = true;	
	protected $block_name = 'product-tag';	
	
	public function render( $attributes = [], $content = '' ) 
	{ 
		$args = ['orderby' => 'name', 'taxonomy' => 'product_tag'];
		if ( !empty($attributes['slugs']) )
			$args['slug'] = $attributes['slugs'];	
		elseif ( is_tax() )
		{			
			global $wp_query;
			$term = $wp_query->get_queried_object();
			if( isset($term->term_id) )
				$args['connection'][$term->taxonomy] = [ $term->term_id ];
		}		
		$terms = \get_terms( $args );
		ob_start();		
		?>
		<div class="list_terms product_tags">
			<?php			
			foreach( $terms as $term ) 		
			{
				?><div class="list_terms__term product_tag"><a href='<?php echo get_term_link($term->term_id, $term->taxonomy) ?>'><?php echo $term->name; ?></a></div><?php	
			}
			?>	
		</div>
		<?php			
		$output = ob_get_clean();
		return $output;
	}
	
	protected function get_attributes() {
		return [				
			'start' => $this->get_schema_number(0),
			'slugs' => $this->get_schema_array(),
		];		
	}
}