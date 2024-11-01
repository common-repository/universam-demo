<?php		
class USAM_Printing_Form_Order extends USAM_Printing_Form
{		
	protected $purchase_log;	
	protected $payment_document;	
	protected $shipped_document;
	protected $taxes = [];	
	protected $product_taxes = [];	
	protected $price_args = [];	
	
	public function __construct( $id = null )
	{ 
		if ( !empty($id) )			
			$id = $id;			
		elseif ( isset($_GET['id']) && is_numeric($_GET['id']) )					
			$id = $_GET['id'];		
		else
			$id = usam_get_orders(['fields' => 'id', 'number' => 1, 'order' => 'DESC']);
		parent::__construct( $id );
		$this->get_data();	
		$this->bank_account_id = $this->data['bank_account_id'];	
	}	
	
	public function get_data( )
	{				
		$this->purchase_log = new USAM_Order( $this->id );		
		$this->data = $this->purchase_log->get_data();	
		
		$this->price_args = ['currency_symbol' => false, 'currency_code' => false, 'type_price' => $this->data['type_price']];
		$this->products = usam_get_products_order( $this->id );	
		$this->payment_document = $this->purchase_log->get_payment_document();
		$this->shipped_document = $this->purchase_log->get_shipped_document();		
		$this->product_taxes = usam_get_order_product_taxes( $this->id ); 
		$this->taxes = usam_get_order_taxes( $this->id );
	}	

	protected function display_table_tbody(  )
	{		
		$i = 0;
		foreach ( $this->products as $product )
		{			
			$total = $product->price * $product->quantity;		
			$row = '';
			foreach ( $this->options['table'] as $column ) 
			{			
				$column_html ='';				
				
				switch ( $column['name'] ) 
				{
					case 'n' :					
						$i++;
						$column_html = $i; 
					break;	
					case 'name' :
						$column_html = stripcslashes($product->name); 
					break;							
					case 'quantity' :
						$column_html = usam_get_formatted_quantity_product( $product->quantity, $product->unit_measure );
					break;				
					case 'price' :
						$column_html = usam_get_formatted_price($product->old_price, $this->price_args);
					break;					
					case 'discount_price' :
						$column_html = usam_get_formatted_price( $product->price, $this->price_args );
					break;
					case 'discount' :
						$discount = $product->old_price>0?($product->old_price - $product->price):0;
						$column_html = usam_get_formatted_price( $discount, $this->price_args );
					break;
					case 'tax' :						
						$tax = 0;						
						foreach ( $this->product_taxes as $product_tax ) 
						{										
							if ($product->product_id == $product_tax->product_id && $product->unit_measure == $product_tax->unit_measure)
							{ 
								$tax += $product_tax->tax;
							}
						}						
						$column_html = usam_currency_display( $tax ); 
					break;
					break;
					case 'total' :
						$column_html = usam_get_formatted_price($total, $this->price_args);
					break;
					case 'unit_measure' :
						$unit = usam_get_unit_measure( $product->unit_measure );
						if ( $unit )
							$column_html = $unit['short'];
					break;
					default:								
						if ( stripos($column['name'], 'storage_') !== false )
						{
							$id = str_replace('storage_', '', $column['name']);
							$column_html = usam_get_stock_in_storage( $id, $product->product_id, 'short' );
						}
						else
							$column_html = usam_get_product_property( $product->product_id, $column['name'] );
					break;						
				}
				$row .= "<td class ='column-".$column['name']."'>$column_html</td>";
			}	
			echo "<tr>$row</tr>";
		}
	}
	
	protected function display_table_tfoot( )
	{	
		$cols = count( $this->options['table'] ) - 4;			
		?>		
		<tr class="subtotal">
			<th colspan='<?php echo $cols; ?>'></th>
			<th colspan='3' style="text-align:right;"><?php esc_html_e( 'Стоимость', 'usam'); ?>:</th>
			<th>%order_basket%</th>
		</tr>
		[if total_discount>0.0 {<tr class='total_discount'>
		<th colspan='<?php echo $cols; ?>'></th>
			<th colspan='3' style="text-align:right;"><?php esc_html_e( 'Скидка', 'usam'); ?>:</th>
			<th>%total_discount%</th>
		</tr>}]		
		[if order_basket_discount>0.0 {<tr class='total_discount'>
		<th colspan='<?php echo $cols; ?>'></th>
			<th colspan='3' style="text-align:right;"><?php esc_html_e( 'Стоимость с учетом скидки', 'usam'); ?>:</th>
			<th>%order_final_basket%</th>
		</tr>}]					
		[if total_shipping>0.0 {<tr class="total_shipping">
			<th colspan='<?php echo $cols; ?>'></th>
			<th colspan='3' style="text-align:right;"><?php esc_html_e( 'Доставка', 'usam'); ?></th>
			<th>%total_shipping%</th>
		</tr>	
		}]		
		<?php 			
		if( !empty($this->taxes) )
		{
			foreach ( $this->taxes as $tax ) 
			{
			?>
			<tr>
				<th colspan='<?php echo $cols; ?>'></th>
				<th colspan='3' style="text-align:right;"><?php echo $tax['name']; ?>:</th>
				<th><?php echo round($tax['tax'], 2); ?></th>
			</tr>
			<?php 
			}
		}	
		?>					
		<tr class="totalprice">
			<th colspan='<?php echo $cols; ?>'></th>
			<th colspan='3' style="text-align:right;"><?php esc_html_e( 'Итого', 'usam'); ?>:</th>
			<th>%total_price%</th>
		</tr>		
		<?php
	}	
	
	protected function get_args( )
	{		
		$order_shortcode = new USAM_Order_Shortcode( $this->id );
		$args = $order_shortcode->get_common_args();	
		if ( !empty($this->data['bank_account_id']) )
			$args +=  usam_get_company_by_acc_number( $this->data['bank_account_id'], 'recipient' );
		return $args;
	}		
}
?>