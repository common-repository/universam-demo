<?php
namespace usam\Blocks\WP;

require_once( USAM_FILE_PATH . "/includes/block/wp/block.class.php" );
class Promotion_Timer extends Block
{		
	protected $enqueued_assets = true;
	protected $block_name = 'promotion-timer';	
	
	public function render( $attributes = [], $content = '' ) 
	{ 		
		global $post;
		
		ob_start();			
		if ( !empty($post->ID) && usam_product_has_stock() )
		{					
			$date = '';
			if( !empty($attributes['promotion_option']) )
			{
				$ids = usam_get_active_products_day_id_by_codeprice();			
				if( in_array( $post->ID, $ids ) )
					$date = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d")+1, date("Y")));
			} 
			if( !$date )
			{
				$discounts = usam_get_current_product_discount( $post->ID );		
				$code_price = usam_get_customer_price_code();
				if( !empty($discounts[$code_price]) )
				{	
					$rule = usam_get_discount_rule( $discounts[$code_price][0] );				
					if( !empty($rule['end_date']) && (empty($attributes['day']) || round((strtotime($rule['end_date'])-time()) / (60 * 60 * 24)) <= $attributes['day'] ) )
						$date = $rule['end_date'];
				}
			}
			if( $date )
			{
				?>
				<div class="promotion_timer">
					<timer :date="'<?php echo $date; ?>'"></timer>
				</div>
				<?php			
			}
		}
		return ob_get_clean();
	}
			
	protected function get_attributes() 
	{		
		return [
			'settings' => $this->get_schema_number( 1 ),
			'day' => $this->get_schema_number( 1 ),	
			'promotion_option' => $this->get_schema_string(),				
		];
	}
}