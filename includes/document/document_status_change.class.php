<?php
class USAM_Document_Status_Change
{
	private $document;
	public function __construct( ) 
	{	
		add_action( 'usam_update_document_status', [$this, 'update_status'], 10, 4 );
		add_action( 'usam_insert_buyer_refund', [$this, 'insert_buyer_refund'], 10, 3 );
	}

	// Действие при обновлении статуса заказа в Журнале продаж. Обрабатывает статусы заказов при их получении. Уменьшает запасы на складе
	function update_status( $id, $status, $old_status, $document ) 
	{		
		$this->document = $document;				
		$type = $this->document->get( 'type' );			
		switch ( $type ) 
		{
			case 'movement' :
				$storage_id = usam_get_document_metadata($id, 'for_storage'); 
				if ( !empty($storage_id) )
				{		
					$add = $status == 'approved' ? true : false;		
					$this->products_stock_updates($storage_id, $add);
				}	
				$storage_id = usam_get_document_metadata($id, 'from_storage');
				if ( !empty($storage_id) )
				{		
					$add = $status == 'approved' ? false : true;					
					$this->products_stock_updates($storage_id, $add);
				}
				
			break;
			case 'receipt' :				
				$storage_id = usam_get_document_metadata($id, 'store_id');				
				if ( !empty($storage_id) )
				{		
					$add = $status == 'approved' ? true : false;
					$this->products_stock_updates($storage_id, $add);
				}					
			break;		
			case 'buyer_refund' :
				$storage_id = usam_get_document_metadata($id, 'store_id');				
				if ( !empty($storage_id) )
				{		
					$add = $status == 'approved' ? true : false;
					$this->products_stock_updates($storage_id, $add);
				}	
				if ( $status == 'approved' )
					$this->return_bonuses($document);
			break;			
		}		
	}

	public function insert_buyer_refund($document, $products, $metas)
	{	
		$this->return_bonuses( $document );
	}
	
	public function return_bonuses( $document )
	{		
		$bonuses = 0;
		$products = usam_get_products_document( $document['id'] );
		foreach ( $products as $product )
			$bonuses += $product->used_bonuses;	
		if ( $bonuses )
		{
			if ( $document['customer_type'] == 'company' )
				$customer = usam_get_company( $document['customer_id'] );
			else
				$customer = usam_get_contact( $document['customer_id'] );					
			if ( !empty($customer['user_id']) )
			{
				$parents = usam_get_parent_documents( $document['id'], $document['type'] );
				$order_id = 0;
				foreach ( $parents as $parent )
				{						
					if ( $parent->document_type == 'order' )
						$order_id = $parent->document_id;
				}
				usam_insert_bonus(['object_id' => $order_id, 'object_type' => 'order', 'sum' => $bonuses, 'description' => __('Списание бонусов при возврате товара','usam'), 'type_transaction' => 0], $customer['user_id'] );
			}
		}
	}
	
	public function products_stock_updates( $storage_id, $add )
	{		
		$products = usam_get_products_document( $this->document->get( 'id' ) );	
		usam_products_stock_updates($products, $storage_id, $add );
	}	
}
new USAM_Document_Status_Change();