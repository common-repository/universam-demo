<?php
class USAM_Document_Shortcode
{		
	protected $document_id = 0;		
	protected $args = array();	
	public function __construct( $document_id ) 
	{	
		if ( is_numeric($document_id) )	
			$this->document_id = $document_id;		
		else
			return false;
		
		$this->args = $this->common_args();
	}
	
	private function common_args() 
	{	
		$data = usam_get_document( $this->document_id );
		$products = usam_get_products_document( $data['id'] );
		
		$price_args = ['currency_symbol' => false, 'currency_code' => false, 'type_price' => $data['type_price']];		
		$formatted_price_currency = ['currency_symbol' => true, 'decimal_point' => true, 'currency_code' => false, 'type_price' => $data['type_price']];				
		$subtotal = 0;	
		$tax = 0;
		$totaldiscount = 0;
		$document_subtotal = 0;
		$license_agreement_ids = [];
		foreach ( $products as $product ) 		
		{
			$document_subtotal += $product->old_price * $product->quantity;
			$subtotal += ($product->price+$tax)*$product->quantity;			
			$totaldiscount += ($product->old_price-$product->price)*$product->quantity;
			$license_agreement_ids[] = usam_get_product_meta($product->product_id, 'license_agreement');			
		}		
		$license_agreement = '';
		if ( $license_agreement_ids )
		{
			$posts = get_posts(['post_type' => 'usam-agreement','post__in' => $license_agreement_ids]);
			foreach ( $posts as $post ) 
			{
				$license_agreement .= '<p>'.get_permalink($post->ID).'</p>';
			}
		}
		$total_tax = usam_get_tax_amount_document( $data['id'] );	
		$display_total_tax = $total_tax == ''?__('без НДС', 'usam'):usam_currency_display( $total_tax, $price_args );					
		$date_format = "d.m.Y";
		$customer_type = isset($data['customer_type'])?$data['customer_type']:'company';		
		$description = usam_get_document_content($data['id'], 'description');
		$conditions = usam_get_document_content($data['id'], 'conditions');		
		$author = usam_get_contact( $data['manager_id'] );		
		$args = array(			
			'date'               => usam_local_date($data['date_insert'], $date_format),
			'closedate'          => !empty($data['closedate'])?sprintf( __('Срок оплаты: %s', 'usam'),usam_local_date($data['closedate'], $date_format)):'',
			'id'                 => $data['number'],
			'document_number'    => $data['number'],			
			'document_subtotal'  => usam_get_formatted_price( $document_subtotal, $price_args ),	
			'document_subtotal_currency'  => usam_get_formatted_price( $document_subtotal, $formatted_price_currency ),
			'subtotal'           => usam_get_formatted_price( $subtotal, $price_args ),	
			'subtotal_currency'  => usam_get_formatted_price( $subtotal, $formatted_price_currency ),
			'status_name'        => !empty($data['status'])?$data['status']:'',
			'total_tax'           => $display_total_tax,
			'total_tax_currency'  => usam_currency_display($total_tax,  $formatted_price_currency ),
			'total_tax_title'     => sprintf( __('Налог: %s', 'usam'), $total_tax )."\r\n",
			'total_price'         => usam_get_formatted_price($data['totalprice'], $price_args ),
			'total_price_currency'=> usam_get_formatted_price( $data['totalprice'], $formatted_price_currency ),		
			'total_price_word'    => mb_ucfirst( usam_get_number_word( $data['totalprice'] ) ),			
			'total_discount'      => usam_get_formatted_price($totaldiscount, $price_args ),		
			'total_discount_currency' => usam_get_formatted_price( $totaldiscount, $formatted_price_currency ),	
			'description'         => !empty($description)?nl2br($description):'',	
			'conditions'          => !empty($conditions)?nl2br($conditions):'',	
			'number_products'     => count($products),	
			'code_type_payer'     => $customer_type,
			'document_content'    => usam_get_document_content( $this->document_id, 'document_content' ),	
			'document_author'      => !empty($author)?$author['appeal']:'',	
			'document_author_post' => (string)usam_get_contact_metadata($data['manager_id'], 'post'),			
			'customer_email'       => '',
			'license_agreement'    => $license_agreement,
		);			
		$requisites = usam_get_company_by_acc_number( $data['bank_account_id'], 'recipient' );
		if ( $requisites )		
		{
			$requisites['shop_name'] = get_option( 'blogname' );	
			$requisites['shop_logo'] = '<img class="shop_logo" src="'.usam_get_company_logo( $requisites['recipient_id'] ).'" alt ="logo" width="100">';
			$requisites['shop_mail'] = usam_get_shop_mail();
			$requisites['shop_phone'] = usam_get_shop_phone();	
			$args += $requisites;			
			$sum = $data['totalprice']*100;
			if ( $sum > 0 )
			{
				$qr_str = "ST00012|Name={$args['recipient_company_name']}|PersonalAcc={$args['recipient_bank_number']}|BankName={$args['recipient_bank_name']}|BIC={$args['recipient_bank_bic']}|CorrespAcc={$args['recipient_bank_ca']}|PayeeINN={$args['recipient_inn']}|KPP={$args['recipient_ppc']}|Sum={$sum}|Purpose=Оплата счета №{$data['number']}";
				$args['qr'] = usam_get_qr( $qr_str );
			}
			else 
				$args['qr'] = '';
		} 
		$this->args = apply_filters( 'usam_document_notification_common_args', $args, $this );		
		return $this->args;
	}

	public function get_common_args() 
	{				
		return $this->args;
	}
	
	// из аргументов собрать строку
	public function get_html( $html ) 
	{			
		$shortcode = new USAM_Shortcode();		
		$html = $shortcode->process_args( $this->args, $html );
	
		$html = wpautop( $html );			
		return $html;
	}	
}