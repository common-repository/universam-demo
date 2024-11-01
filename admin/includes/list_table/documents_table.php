<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_all_documents_list_table.class.php' );
require_once( USAM_FILE_PATH . '/includes/document/documents_query.class.php' );		
class USAM_Documents_Table extends USAM_Table_ALL_Documents 
{	
	protected $order = 'DESC';
	protected $orderby = 'date_insert';
	protected $document_type = [];
	protected $status = 'all';
	protected $manager_id = '';		
	
	function __construct( $args = [] )
	{		
		if ( $this->document_type )
			$statuses_type = $this->document_type;
		else
		{
			$details_documents = usam_get_details_documents( );
			$statuses_type = array_keys($details_documents);			
		}
		$statuses = usam_get_object_statuses(['type' => $statuses_type, 'cache_results' => true]);	
		foreach ( $statuses as $status )
		{		
			if ( isset($this->statuses[$status->internalname]) )
				$this->statuses[$status->internalname]->number += $status->number;
			else
				$this->statuses[$status->internalname] = $status;
		}
		$selected = $this->get_filter_value('manager');
		if ( $selected && $selected !== 'all' )
			$this->manager_id = array_map('intval', (array)$selected);		
		elseif ( ( !defined('DOING_AJAX') || !DOING_AJAX ) && $this->manager_id !== 'all' )
			$this->manager_id = get_current_user_id();
			
		parent::__construct( $args );
    }
		
	public function display_interface_filters(  ) 
	{
		$interface_filters = $this->get_class_interface_filters([$this->table, 'documents']); 		
		?>
		<div class='toolbar_filters' v-cloak>
			<?php 
			$filters = $this->get_filter_tablenav();
			$interface_filters->display( isset($filters['interval']) ); 
			?>
		</div>
		<?php	
	}
	
	function column_name( $item )
	{		
		if ( $item->name == '' )
			$name = "<a href='".esc_url( add_query_arg(['form' => 'view', 'form_name' => $item->type,'id' => $item->id], $this->url))."'>№ {$item->number}</a>";	
		else
			$name = "<a href='".esc_url( add_query_arg(['form' => 'view', 'form_name' => $item->type, 'id' => $item->id], $this->url) )."'>{$item->name}</a><br>№ {$item->number}";	
		$this->row_actions_table( $name, $this->get_row_actions( $item ) );	
	}
	
	function column_id( $item )
	{		
		echo "<a href='".usam_get_document_url( $item )."'>{$item->number}</a>";	
	}
	
	protected function get_row_actions( $item ) 
    { 				
		$actions = $this->standart_row_actions( $item->id, $item->type, ['copy' => __('Копировать', 'usam')] );
		if ( $item->status != 'draft' && !current_user_can('delete_any_'.$item->type) || !current_user_can('delete_'.$item->type) )
			unset($actions['delete']);
		if ( !current_user_can('add_'.$item->type) )
			unset($actions['copy']);
		if ( !current_user_can('edit_'.$item->type) )
			unset($actions['edit']);
		return $actions;
	}	
	
	public function extra_tablenav( $which ) 
	{
		if ( 'top' == $which )
		{		
			?>		
			<div class="alignleft actions">
				<div class="table_buttons actions">
					<?php 
					foreach ( $this->document_type as $type )
					{
						if ( current_user_can('print_'.$type) )
						{
							$this->print_button();
							break;
						}
					} 
					foreach ( $this->document_type as $type )
					{
						if ( current_user_can('export_'.$type) )
						{
							$this->excel_button();
							break;
						}
					}
					?>
				</div>
			</div>
			<?php
		}
	}
		
	function get_bulk_actions_display() 
	{	
		$actions = [
			'delete' => __('Удалить', 'usam'),			
		];		
		return $actions;
	}	
	
	function column_type( $item )
	{		
		$details_documents = usam_get_details_documents( );	
		if ( isset($details_documents[$item->type]) )
		{
			echo $details_documents[$item->type]['single_name'];
		}
	}

	function column_number( $item ) 
    { 
		$this->row_actions_table( '<a class="row-title" href="'.usam_get_document_url( $item ).'">'.$item->number.'</a>', $this->get_row_actions( $item ) );	
	}	
		
