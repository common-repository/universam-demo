<?php
namespace usam\Blocks\WP;

require_once( USAM_FILE_PATH . "/includes/block/wp/block.class.php" );
class Stock_Points extends Block
{		
	protected $enqueued_assets = true;
	protected $block_name = 'stock-points';		
	public function register_block_type() 
	{ 
		register_block_type($this->namespace.'/'.$this->block_name,	array('render_callback' => array( $this, 'render' ), 'editor_script'   => "usam-{$this->block_name}-block",	 'attributes' => $this->get_attributes() ));		
	}
	
	public function render( $attributes = [], $content = '' ) 
	{ 		
		global $post;
		
		ob_start();	
		$args = ['number' => 50, 'cache_meta' => true, 'cache_location' => true, 'owner' => ''];
		if ( isset($attributes['issuing']) && $attributes['issuing'] != 2 )
			$args['issuing'] = $attributes['issuing'];
		if ( isset($attributes['type']) && $attributes['type'] != 'all' )
			$args['type'] = $attributes['type'];
		if ( isset($attributes['shipping']) && $attributes['shipping'] != 2 )
			$args['shipping'] = $attributes['shipping'];
		if ( !empty($attributes['location']) )
			$args['location_id'] = [0, usam_get_customer_location()];
		$storages = usam_get_storages( $args );		
		if ( $storages )
		{
			$product_not_available = true;
			?>
			<div class="stock_in_storages">
				<?php
			foreach ( $storages as $storage )
			{
				$in_stock = isset($post->ID) && usam_get_stock_in_storage($storage->id, $post->ID)?1:0;		
				if ( $in_stock )
				{
					$product_not_available = false;
					?>
					<div class="stock_in_storage">		
						<div class="stock_in_storage__name"><span class="stock_in_storage__name_text"><?php echo usam_get_storage_metadata( $storage->id, 'address'); ?></span></div>
						<?php
						if( !empty($attributes['type_price']) )
						{
							?><div class="stock_in_storage__price"><?php echo usam_get_product_price_currency($post->ID, false, $storage->type_price); ?></div><?php
						}
						?>
						<div class="stock_in_storage__stock <?php echo $in_stock?'usam_product_in_stock':'usam_product_not_available'; ?>"><?php echo usam_get_stock_in_storage($storage->id, $post->ID, 'short'); ?></div>
					</div>
					<?php
				}
			}
			?>
			</div><?php
			if ( $product_not_available )
			{
				?><p><?php _e('Этот товар отсутствует в магазинах', 'usam'); ?></p><?php
			}
		}
		return ob_get_clean();
	}
			
	protected function get_attributes() 
	{
		return [
			'settings' => $this->get_schema_number( 1 ),
			'issuing' => $this->get_schema_number( 2 ),
			'shipping' => $this->get_schema_number( 2 ),
			'type_price' => $this->get_schema_number( 0 ),
			'location' => $this->get_schema_number( 0 ),	
			'type' => $this->get_schema_string( 'all' ),				
		];
	}
}