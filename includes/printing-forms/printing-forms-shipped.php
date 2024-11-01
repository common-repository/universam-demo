<?php		
class USAM_Printing_Form_shipped extends USAM_Printing_Form
{		
	private $purchase_log;	
	private $shipped_document = array();	
	
	public function __construct( $id = null )
	{
		if ( !empty($id) )			
			$id = $id;			
		elseif ( isset($_GET['id']) && is_numeric($_GET['id']) )					
			$id = $_GET['id'];		
		else
		{		
			require_once(USAM_FILE_PATH.'/includes/document/shipped_documents_query.class.php');
			$id = usam_get_shipping_documents(['fields' => 'id', 'number' => 1, 'order' => 'DESC']);				
		}				
		parent::__construct( $id );
		
		$this->shipped_document = usam_get_shipped_document( $this->id );
		if ( empty($this->shipped_document) )
			return false;
		
		if ( $this->shipped_document['order_id'] )
		{
			$this->purchase_log = new USAM_Order( $this->shipped_document['order_id'] );		
			$this->data = $this->purchase_log->get_data();		 
			
			$products = usam_get_products_order( $this->shipped_document['order_id'] );		
			foreach ( usam_get_products_shipped_document( $this->id ) as $shipped_product ) 
			{
				foreach ( $products as $order_product ) 
				{
					if ( $order_product->product_id == $shipped_product->product_id )
					{
						$order_product->quantity = $shipped_product->quantity;
						$this->products[] = $order_product;
					}
				}
			}			
			$this->bank_account_id = $this->data['bank_account_id'];
		}	
		else
			return false;
	}	
	
	protected function display_table_tbody(  )
	{				
		foreach ( $this->products as $product ) 
		{
			$total = ( $product->price * $product->quantity);		
			$row = '';
			foreach ( $this->options['table'] as $column ) 
			{						
				$column_html = '';				
				switch ( $column['name'] ) 
				{
					case 'name' :
						$column_html = '<p style="text-align: left;white-space:normal;">'.$product->name.'</p>'; 
					break;
					case 'sku' :
						$column_html = usam_get_product_meta( $product->product_id, 'sku' );
					break;
					case 'barcode' :
						$column_html = usam_get_product_barcode( $product->product_id );
					break;				
					case 'quantity' :
						$column_html = usam_get_formatted_quantity_product( $product->quantity, $product->unit_measure );
					break;
					case 'price' :
						$column_html = usam_get_formatted_price($product->price);
					break;
					case 'total' :
						$column_html = usam_get_formatted_price($total);
					break;
					default:
						$column_html = usam_get_product_property( $product->product_id, $column['name'] );
					break;
				}
				$row .= "<td class ='column-".$column['name']."'>$column_html</td>";
			}
			echo "<tr>$row</tr>";
		}			
	}
	
	protected function display_table_tfoot(  )
	{	
		$cols = count( $this->options['table'] ) - 2;	
		?>
		<tr class="subtotal">
			<td colspan='<?php echo $cols; ?>'></td>
			<th><?php esc_html_e( 'Сумма', 'usam'); ?>:</th>
			<td>%total_price_currency%</td>
		</tr>	
		<?php					
	}
	
	protected function get_args( )
	{					
		$totalprice = 0;
		foreach ( $this->products as $product ) 
		{
			$totalprice += $product->price*$product->quantity;
		}				
		$args['shipped_notes'] = usam_get_shipped_document_metadata($this->id, 'note');
		$args['shipped_price'] = $this->shipped_document['price'];
		$args['shipped_price_currency'] = usam_get_formatted_price($this->shipped_document['price']);		
		
		$price_args = ['currency_symbol' => false, 'currency_code' => false];
		if ( $this->shipped_document['order_id'] ) 
		{
			$order_shortcode = new USAM_Order_Shortcode( $this->shipped_document['order_id'], $this->id );
			$args += $order_shortcode->get_common_args();	
		}	
		$args['id'] = $this->id;	
		$args['date'] = usam_local_date( $this->shipped_document['date_insert'], 'd.m.Y' );
		$args['number_products'] = count($this->products);
		$args['total_price'] = usam_get_formatted_price($totalprice, $price_args);		
		$args['total_price_currency'] = usam_get_formatted_price($totalprice);
		$args['total_price_word'] = mb_ucfirst( usam_get_number_word( $this->shipped_document['price'] ) );
		return $args;
	}
}
?>