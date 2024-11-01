<?php
require_once(USAM_FILE_PATH.'/includes/crm/bank_accounts_query.class.php');	
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_Table_ALL_Documents extends USAM_List_Table
{			
	protected $statuses = [];
	protected $all_in_work = true;		
		
	protected function get_filter_tablenav( ) 
	{
		return ['interval' => '', 'status' => $this->status, 'manager' => $this->manager_id];
	}
	
	public function extra_tablenav( $which ) 
	{
		if ( 'top' == $which && $this->filter_box ) 
		{
			$this->print_button();
		}
	}
	
	protected function bulk_actions( $which = '' ) 
	{ 	
		static $count = 0;
		$count++;		
		$actions = $this->get_bulk_actions();
		$actions = apply_filters( "bulk_actions-{$this->screen->id}", $actions );		
		if ( $count == 1 ) 
			$two = '';
		else 
			$two = '2';	
		
		echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . __('Select bulk action' ) . '</label>';
		echo '<select name="action' . $two . '" id="bulk-action-selector-' . esc_attr( $which ) . "\">\n";
		echo '<option value="-1">' . __('Массовые действия', 'usam') . "</option>\n";		
		foreach ( $actions as $name => $title ) 
		{
			$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';
			echo "\t" . '<option value="' . $name . '"' . $class . '>' . $title . "</option>\n";
		}	
		echo '<optgroup label="' . __('Статусы', 'usam') . '">';				
		foreach ( $this->statuses as $status )	
		{
			$style = $status->color != ''?'style="background:'.$status->color.'"':'';
			echo "\t" . '<option '.$style.' value="status-'.$status->id.'" class="status_document">'.$status->name."</option>\n";
		}		
		echo '</optgroup>';				
		echo "</select>\n";

		submit_button( __('Apply' ), 'action', '', false, array( 'id' => "doaction$two" ) );
	}	
	
	public function get_views() 
	{					
		$statuses = [];
		$total_count = 0;
		$all_in_work_total_count = 0;
	
		foreach ( $this->statuses as $status )	
		{ 	
			if ( $status->internalname != 'delete' )
			{
				$total_count += $status->number;	
				if ( !$status->close )
					$all_in_work_total_count += $status->number;	
			}
		}
		$all_text = sprintf(_nx( 'Всего <span class="count">(%s)</span>', 'Всего <span class="count">(%s)</span>', $total_count, 'purchase logs', 'usam'),	number_format_i18n( $total_count ) );		
		$all_class = ( $this->status == 'all' && empty($_REQUEST['m']) && empty($_REQUEST['s']) ) ? 'class="current"' : '';	
		$views = [ 'all' => sprintf('<a href="%s" %s>%s</a>', esc_url( add_query_arg( 'status', 'all', $this->url ) ), $all_class, $all_text ) ];	
		if ( $this->all_in_work )
		{
			$all_in_work = ( $this->status == 'all_in_work' && empty($_REQUEST['m']) && empty($_REQUEST['s']) ) ? 'class="current"' : '';
			$all_in_work_text = sprintf(_nx( 'Всего в работе <span class="count">(%s)</span>', 'Всего в работе <span class="count">(%s)</span>', $all_in_work_total_count, 'purchase logs', 'usam'),	number_format_i18n( $all_in_work_total_count ) );
			$views['all_in_work'] = sprintf('<a href="%s" %s>%s</a>', esc_url( add_query_arg( 'status', 'all_in_work', $this->url ) ), $all_in_work, $all_in_work_text );	
		}
		foreach ( $this->statuses as $status )
		{			
			if ( !$status->number )
				continue;		
			$text = $text = sprintf( $status->short_name.' <span class="count">(%s)</span>', number_format_i18n( $status->number )	);
			$href = add_query_arg( 'status', $status->internalname, $this->url );			
			$class = ( $this->status == $status->internalname ) ? 'class="current"' : '';
			$views[$status->internalname] = sprintf( '<a href="%s" %s>%s</a>', esc_url( $href ), $class, $text );			
		}
		return $views;
	}
	
	public function pagination( $which )
	{
		ob_start();
		parent::pagination( $which );
		$output = ob_get_clean();
		$string = _x( 'Всего: %s', 'Сумма', 'usam');
		$total_amount = ' - ' . sprintf( $string, usam_get_formatted_price( $this->total_amount ) );
		$total_amount = str_replace( '$', '\$', $total_amount );
		$output = preg_replace( '/(<span class="displaying-num">)([^<]+)(<\/span>)/', '${1}${2}'.' '.$total_amount.'${3}', $output );
		echo $output;
	}
	
	function column_status( $item ) 
	{		
		usam_display_status( $item->status, $item->type );
		if ( !empty($item->manager_id) )
		{
			$url = add_query_arg(['manager' => $item->manager_id, 'page' => $this->page, 'tab' => $this->tab], wp_get_referer() );	
			?> 
			<div><strong><?php _e('Ответственный','usam'); ?>:</strong> <a href="<?php echo $url; ?>"><?php echo usam_get_manager_name( $item->manager_id ); ?></a></div>	
			<?php	
		}
		else
		{
			?><div class="no_manager_assigned"><?php _e('Нет ответственного','usam'); ?></div><?php
		}
	}
	
	function column_manager( $item ) 
	{			
		if ( !empty($item->manager_id) )
		{
			$url = add_query_arg(['manager' => $item->manager_id, 'page' => $this->page, 'tab' => $this->tab], wp_get_referer() );	
			?> 
			<a href="<?php echo $url; ?>"><?php echo usam_get_manager_name( $item->manager_id ); ?></a>		
			<?php	
		}
		else
		{
			?><span class="no_manager_assigned"><?php _e('Нет ответственного','usam'); ?></span><?php
		}			
	}	
	
	protected function get_row_actions( $item ) 
    { 
		return [];
	}
		
	protected function viewing_allowed( $document_type )
	{	
		static $department_employees = null, $bank_accounts = null;
		$contact_id = usam_get_contact_id();
		$contact = usam_get_contact( $contact_id );
		$user_id = get_current_user_id();
								
		$conditions = [];
		$access_allowed = false;
		if ( current_user_can('any_view_'.$document_type) )
			$access_allowed = true;
		elseif ( current_user_can('view_'.$document_type) )
		{
			if ( $document_type == 'payment' || $document_type == 'shipped' )
				return true;
			$conditions[] = ['key' => 'manager_id', 'value' => [$user_id, 0], 'compare' => 'IN'];
			$access_allowed = true;
		}
		elseif ( current_user_can('company_view_'.$document_type) )
		{
			if ( $document_type == 'shipped' )
				return true;
			if ( !empty($contact['company_id']) )
			{
				if ( $bank_accounts === null )
				{
					$bank_accounts = usam_get_bank_accounts(['company_id' => $contact['company_id'], 'fields' => 'id']);	
					$bank_accounts[] = 0;
				}
				$conditions[] = ['key' => 'bank_account_id', 'value' => $bank_accounts, 'compare' => 'IN'];
				$access_allowed = true;
			}
		}
		elseif ( current_user_can('department_view_'.$document_type) )
		{
			if ( $document_type == 'payment' || $document_type == 'shipped' )
				return true;
			$department_id = usam_get_contact_metadata($contact_id, 'department');
			if ( $department_id )
			{ 
				if ( $department_employees === null )
				{
					$department_employees = usam_get_contacts(['meta_key' => 'department', 'source' => 'all', 'meta_value' => $department_id, 'fields' => 'user_id']);					
					$department_employees[] = 0;
				}
				$conditions[] = ['key' => 'manager_id', 'value' => $department_employees, 'compare' => 'IN'];
				$access_allowed = true;
			}
		}
		if ( $conditions )
			$this->query_vars['conditions'] = $conditions;			
		return $access_allowed;
	}
}
?>