<?php	
require_once( USAM_FILE_PATH . "/includes/printing-forms/printing-forms-order.php" );		
class USAM_Printing_Form_payment extends USAM_Printing_Form_Order
{				
	protected $license_products = [];			
	protected $shipped_documents = [];	
	protected $taxes_license_products = [];	
	
	public function get_data( )
	{						
		$this->purchase_log = new USAM_Order( $this->id );		
		if ( !$this->purchase_log->exists() )	
			return false;
		$this->data = $this->purchase_log->get_data();		
		$this->products = usam_get_products_order( $this->id );	
		$shipped_documents = usam_get_shipping_documents_order( $this->id );	
	
		$this->price_args = ['currency_symbol' => false, 'currency_code' => false, 'type_price' => $this->data['type_price']];
		$this->product_taxes = usam_get_order_product_taxes( $this->id ); 
		foreach ( $shipped_documents as $document ) 
		{ 
			if ( empty($document->price) || $document->price == '0.00' )
				continue;
			
			$delivery_method = usam_get_delivery_service( $document->method );
			if( !$document->include_in_cost )
			{								
				$document->courier_company = empty($delivery_method['courier_company'])?get_option( 'usam_shop_company' ):$delivery_method['courier_company'];
				$document->tax_name = usam_get_shipped_document_metadata( $document->id, 'tax_name' );
				$this->shipped_documents[] = $document;
				$this->data['totalprice'] = $this->data['totalprice']-$document->price;
			}
			else
			{	
				$product = new stdClass();
				$product->name = __('Оплата за услуги по доставке','usam');
				$product->product_id = 0;
				$product->price = $document->price;
				$product->quantity = 1;
				$product->unit_measure = '';	
				$product->old_price = $document->price;
				$this->products[] = $product;
				
				if ( $document->tax_id )
				{
					$tax = new stdClass();
					$tax->name = stripcslashes(usam_get_shipped_document_metadata( $document->id, 'tax_name' ));
					$tax->product_id = 0;
					$tax->tax_id = $document->tax_id;
					$tax->tax = $document->tax_value;
					$tax->is_in_price = usam_get_shipped_document_metadata( $document->id, 'tax_is_in_price' );
					$tax->rate = usam_get_shipped_document_metadata( $document->id, 'tax_rate' );
					$this->product_taxes[] = $tax; 
				}
			}
		}	
		foreach ( $this->products as $key => $product ) 
		{
			$license_agreement = usam_get_product_meta( $product->product_id, 'license_agreement' );
			if ( $license_agreement )
			{
				$this->license_products[] = $product;
				unset($this->products[$key]);
			}
		}	 				
		$this->taxes = $this->get_order_taxes( $this->id, $this->products );  
		if ( !empty($this->license_products) )
			$this->taxes_license_products = $this->get_order_taxes( $this->id, $this->license_products );		
		$this->bank_account_id = $this->data['bank_account_id'];		
	}	
	
	function get_order_taxes( $order_id, $products )
	{	
		$products_quantity = array();
		foreach ( $products as $product ) 
			$products_quantity[$product->product_id] = $product->quantity;
		
		$results = array();
		foreach ( $this->product_taxes as $product_tax ) 
		{		
			$tax = $products_quantity[$product_tax->product_id] * $product_tax->tax;
			if ( isset($results[$product_tax->tax_id]) )
				$results[$product_tax->tax_id]['tax'] += $tax;
			else
				$results[$product_tax->tax_id] = ['name' => stripcslashes($product_tax->name), 'tax' => $tax];
		}	
		return $results;
	}

	protected function display_table_tfoot(  )
	{	
		$cols = count( $this->options['table'] ) - 4;	
		$totalprice = 0;
		$discont = 0; 
		$taxes = array();
		foreach ( $this->product_taxes as $product_tax ) 
		{
			if ( $product_tax->is_in_price == 0 )
			{
				$taxes[$product_tax->product_id] = isset($taxes[$product_tax->product_id])?$taxes[$product_tax->product_id]:0;
				$taxes[$product_tax->product_id] += $product_tax->tax;
			}
		}
		foreach ( $this->products as $key => $product ) 
		{
			$tax = !empty($taxes[$product->product_id])?$taxes[$product->product_id]:0;			
			$totalprice += ($product->price + $tax) * $product->quantity;
			$discont += ($product->old_price - $product->price) * $product->quantity;
		}		
		if ( $discont || !empty($this->taxes) ) { ?>
		<tr class="subtotal">
			<th colspan='<?php echo $cols; ?>'></td>
			<th colspan='3' style="text-align:right;"><?php esc_html_e( 'Итого', 'usam'); ?>:</th>
			<td><?php echo usam_get_formatted_price($totalprice+$discont, $this->price_args); ?></td>
		</tr>			
		<?php 
		}
		if ( $discont ) { ?>			
		<tr class='total_discount'>
			<th colspan='<?php echo $cols; ?>'></td>
			<th colspan='3' style="text-align:right;"><?php esc_html_e( 'Скидка', 'usam'); ?>:</th>
			<td><?php echo usam_get_formatted_price( $discont, $this->price_args ); ?></td>
		</tr>
		<?php } ?>		
		<?php 			
		if( !empty($this->taxes) )
		{ 
			foreach ( $this->taxes as $tax ) 
			{
			?>
			<tr>
				<td colspan='<?php echo $cols; ?>'></td>
				<th colspan='3' style="text-align:right;"><?php echo $tax['name']; ?>:</th>
				<td><?php echo $tax['tax'] == 0?__('без НДС', 'usam'):usam_currency_display( $tax['tax'] ); ?></td>
			</tr>
			<?php 
			}
		}	
		?>						
		<tr class="totalprice">
			<th colspan='<?php echo $cols; ?>'></td>
			<th colspan='3' style="text-align:right;"><?php esc_html_e( 'Всего к оплате', 'usam'); ?>:</th>
			<td><?php echo usam_get_formatted_price($totalprice, $this->price_args); ?></td>
		</tr>		
		<?php					
	}	
	
	protected function get_args( )
	{		
		$date_format = get_option('date_format', "d.m.Y");			
		$totalprice = 0;
		foreach ( $this->products as $key => $product ) 
		{
			$totalprice += $product->price * $product->quantity;			
		}	
		$currency_args = ['currency_symbol' => true, 'currency_code' => false, 'type_price' => $this->data['type_price']];
		$sum = explode(".", $totalprice);		
		
		$order_shortcode = new USAM_Order_Shortcode( $this->id );
		$args = $order_shortcode->get_common_args();
		
		$args['number_products'] = count($this->products);
		$args += array(					
			'total_price'          => usam_get_formatted_price($totalprice, $this->price_args),	
			'total_price_currency' => usam_get_formatted_price( $this->data['totalprice'], $currency_args ),
			'total_price_word'     => usam_get_number_word( $this->data['totalprice'] ),	
			'totalprice1'          => $sum[0],	
			'totalprice2'          => isset($sum[1])?$sum[1]:'00',								
		);	
		foreach ( $this->shipped_documents as $key => $document ) 
			$args +=  usam_get_company_by_acc_number( $document->courier_company, 'shipped_'.$document->courier_company );
		
		if ( !empty($this->data['bank_account_id']) )
			$args += usam_get_company_by_acc_number( $this->data['bank_account_id'], 'recipient' );
		
		return $args;
	}		
}
?>