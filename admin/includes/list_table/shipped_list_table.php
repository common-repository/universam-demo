<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_all_documents_list_table.class.php' );
require_once(USAM_FILE_PATH.'/includes/document/shipped_documents_query.class.php');
class USAM_Table_Shipped extends USAM_Table_ALL_Documents 
{	
	protected $storages = array( );
	protected $couriers = array( );
	function __construct( $args = array() )
	{	
		parent::__construct( $args );			
		$storages = usam_get_storages(['active' => 'all']);		
		foreach ( $storages as $storage )		
			$this->storages[$storage->id] = $storage;
			
		if ( usam_check_current_user_role( 'courier' ) )
		{
			$this->status = 'courier';
		}		
		$this->statuses = usam_get_object_statuses(['type' => 'shipped', 'cache_results' => true, 'fields' => 'code=>data']);
		$this->status = isset($_REQUEST['status']) && in_array($_REQUEST['status'], array_keys($this->statuses))?$_REQUEST['status']:$this->status;		
		$this->couriers = usam_get_contacts(['role__in' => array('courier'), 'source' => 'all']);
    }
	
	protected function get_filter_tablenav( ) 
	{
		return ['interval' => '', 'status' => $this->status];
	}
	
	// массовые действия 
	public function get_bulk_actions() 
	{		
		if ( ! $this->bulk_actions )
			return array();

		$actions = [
			'delete' => __('Удалить', 'usam'),	
		];
		if ( !current_user_can( 'delete_shipped' ) )
			unset($actions['delete']);
		return $actions;
	}		
	 
	function get_sortable_columns() 
	{
		$sortable = array(
			'id'       => array('id', false),
			'order_id' => array('order_id', false),
			'status'   => array('status', false),
			'track_id' => array('track_id', false),	
			'method'   => array('method', false),	
			'name_storage'   => array('storage', false),				
			'date'     => array('date', false),			
			);
		return $sortable;
	}
	
	public function column_price( $item ) 
	{
		if ( $item->price > 0 )
			echo usam_currency_display( $item->price );
	}
	
	function column_id( $item )
	{
		if ( current_user_can('edit_shipped') )
		{
			?><a href="<?php echo admin_url("admin.php?page=storage&tab=warehouse_documents&form=edit&form_name=shipped&id=".$item->id); ?>"><?php echo $item->number; ?></a><?php
		}
		else
			echo $item->number;
	}
			
	public function column_status( $item ) 
	{				
		if ( usam_check_object_is_completed( $item->status, 'shipped' ) )
			echo usam_get_object_status_name( $item->status, 'shipped' );
		else	
		{
			echo '<select class="js-shipped-document-status" data-id="'.$item->id.'">';
			foreach ( $this->statuses as $status ) 
			{											
				echo '<option value="'.esc_attr( $status->internalname ).'" '.selected($status->internalname, $item->status, false) . '>'.esc_html( $status->name ). '</option>';
			}
			echo '</select>';
		}
	}
	
	function column_courier( $item )
	{	
		if ( usam_check_object_is_completed( $item->status, 'shipped' ) )
		{
			$contact = usam_get_contact( $item->courier, 'user_id' );
			echo !empty($contact)?$contact['appeal']:'';
		}
		else	
		{		
			echo '<select class="js-shipped-document-courier" data-id="'.$item->id.'">';
			foreach ( $this->couriers as $contact ) 
			{											
				echo '<option value="'.esc_attr( $contact->user_id ).'" '.selected($contact->user_id, $item->courier, false) . '>'.esc_html( $contact->appeal ). '</option>';
			}
			echo '</select>';
		}
		$date_delivery = usam_get_shipped_document_metadata( $item->id, 'date_delivery' );
		if ( $date_delivery )
			echo "<div class='address'><strong>".__("Дата доставки","usam").":</strong> ".usam_local_date( $date_delivery ).'</div>';		
	}
	
