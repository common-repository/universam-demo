<?php
new USAM_Document_Rest_API();
class USAM_Document_Rest_API
{	
	protected $namespace = 'usam/v1';
	
	function __construct( )
	{	
		add_action('rest_api_init', [$this,'register_routes'] );	
	}
	
	public function viewing_not_allowed( $type )
    {
		return current_user_can('universam_api') || current_user_can('view_'.$type) || current_user_can('department_view_'.$type) || current_user_can('company_view_'.$type) || current_user_can('any_view_'.$type);
	}
			
	public function register_routes()
    {				
		require_once( USAM_FILE_PATH . '/includes/exchange/API/rest_route/documents_API.class.php' );
		$documents_args = [			
			'order' => [
				'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'status' => ['type' => 'string', 'required' => false],		
				'bank_account_id' => ['type' => 'integer', 'required' => false],	
				'price_external_code' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				'note' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],				
				'type_price' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				'manager_id' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],	
				'code' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				'source' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],		
				'user_ID' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],	
				'type_payer' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
				'bonuses' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],					
				'exchange' => ['type' => 'integer', 'required' => false],
				'coupon_name' => ['type' => 'string,integer', 'required' => false],
				'date_exchange' => ['type' => 'data', 'required' => false],		
				'cancellation_reason' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],		
				'add_shipping' => ['type' => 'integer', 'required' => false],		
				'change_shipping' => ['type' => 'integer', 'required' => false],					
				'change_payment' => ['type' => 'integer', 'required' => false],					
				'external_document' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				'external_document_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'date_pay_up' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'address_id' => ['type' => 'integer', 'required' => false],
				'products' => [
					'id' => ['type' => 'integer', 'required' => false],
					'product_id' => ['type' => 'integer', 'required' => false],
					'quantity' => ['type' => 'float', 'required' => false],	
					'price' => ['type' => 'float', 'required' => false],	
					'old_price' => ['type' => 'float', 'required' => false],	
					'unit_measure' => ['type' => 'string', 'required' => false],
					'bonus' => ['type' => 'integer', 'required' => false],						
				],
				'store_code' => ['type' => 'integer,string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				'properties' => [
					[ 
						'key' => ['type' => 'string', 'required' => false],
						'value' => ['type' => 'string', 'required' => false],
					],
				],					
			],
			'lead' => [			
				'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'bank_account_id' => ['type' => 'integer', 'required' => false],
				'type_price' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				'price_external_code' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				'status' => ['type' => 'string', 'required' => false],		
				'source' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],	
				'manager_id' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],	
				'user_id' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],	
				'type_payer' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],						
				'totalprice' => ['type' => 'string,integer', 'required' => false],
				'exchange' => ['type' => 'integer', 'required' => false],
				'date_exchange' => ['type' => 'data', 'required' => false],		
				'cancellation_reason' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],		
				'external_document' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				'external_document_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'products' => [
					'id' => ['type' => 'integer', 'required' => false],
					'product_id' => ['type' => 'integer', 'required' => false],
					'quantity' => ['type' => 'float', 'required' => false],	
					'price' => ['type' => 'float', 'required' => false],	
					'old_price' => ['type' => 'float', 'required' => false],	
					'unit_measure' => ['type' => 'string', 'required' => false],
					'bonus' => ['type' => 'integer', 'required' => false],	
				],
				'properties' => [
					[ 
						'key' => ['type' => 'string', 'required' => false],
						'value' => ['type' => 'string', 'required' => false],
					],
				],		
			],
			'payment' => [					
				'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'date_payed' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'status' => ['type' => 'string', 'required' => false],				
				'bank_account_id' => ['type' => 'integer', 'required' => false],
				'name' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],				
				'transactid' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				'external_document' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				'external_document_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
			],
			'invoice' => [					
				'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'status' => ['type' => 'string', 'required' => false],		
				'document_type' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],	
				'contract' => ['type' => 'integer', 'required' => false],					
				'conditions' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],
				'note' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],
				'title' => ['type' => 'string', 'required' => false],
				'bank_account_id' => ['type' => 'integer', 'required' => false],
				'manager_id' => ['type' => 'integer', 'required' => false],						
				'products' => ['type' => 'array', 'required' => false],					
				'external_document' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				'external_document_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'closedate' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'groups' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
			],
			'suggestion' => [					
				'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'status' => ['type' => 'string', 'required' => false],						
				'conditions' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],
				'note' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],
				'title' => ['type' => 'string', 'required' => false],
				'bank_account_id' => ['type' => 'integer', 'required' => false],
				'manager_id' => ['type' => 'integer', 'required' => false],						
				'products' => ['type' => 'array', 'required' => false],					
				'external_document' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				'external_document_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'closedate' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'groups' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
			],
			'act' => [					
				'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'status' => ['type' => 'string', 'required' => false],				
				'note' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],
				'title' => ['type' => 'string', 'required' => false],
				'bank_account_id' => ['type' => 'integer', 'required' => false],
				'manager_id' => ['type' => 'integer', 'required' => false],						
				'products' => ['type' => 'array', 'required' => false],					
				'external_document' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				'external_document_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],				
				'groups' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
			],			
			'buyer_refund' => [					
				'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'note' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],
				'status' => ['type' => 'string', 'required' => false],		
				'title' => ['type' => 'string', 'required' => false],
				'bank_account_id' => ['type' => 'integer', 'required' => false],
				'bank_account_code' => ['string' => 'integer', 'required' => false],	
				'manager_id' => ['type' => 'integer', 'required' => false],
				'store_id' => ['type' => 'integer', 'required' => false],					
				'store_code' => ['type' => 'integer,string', 'required' => false],				
				'order_external_code' => ['type' => 'integer,string', 'required' => false],
				'code' => ['type' => 'integer,string', 'required' => false],
				'price_external_code' => ['type' => 'integer,string', 'required' => false],
				'products' => ['type' => 'array', 'required' => false],	
				'external_document' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				'external_document_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
			],
			'check' => [					
				'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'note' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],
				'status' => ['type' => 'string', 'required' => false],		
				'payment_type' => ['type' => 'string', 'required' => false],
				'title' => ['type' => 'string', 'required' => false],
				'bank_account_id' => ['type' => 'integer', 'required' => false],
				'manager_id' => ['type' => 'integer', 'required' => false],
				'store_id' => ['type' => 'integer', 'required' => false],
				'type_price' => ['type' => 'string', 'required' => false],	
				'price_code' => ['type' => 'string', 'required' => false],					
				'store_code' => ['type' => 'integer,string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				'products' => ['type' => 'array', 'required' => false],	
				'external_document' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				'external_document_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
			],
			'contract' => [					
				'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'note' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],
				'status' => ['type' => 'string', 'required' => false],		
				'title' => ['type' => 'string', 'required' => false],
				'bank_account_id' => ['type' => 'integer', 'required' => false],
				'manager_id' => ['type' => 'integer', 'required' => false],			
				'closedate' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'external_document' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				'external_document_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
			],
			'proxy' => [					
				'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'note' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],
				'status' => ['type' => 'string', 'required' => false],		
				'title' => ['type' => 'string', 'required' => false],
				'bank_account_id' => ['type' => 'integer', 'required' => false],
				'manager_id' => ['type' => 'integer', 'required' => false],			
				'closedate' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'external_document' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				'external_document_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
			],	
			'receipt' => [					
				'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'note' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],
				'status' => ['type' => 'string', 'required' => false],		
				'title' => ['type' => 'string', 'required' => false],
				'contract' => ['type' => 'integer', 'required' => false],		
				'bank_account_id' => ['type' => 'integer', 'required' => false],
				'manager_id' => ['type' => 'integer', 'required' => false],			
				'store_id' => ['type' => 'integer', 'required' => false],				
				'closedate' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'external_document' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				'external_document_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
			],
			'invoice_payment' => [					
				'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'note' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],
				'status' => ['type' => 'string', 'required' => false],		
				'title' => ['type' => 'string', 'required' => false],
				'contract' => ['type' => 'integer', 'required' => false],		
				'bank_account_id' => ['type' => 'integer', 'required' => false],
				'manager_id' => ['type' => 'integer', 'required' => false],	
				'closedate' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'external_document' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				'external_document_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
			],	
			'reconciliation_act' => [	
				'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'note' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],
				'status' => ['type' => 'string', 'required' => false],		
				'title' => ['type' => 'string', 'required' => false],				
				'bank_account_id' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
				'manager_id' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],	
				'start_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'end_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'external_document' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				'external_document_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
			],	
			'partner_order' => [	
				'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'note' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],
				'status' => ['type' => 'string', 'required' => false],		
				'title' => ['type' => 'string', 'required' => false],
				'contract' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],		
				'bank_account_id' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
				'manager_id' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],					
				'external_document' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				'external_document_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
			],	
			'payment_order' => [	
				'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'note' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],
				'status' => ['type' => 'string', 'required' => false],		
				'title' => ['type' => 'string', 'required' => false],
				'contract' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],		
				'bank_account_id' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
				'manager_id' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],					
				'external_document' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				'external_document_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
			],			
			'shipped' => [		
				'method' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
				'note' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_textarea']],
				'status' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],		
				'date_insert' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'date_delivery' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],				
				'track_id' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				'order_id' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
				'courier' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
				'storage_pickup' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
				'storage' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint'],
				'price' => ['type' => 'integer', 'required' => false],			
				'products' => ['type' => 'array', 'required' => false],	
				'external_document' => ['type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field'],
				'external_document_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],
				'readiness_date' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_date']],				
			],
		];		
		$documents = usam_get_details_documents();
		foreach($documents as $type => $document)
		{
			if ( in_array($type, ['order', 'lead', 'payment', 'shipped']) )
				$function = 'delete_'.$type;
			else
				$function = 'delete_document';
			$args = isset($documents_args[$type])?$documents_args[$type]:['status' => ['type' => 'string', 'required' => false]];
			register_rest_route( $this->namespace, '/'.$type.'/(?P<id>\d+)', [		
				[ 
					'methods'  => 'GET',
					'callback' => ['USAM_Documents_API', 'get_'.$type],				
					'args' => [
						'fields' => ['type' => ['string','array'], 'required' => false],
					],
					'permission_callback' => function( $request ) use ($type) {
						$permission_callback = $type == 'order' ? is_user_logged_in() : $this->viewing_not_allowed('check');
						return current_user_can('universam_api') || $permission_callback;
					}
				],				
				[ 
					'methods'  => 'POST',
					'callback' => ['USAM_Documents_API', 'update_'.$type],	
					'args' => $args,
					'permission_callback' => function( $request ) use ($type){
						return current_user_can('universam_api') || is_user_logged_in();
					},
				],
				[ 
					'methods'  => 'DELETE',
					'callback' => ['USAM_Documents_API', 'delete_document'],					
					'permission_callback' => function( $request ) use ($type){
						return current_user_can('universam_api') || current_user_can('delete_'.$type);
					},
				],		
			]);			
			register_rest_route( $this->namespace, '/'.$type, [		
				[ 
					'methods'  => 'POST',
					'callback' => ['USAM_Documents_API', 'insert_'.$type],				
					'args' => $args,
					'permission_callback' => function( $request ) use ($type){
						return current_user_can('universam_api') || current_user_can('add_'.$type);
					}
				]				
			]);	
		}
		register_rest_route( $this->namespace, '/orders_contractor', [	
			[
				'methods'  => 'GET,POST',
				'args' => [
					'fields' => ['type' => ['string','array'], 'required' => false],
					'status' => ['type' => ['array', 'string'], 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('sanitize_text_field', (array)$param); } ],
					'orderby' => ['type' => 'string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],
					'include' => ['type' => 'array', 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('intval', $param); } ],
				],
				'callback' => ['USAM_Documents_API', 'get_orders_contractor'],				
				'permission_callback' => function( $request ){
					return $this->viewing_not_allowed('check');
				},
			],				
			[ // Обновить
				'methods'  => 'PUT',
				'callback' => ['USAM_Documents_API', 'save_orders_contractor'],
				'args' => [					
					'items' => ['type' => 'array', 'required' => true],					
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || is_user_logged_in();
				}
			],
			[ 
				'methods'  => 'DELETE',
				'callback' => ['USAM_Documents_API', 'delete_documents'],					
				'permission_callback' => function( $request ) use ($type){
					return current_user_can('universam_api') || current_user_can('delete_'.$type);
				},
			],			
		]);	
		register_rest_route( $this->namespace, '/checks', [	
			[
				'methods'  => 'GET,POST',
				'args' => [
					'fields' => ['type' => ['string','array'], 'required' => false],
					'status' => ['type' => ['array', 'string'], 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('sanitize_text_field', (array)$param); } ],
					'orderby' => ['type' => 'string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],
					'include' => ['type' => 'array', 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('intval', $param); } ],
				],
				'callback' => ['USAM_Documents_API', 'get_checks'],				
				'permission_callback' => function( $request ){
					return $this->viewing_not_allowed('check');
				},
			],				
			[ // Обновить
				'methods'  => 'PUT',
				'callback' => ['USAM_Documents_API', 'save_checks'],
				'args' => [					
					'items' => ['type' => 'array', 'required' => true],					
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || is_user_logged_in();
				}
			],			
		]);	
		register_rest_route( $this->namespace, '/acts', [	
			[
				'methods'  => 'GET,POST',
				'args' => [
					'fields' => ['type' => ['string','array'], 'required' => false],
					'status' => ['type' => ['array', 'string'], 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('sanitize_text_field', (array)$param); } ],
					'orderby' => ['type' => 'string', 'required' => false],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],
					'include' => ['type' => 'array', 'required' => false, 'validate_callback' => function( $param, $request, $key ) { array_map('intval', $param); } ],
				],
				'callback' => ['USAM_Documents_API', 'get_acts'],				
				'permission_callback' => function( $request ){
					return $this->viewing_not_allowed('check');
				},
			],				
			[ // Обновить
				'methods'  => 'PUT',
				'callback' => ['USAM_Documents_API', 'save_acts'],
				'args' => [					
					'items' => ['type' => 'array', 'required' => true],					
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || is_user_logged_in();
				}
			],			
		]);		
		register_rest_route( $this->namespace, '/document/approve/(?P<id>\d+)', [		
			[ 
				'methods'  => 'POST',
				'callback' => ['USAM_Documents_API', 'manager_approving_document'],				
				'args' => [					
					'status' => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],		
				],
				'permission_callback' => function( $request ){	
					return current_user_can('universam_api') || is_user_logged_in();
				}
			],
		]);
		register_rest_route( $this->namespace, '/leads', array(		
			[ // получить список заказов
				'methods'  => 'GET,POST',
				'callback' => ['USAM_Documents_API', 'get_leads'],	
				'args' => [
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'status' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'status__not_in' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'user' => ['type' => 'string,integer,array', 'required' => false],
					'bank_account_id' => ['type' => 'array', 'required' => false],	
					'paid' => ['type' => 'integer', 'required' => false],
					'exchange' => ['type' => 'integer', 'required' => false, 'enum' => [0,1], 'default' => 0],
					'fields' => ['type' => ['string','array'], 'required' => false],
					'orderby' => ['type' => 'string', 'required' => false, 'default' => 'id'],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],
					'count' => ['type' => 'integer', 'required' => false], 
					'paged' => ['type' => 'integer', 'required' => false],
				],
				'permission_callback' => function( $request ){						
					return $this->viewing_not_allowed('lead');
				},
			],				
			[ // Обновить
				'methods'  => 'PUT',
				'callback' => ['USAM_Documents_API', 'save_leads'],
				'args' => [					
					'items' => ['type' => 'array', 'required' => true],					
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || is_user_logged_in();
				}
			],			
		));				
		register_rest_route( $this->namespace, '/shippeds', array(		
			[ 
				'methods'  => 'GET,POST',
				'callback' => ['USAM_Documents_API', 'get_shippeds'],	
				'args' => [
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'status' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'status_type' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'courier_delivery' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],	
					'storage_pickup' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
					'storage' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
					'export' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],						
					'exchange' => ['type' => 'integer', 'required' => false, 'enum' => [0,1], 'default' => 0],
					'fields' => ['type' => ['string','array'], 'required' => false],
					'add_fields' => ['type' => ['string','array'], 'required' => false],					
					'orderby' => ['type' => 'string', 'required' => false, 'default' => 'id'],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],
					'count' => ['type' => 'integer', 'required' => false], 
					'paged' => ['type' => 'integer', 'required' => false],
				],
				'permission_callback' => function( $request ){						
					return $this->viewing_not_allowed('shipped');
				},
			],		
			[
				'methods'  => 'PUT',
				'callback' => ['USAM_Documents_API', 'save_shippeds'],
				'args' => [					
					'items' => ['type' => 'array', 'required' => true],					
				],
				'permission_callback' => function( $request ){
					return $this->viewing_not_allowed('shipped');
				}
			],			
			[
				'methods'  => 'DELETE',
				'callback' => ['USAM_Documents_API', 'delete_shippeds'],
				'args' => [					
					'args' => ['type' => 'object', 'required' => true],					
				],
				'permission_callback' => function( $request ){
					return $this->viewing_not_allowed('shipped');
				}
			]		
		));
		register_rest_route( $this->namespace, '/shipped/recalculate/(?P<id>\d+)', [
			[
				'methods'  => 'GET',
				'callback' => ['USAM_Documents_API', 'recalculate_shipped'],				
				'permission_callback' => function( $request ){
					return $this->viewing_not_allowed('shipped');
				}
			],			
		]);
		register_rest_route( $this->namespace, '/shipped/tracking/(?P<id>\d+)', [
			[
				'methods'  => 'GET',
				'callback' => ['USAM_Documents_API', 'tracking_shipped'],				
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || is_user_logged_in();
				}
			],			
		]);
		register_rest_route( $this->namespace, '/shipped/move/(?P<id>\d+)', [
			[
				'methods'  => 'GET',
				'callback' => ['USAM_Documents_API', 'create_move_shipped'],				
				'permission_callback' => function( $request ){
					return $this->viewing_not_allowed('movement');
				}
			],			
		]);		
		register_rest_route( $this->namespace, '/shipped/order/transport/(?P<id>\d+)', [
			[
				'methods'  => 'GET',
				'callback' => ['USAM_Documents_API', 'create_order_transport_company'],				
				'permission_callback' => function( $request ){
					return $this->viewing_not_allowed('shipped');
				}
			],			
		]);
		register_rest_route( $this->namespace, '/delivery/problems', [
			[
				'methods'  => 'GET',
				'callback' => ['USAM_Documents_API', 'get_delivery_problems'],				
				'permission_callback' => function( $request ){
					return $this->viewing_not_allowed('shipped');
				}
			],			
		]);
		register_rest_route( $this->namespace, '/delivery/services', [
			[
				'methods'  => 'GET',
				'callback' => ['USAM_Documents_API', 'get_delivery_services'],				
				'args' => [					
					'order_id' => ['type' => 'integer', 'required' => true],					
				],
				'permission_callback' => function( $request ){
					return $this->viewing_not_allowed('shipped');
				}
			],			
		]);		
		register_rest_route( $this->namespace, '/contracts', array(		
			[ 
				'methods'  => 'GET,POST',
				'callback' => ['USAM_Documents_API', 'get_contracts'],	
				'args' => [
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'status' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'status_type' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'add_fields' => ['type' => ['string','array'], 'required' => false],					
					'orderby' => ['type' => 'string', 'required' => false, 'default' => 'id'],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],
					'count' => ['type' => 'integer', 'required' => false], 
					'paged' => ['type' => 'integer', 'required' => false],
				],
				'permission_callback' => function( $request ){						
					return $this->viewing_not_allowed('contract');
				},
			],		
			[
				'methods'  => 'PUT',
				'callback' => ['USAM_Documents_API', 'save_contracts'],
				'args' => [					
					'items' => ['type' => 'array', 'required' => true],					
				],
				'permission_callback' => function( $request ){
					return $this->viewing_not_allowed('contract');
				}
			],			
			[
				'methods'  => 'DELETE',
				'callback' => ['USAM_Documents_API', 'delete_contracts'],
				'args' => [					
					'args' => ['type' => 'object', 'required' => true],					
				],
				'permission_callback' => function( $request ){
					return $this->viewing_not_allowed('contract');
				}
			]
		));
		register_rest_route( $this->namespace, '/payments', array(		
			[ 
				'methods'  => 'GET,POST',
				'callback' => ['USAM_Documents_API', 'get_payments'],	
				'args' => [
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'status' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'status_type' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'add_fields' => ['type' => ['string','array'], 'required' => false],					
					'orderby' => ['type' => 'string', 'required' => false, 'default' => 'id'],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],
					'count' => ['type' => 'integer', 'required' => false], 
					'paged' => ['type' => 'integer', 'required' => false],
				],
				'permission_callback' => function( $request ){						
					return $this->viewing_not_allowed('payment');
				},
			],		
			[
				'methods'  => 'PUT',
				'callback' => ['USAM_Documents_API', 'save_payments'],
				'args' => [					
					'items' => ['type' => 'array', 'required' => true],					
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || is_user_logged_in();
				}
			],			
			[
				'methods'  => 'DELETE',
				'callback' => ['USAM_Documents_API', 'delete_payments'],
				'args' => [					
					'args' => ['type' => 'object', 'required' => true],					
				],
				'permission_callback' => function( $request ){
					return $this->viewing_not_allowed('shipped');
				}
			]
		));
		register_rest_route( $this->namespace, '/orders', array(		
			[ // получить список заказов
				'methods'  => 'GET,POST',
				'callback' => ['USAM_Documents_API', 'get_orders'],	
				'args' => [
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],					
					'user' => ['type' => 'string,integer,array', 'required' => false],
					'users' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
					'status_type' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],
					'status' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'status__not_in' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'bank_account_id' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
					'storage_pickup' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
					'document_discount' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
					'contacts' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
					'companies' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],
					'manager' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
					'seller' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
					'payer' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
					'payment' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
					'shipping' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
					'campaign' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
					'code_price' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],								
					'paid' => ['type' => 'integer', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],	
					'exchange' => ['type' => 'integer', 'required' => false, 'enum' => [0,1]],
					'fields' => ['type' => ['string','array'], 'required' => false],
					'add_fields' => ['type' => ['string','array'], 'required' => false],					
					'orderby' => ['type' => 'string', 'required' => false, 'default' => 'id'],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],
					'count' => ['type' => 'integer', 'required' => false], 
					'paged' => ['type' => 'integer', 'required' => false],
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || is_user_logged_in();
				},
			],		
			[ // Обновить заказы
				'methods'  => 'PUT',
				'callback' => ['USAM_Documents_API', 'save_orders'],
				'args' => [					
					'items' => ['type' => 'array', 'required' => true],					
				],
				'permission_callback' => function( $request ){
					return current_user_can('universam_api') || is_user_logged_in();
				}
			],			
		));	
		register_rest_route( $this->namespace, '/order/status/(?P<id>\S+)', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_Documents_API', 'get_status_order'],				
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],			
		]);	
		register_rest_route( $this->namespace, '/order/copy/(?P<id>\S+)', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_Documents_API', 'order_copy'],				
				'permission_callback' => function( $request ){	
					return current_user_can('universam_api') || is_user_logged_in();
				}
			],			
		]);			
		register_rest_route( $this->namespace, '/order/customer/load', [		
			[ // Получить
				'methods'  => 'GET',
				'callback' => ['USAM_Documents_API', 'get_customer_details'],				
				'args' => [					
					'customer_id' => ['type' => 'integer', 'required' => true, 'sanitize_callback' => 'absint'],				
					'customer_type' => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field'],							
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || current_user_can('edit_order');
				}
			],		
		]);	
		register_rest_route( $this->namespace, '/order/payment_number/(?P<number>\S+)', [		
			[
				'methods'  => 'GET',
				'callback' => ['USAM_Documents_API', 'get_order_by_payment_number'],	
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],				
		]);			
		register_rest_route( $this->namespace, '/units', [		
			[
				'methods'  => 'GET',
				'args' => [					
					'fields' => ['type' => 'string', 'required' => false],					
				],
				'callback' => ['USAM_Documents_API', 'get_list_units'],	
				'permission_callback' => ['USAM_Request_Processing', 'permission']
			],				
		]);	
		register_rest_route( $this->namespace, '/additional_agreements', array(		
			[ // получить список заказов
				'methods'  => 'GET,POST',
				'callback' => ['USAM_Documents_API', 'get_additional_agreements'],	
				'args' => [
					'meta_query' => ['type' => 'array', 'required' => false],	
					'meta_key' => ['type' => 'string', 'required' => false],
					'meta_value' => ['type' => ['string','integer'], 'required' => false],	
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],	
					'status' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'status__not_in' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'bank_account_id' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],						
					'exchange' => ['type' => 'integer', 'required' => false, 'enum' => [0,1]],
					'fields' => ['type' => ['string','array'], 'required' => false],
					'add_fields' => ['type' => ['string','array'], 'required' => false],					
					'orderby' => ['type' => 'string', 'required' => false, 'default' => 'id'],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],
					'count' => ['type' => 'integer', 'required' => false], 
					'paged' => ['type' => 'integer', 'required' => false],					
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || is_user_logged_in();
				},
			]		
		));	
		register_rest_route( $this->namespace, '/documents', array(		
			[ // получить список заказов
				'methods'  => 'GET,POST',
				'callback' => ['USAM_Documents_API', 'get_documents'],	
				'args' => [
					'meta_query' => ['type' => 'array', 'required' => false],	
					'meta_key' => ['type' => 'string', 'required' => false],
					'meta_value' => ['type' => ['string','integer'], 'required' => false],	
					'search' => ['type' => 'string', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_text']],	
					'status' => ['type' => 'string,array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'status__not_in' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_string']],
					'bank_account_id' => ['type' => 'array', 'required' => false, 'sanitize_callback' => ['USAM_Request_Processing', 'sanitize_array_number']],						
					'exchange' => ['type' => 'integer', 'required' => false, 'enum' => [0,1]],
					'fields' => ['type' => ['string','array'], 'required' => false],
					'add_fields' => ['type' => ['string','array'], 'required' => false],					
					'orderby' => ['type' => 'string', 'required' => false, 'default' => 'id'],
					'order' => ['type' => 'string', 'enum' => ['ASC','asc','DESC','desc'], 'default' => 'DESC', 'required' => false],
					'count' => ['type' => 'integer', 'required' => false], 
					'paged' => ['type' => 'integer', 'required' => false],					
				],
				'permission_callback' => function( $request ){						
					return current_user_can('universam_api') || is_user_logged_in();
				},
			]		
		));		
	}
}
?>