	function column_company( $item ) 
    {
		$bank_account = usam_get_bank_account( $item->bank_account_id );	
		if ( !empty($bank_account) )
		{
			$company = usam_get_company( $bank_account['company_id'] );	
			echo '<a href="'.usam_get_company_url( $bank_account['company_id'] ).'" target="_blank">'.$company['name'].'</a>';			
			$currency = usam_get_currency_sign( $bank_account['currency'] );
			echo '<p class="description_column">'.$bank_account['name']." ".$bank_account['number']." $currency</p>";
		}
	}	
		
	public function single_row( $item ) 
	{		
		echo '<tr id = "contact-'.$item->id.'" data-customer_id = "'.$item->id.'">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}	
	
	function column_totalprice( $item ) 
    {
		$time = time();
		?><a href="<?php echo usam_url_action('printed_form', ['form' => $item->type, 'id' => $item->id, 'time' => $time] ); ?>" target="_blank"><?php echo  usam_get_formatted_price( $item->totalprice, ['type_price' => $item->type_price]); ?></a><?php
		if ( !empty($item->closedate) )
		{
			?><div><strong><?php _e('Срок', 'usam'); ?>: </strong><?php echo usam_local_date( $item->closedate, get_option( 'date_format', 'Y/m/d' ) ) ?></div><?php
		}
	}
	
	function get_sortable_columns()
	{
		$sortable = [
			'number'      => array('number', false),
			'name'        => array('name', false),
			'manager'     => array('manager', false),		
			'status'      => array('status', false),		
			'totalprice'  => array('totalprice', false),	
			'closedate'   => array('closedate', false),	
			'company'  => array('bank_account_id', false),		
			'counterparty'  => array('customer_id', false),		
			'date'  => array('date_insert', false),				
		];
		return $sortable;
	}
		
	function get_vars_query_filter()
	{
		$selected = $this->get_filter_value('company_own');
		if ( $selected )
			$this->query_vars['bank_account'] = array_map('intval', (array)$selected);	
		if ( $this->manager_id !== 'all' )
			$this->query_vars['manager_id'] = $this->manager_id;	
		$selected = $this->get_filter_value('contacts');
		if ( $selected )
			$this->query_vars['contacts'] = array_map('intval', (array)$selected);
		$selected = $this->get_filter_value('companies');
		if ( $selected )
			$this->query_vars['companies'] = array_map('intval', (array)$selected);
		$selected = $this->get_filter_value( 'group' );
		if ( $selected )
			$this->query_vars['group'] = array_map('intval', (array)$selected);			
		$selected = $this->get_filter_value( 'document_types' );
		if ( $selected )
		{
			if ( is_string($this->query_vars['type']) )
				$this->query_vars['type'] = [ $this->query_vars['type'] ];
			$document_types = array_map('sanitize_title', (array)$selected);	
			foreach ( $document_types as $type )
			{
				if ( current_user_can('view_'.$type) )
					$this->query_vars['type'][] = $type;
			}
		}
		$this->get_digital_interval_for_query(['sum', 'number']);		
	}
	
	protected function document_viewing_allowed()
	{			
		$access_allowed = false;
		$conditions = ['relation' => 'OR'];
		if ( !$this->document_type )
			$this->document_type = array_keys(usam_get_details_documents());		
		foreach ( $this->document_type as $document_type )
		{			
			if ( $this->viewing_allowed($document_type) )
			{				
				$conditions[] = ['key' => 'type', 'value' => $document_type, 'compare' => '='];
				$access_allowed = true;
			}
		}
		if ( $access_allowed )
			$this->query_vars['conditions'] = [ $conditions ];
		return $access_allowed;
	}
		
	function prepare_items() 
	{					
		$this->get_query_vars();		
		if ( $this->document_viewing_allowed() )
		{
			if ( $this->status == 'all_in_work' )
			{
				$this->query_vars['status'] = [];				
				foreach ( $this->statuses as $key => $status )	
				{
					if ( !$status->close )
						$this->query_vars['status'][] = $key;
				}
			}
			elseif ( $this->status != 'all' )
				$this->query_vars['status'] = $this->status;				
			if ( empty($this->query_vars['include']) )
			{							
				$this->get_vars_query_filter();
			}				
			$this->query_vars['cache_bank_accounts'] = true;
			$this->query_vars['cache_meta'] = true;	
			
			$documents = new USAM_Documents_Query( $this->query_vars );		
			$this->items = $documents->get_results();		
			$total_items = $documents->get_total();
			$this->total_amount = $documents->get_total_amount();		
			$this->set_pagination_args( array( 'total_items' => $total_items, 'per_page' => $this->per_page ) );
		}
	}
}
?>