	function column_name( $item )
	{
		echo $item->name;
		if ( !empty($this->storages[$item->storage_pickup]) )		
			echo "<div class='address'><strong>".__("Офис получения","usam").":</strong> ".$this->storages[$item->storage_pickup]->title.'</div>';
		else
		{
			if ( $item->track_id )
				echo "<div class='address'><strong>".__("Номер отслеживания","usam").":</strong> ".$item->track_id.'</div>';
		}			
	}
		
	function column_name_storage( $item )
	{
		if ( !empty($this->storages[$item->storage]) )
			echo $this->storages[$item->storage]->title;		
	}
		
	function column_storage_pickup( $item )
	{
		if ( !empty($this->storages[$item->storage_pickup]) )
			echo $this->storages[$item->storage_pickup]->title;
	}
	
	function column_order_id( $item )
	{		
		echo usam_get_link_order( $item->order_id );	
	}
	
	function column_document( $item )
	{		
		$number = usam_get_shipped_document_metadata($item->id, 'external_document');
		if ( $number )
		{
			$date = usam_get_shipped_document_metadata($item->id, 'external_document_date');
			echo "<div>№ $number</div>";	
			echo $date?'': usam_local_formatted_date( $date, get_option( 'date_format', 'Y/m/d' ) );
		}
	}
	
	function column_customer( $item )
	{	
		$property_types = usam_get_order_property_types( $item->order_id );
		if ( !empty($property_types['delivery_contact']) )
			echo '<a>'.$property_types['delivery_contact']['_name'].'</a>';
		if ( !empty($property_types['delivery_address']) )
			echo '<div class="address"><strong>'.__("Адрес","usam").':</strong> '.$property_types['delivery_address']['_name'].'</div>';
	}	
		
	public function prepare_items() 
	{
		$this->get_query_vars();
		if ( $this->viewing_allowed('shipped') )
		{
			if ( empty($this->query_vars['include']) )
			{				
				if ( $this->status == 'all_in_work' )	
				{
					$this->query_vars['status'] = [];				
					foreach ( $this->statuses as $code => $status )	
					{				
						if ( !$status->close )
							$this->query_vars['status'][] = $code;
					}
				}
				elseif ( $this->status != 'all' ) 
					$this->query_vars['status'] = $this->status;				
				else
					$this->query_vars['status'] = array_keys($this->statuses);				
				$selected = $this->get_filter_value('shipping');
				if ( $selected )
					$this->query_vars['method'] = array_map('intval', (array)$selected);
				else 
				{
					$selected = $this->get_filter_value('courier_delivery');
					if ( $selected )
						$this->query_vars['method'] = $selected;
				}
				$selected = $this->get_filter_value( 'storage' );
				if ( $selected )
					$this->query_vars['storage'] = array_map('intval', (array)$selected);	
				
				$selected = $this->get_filter_value( 'storage_pickup' );
				if ( $selected )
					$this->query_vars['storage_pickup'] = array_map('intval', (array)$selected);	
				
				$selected = $this->get_filter_value( 'export' );
				if ( $selected )
				{
					$selected = array_map('intval', (array)$selected);	
					foreach ( $selected as $result ) 
					{
						if ( $result == 0 )
							$this->query_vars['meta_query'] = ['relation' => 'OR', ['key' => 'exchange', 'compare' => "NOT EXISTS"], ['key' => 'exchange','value' => 0, 'compare' => '=']];		
						else
							$this->query_vars['meta_query'] = [['key' => 'exchange','value' => 1, 'compare' => '=']];		
					}
				}			
				$this->get_digital_interval_for_query( array('price' ) );
			}		
			$query = new USAM_Shippeds_Document_Query( $this->query_vars );
			$this->items = $query->get_results();	
			if ( $this->per_page )
			{
				$total_items = $query->get_total();	
				$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page, ) );
			}
		}
	}	
}