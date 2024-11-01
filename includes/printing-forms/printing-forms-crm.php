<?php		
class USAM_Printing_Form_crm extends USAM_Printing_Form
{		
	protected $totaldiscount = 0;	
	protected $total_tax = 0;	
	public function __construct( $id = null )
	{
		if ( !empty($id) )			
			$id = $id;				
		elseif ( isset($_GET['id']) && is_numeric($_GET['id']) )					
			$id = $_GET['id'];	
		else
		{	
			require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );
			$id = usam_get_documents(['fields' => 'id', 'number' => 1, 'type' => 'suggestion', 'order' => 'DESC']);			
		}		
		parent::__construct( $id );	
		
		$this->data = usam_get_document( $this->id );
		$this->products = usam_get_products_document( $this->id );
		
		$this->bank_account_id = $this->data['bank_account_id'];
		
		$this->price_args = ['currency_symbol' => false, 'currency_code' => false, 'type_price' => $this->data['type_price']];		
	}		
	
	protected function get_args( )
	{		
		require_once( USAM_FILE_PATH . '/includes/document/document_shortcode.class.php'  );
		$document = new USAM_Document_Shortcode( $this->data['id'] );	
		$args = $document->get_common_args( );	
		
		$customer_type = isset($this->data['customer_type'])?$this->data['customer_type']:'company';				
		if ( $customer_type == 'contact' )
		{
			$contact_data = usam_get_contact( $this->data['customer_id'] );		
			$args['customer_name'] = $contact_data['appeal']; 
			$args['customer_email'] = usam_get_contact_metadata($this->data['customer_id'], 'email' );		
			$args['customer_address'] = usam_get_full_contact_address( $this->data['customer_id'] );	
		}
		else
		{
			$args += usam_get_company_requisites( $this->data['customer_id'], 'customer' );			
			$bank_accounts = usam_get_company_bank_accounts( $this->data['customer_id'] );			
			
			if ( !empty($bank_accounts) )
				$args['counterparty_accounts'] = __('р/с','usam').' '.$bank_accounts[0]->number.' '.$bank_accounts[0]->name.' '.__('БИК', 'usam').' '.$bank_accounts[0]->bic.' '.$bank_accounts[0]->address.' '.__('кор/с','usam').' '.$bank_accounts[0]->bank_ca;
		} 
		$args += usam_get_company_by_acc_number( $this->data['bank_account_id'], 'recipient' );
		return $args;
	}		
	
	protected function blank_description( )
	{
		return array( 
			'%company_own_name%' => __('Название компании'), 
			'%company_own_inn%' => __('ИНН компании'), 
			'%company_own_ppc%' => __('КПП компании'), 
			'%company_own_legalpostcode%' => __('Индекс компании'), 
			'%company_own_legalcity%' => __('Город компании'), 
			'%company_own_legaladdress%' => __('Адрес компании') 
		);
	}
	
	protected function get_data_table(  )
	{					
		$taxes = usam_get_document_product_taxes( $this->data['id'] ); 
		$i = 0;
		$data = [];
		$prices = usam_get_prices(['type' => 'all', 'orderby' => 'id', 'order' => 'ASC']);
		foreach ( $this->products as $product ) 
		{
			$values = [];				
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
						$price = $product->old_price>0?$product->old_price:$product->price;	
						$column_html = usam_get_formatted_price($price, $this->price_args);
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
						foreach ( $taxes as $product_tax ) 
						{
							if ($product->product_id == $product_tax->product_id && $product->unit_measure == $product_tax->unit_measure)
							{
								$tax += $product_tax->tax;
							}								
						}		
						$column_html = usam_currency_display( $tax );
					break;
					case 'total' :
						$total = $product->price * $product->quantity;	
						$column_html = usam_get_formatted_price($total, $this->price_args);
					break;
					default:
						foreach ( $prices as $price )
						{
							if ( $column['name'] == 'price_'.$price['code'] )
							{
								$column_html = usam_get_product_price( $product->product_id, $price['code'] );
								break;
							}
						}
						if ( !$column_html )
							$column_html = usam_get_product_property( $product->product_id, $column['name'] );
					break;	
				}
				$values[$column['name']] = $column_html;
			}
			if ( $values )
				$data[] = $values;
		}
		return $data;
	}
	
	protected function display_table_tfoot(  )
	{	
		$cols = count( $this->options['table'] ) - 1;			
		?>
		[if total_discount>0 {
		<tr class="subtotal">
			<th colspan='<?php echo $cols; ?>'><?php esc_html_e( 'Стоимость', 'usam'); ?>:</td>
			<td>%document_subtotal%</td>
		</tr>
		<tr class='total_discount'>
			<th colspan='<?php echo $cols; ?>'><?php esc_html_e( 'Сумма скидки', 'usam'); ?>:</td>
			<td>%total_discount%</td>
		</tr>
		}]		
		<tr class='total_tax'>
			<th colspan='<?php echo $cols; ?>'><?php esc_html_e( 'В том числе НДС', 'usam'); ?>:</td>
			<td>%total_tax%</td>
		</tr>					
		<tr class="totalprice">
			<th colspan='<?php echo $cols; ?>'><?php esc_html_e( 'Итого', 'usam'); ?>:</td>
			<td>%total_price%</td>
		</tr>		
		<?php					
	}
}
?>