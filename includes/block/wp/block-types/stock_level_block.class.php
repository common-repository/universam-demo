<?php
namespace usam\Blocks\WP;

require_once( USAM_FILE_PATH . "/includes/block/wp/block.class.php" );
class Stock_Level extends Block
{		
	protected $enqueued_assets = true;	
	protected $block_name = 'stock-level';		
		
	public function render( $attributes = [], $content = '' ) 
	{ 
		global $post;
		$output = '';
		if ( !empty($post->post_type) && $post->post_type == 'usam-product')
		{ 
			$max_stock = usam_get_product_stock($post->ID, 'max_stock');
			$stock = usam_product_remaining_stock($post->ID, 'stock');
			if ( $max_stock )
			{ 
				$r = round($stock * 100 / $max_stock, 2);
				ob_start();	
				?>
				<span class='product-stock-level' v-cloak location="<?php echo (int)!empty($attributes['location']); ?>">					
					<div class='product_stock_level' @click="load">
						<div class='stock_level' ref="columns">
							<div class='stock_level_column <?php echo ($r >= 5?"stock_level_column_full":"").($r < 5 && $r > 0?"stock_level_column_empty":""); ?>'></div>
							<div class='stock_level_column <?php echo($r >= 66.66?"stock_level_column_full":""); ?>'></div>
							<div class='stock_level_column <?php echo ($r >= 85?"stock_level_column_full":""); ?>'></div>
						</div>
						<?php if ( !empty($attributes['stock_number']) ) { ?>
							<div class='stock_number'><?php echo $stock; ?></div>
						<?php } ?>
					</div>
					<hint-window ref="productStockLevel">
						<template v-slot:content>
							<div class='hint_name'><?php _e('Остаток на складах', 'usam'); ?></div>
							<div class='stock_in_storages' v-if="storages.length">
								<div class="stock_in_storage" v-for="storage in storages">		
									<div class="stock_in_storage__name"><span class="stock_in_storage__name_text" v-html="storage.address"></span></div>
									<div class="stock_in_storage__stock" :class="[storage.in_stock?'usam_product_in_stock':'usam_product_not_available']" v-html="storage.stock"></div>
								</div>
							</div>
							<p v-else-if="loaded"><?php _e('Этот товар отсутствует в магазинах', 'usam'); ?></p>
						</template>						
					</hint-window>					
				</span>
				<?php
				$output = ob_get_clean();
			}
		}	
		return $output;
	}
	
	protected function get_attributes() {
		return [
			'code'      => $this->get_schema_string(),
			'location'  => $this->get_schema_number(0),
			'stock_number'  => $this->get_schema_number(1),
		];
	}
}