<?php
require_once( USAM_FILE_PATH .'/admin/includes/list_table/contact_table.php' );
require_once( USAM_FILE_PATH .'/includes/personnel/department.class.php' );
require_once( USAM_FILE_PATH .'/includes/personnel/departments_query.class.php' );
class USAM_List_Table_employees extends USAM_Contacts_Table
{				
	protected function get_filter_tablenav( ) 
	{		
		return ['interval' => ''];
	}	
	
	protected function bulk_actions( $which = '' ) 
	{
		if ( !current_user_can('edit_employee') )
			return false;
		
		static $count = 0;
		$count++;		
		$actions = $this->get_bulk_actions();
		$actions = apply_filters( "bulk_actions-{$this->screen->id}", $actions );		
		if ( $count == 1 ) 
			$two = '';
		else 
			$two = '2';
		$types = usam_get_companies_types();		
		echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . __('Select bulk action' ) . '</label>';
		echo '<select name="action' . $two . '" id="bulk-action-selector-' . esc_attr( $which ) . "\">\n";
			echo '<option value="-1">' . __('Массовые действия', 'usam') . "</option>\n";			
			echo '<option value="delete">' . __('Удалить из персонала', 'usam') . "</option>\n";
			echo '<option value="process_webform">'.__('Обрабатывает веб-формы', 'usam')."</option>\n";	
			echo '<option value="not_process_webform">'.__('Не обрабатывает веб-формы', 'usam')."</option>\n";	
			echo '<option value="process_chat">'.__('Доступен в чате', 'usam')."</option>\n";	
			echo '<option value="not_process_chat">'.__('Не доступен в чате', 'usam')."</option>\n";				
			echo '<option value="bulk_actions">'.__('Открыть массовые действия', 'usam')."</option>\n";
		echo "</select>\n";
		submit_button( __('Apply' ), 'action', '', false, array( 'id' => "doaction$two" ) );
		echo "\n";
	}
	
	function column_name( $item ) 
    { 	
		echo "<div class='user_block'>";
		echo "<a href='".usam_get_employee_url( $item->id )."' class='image_container usam_foto'><img src='".esc_url( usam_get_contact_foto( $item->id ) )."' loading='lazy'></a>";	
		echo "<div>";
		$name = '<a class="row-title js-object-value" href="'.usam_get_employee_url( $item->id ).'">'.$item->appeal.'</a>';		
		$name .= "<div class='item_labels'>";
		ob_start();
		if ( $item->contact_source == 'employee' )
			usam_display_status( $item->status, 'employee' );
		else
			usam_display_status( $item->status, 'contact' );
		$name .= ob_get_clean();	
		$name .= "</div>";
		$this->row_actions_table( $name, $this->contact_standart_actions( $item ) );
		echo "<div>";
		echo "</div>";
	}
	
	function column_birthday( $item ) 
    {	
		$birthday = usam_get_contact_metadata($item->id, 'birthday');
		if ( $birthday )
		{
			$birthday_time = strtotime(get_date_from_gmt( $birthday));			
			$time = mktime(0, 0, 0, date('n', $birthday_time), date('j', $birthday_time), date('Y')) - time();
			$day = round($time/86400,0);
			if ( $day < 5 && $day > 0 )
				echo "<span class='item_status item_status_notcomplete'>".sprintf( _n( 'Через %d день', 'Через %d дня', $day, 'usam'), $day)." - ".usam_local_date( $birthday, 'd.m' )."</span>";			
			else if ( $day == 0 )
				echo "<span class='item_status item_status_valid'>".__( 'Сегодня', 'usam')."</span>";	
			else
				echo usam_local_date( $birthday, get_option( 'date_format', 'd.m.Y' ) );
		}
	}	
	
	function column_department( $item ) 
    {
		$department_id = usam_get_contact_metadata( $item->id, 'department' );
		if ( $department_id ) 
		{ 
				$department = usam_get_department( $department_id );
				if( $department )
					echo $department['name'];
		}
	}
			
	public function single_row( $item ) 
	{		
		echo '<tr id = "contact-'.$item->id.'" data-customer_id = "'.$item->id.'">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}
	
	function get_sortable_columns()
	{
		$sortable = array(
			'name'        => array('name', false),			
			'date'        => array('date_insert', false),		
			'department'  => array('department', false),			
			'online'      => array('online', false),				
		);
		return $sortable;
	}
	
	function get_columns()
	{		
        $columns = [    
			'cb'             => '<input type="checkbox" />',
			'name'           => __('Контакт', 'usam'),	
			'department'     => __('Отдел', 'usam'),
			'birthday'       => __('День рождения', 'usam'),			
			'online'         => __('Онлайн', 'usam')	
        ];	
        return $columns;
    }	
	
	function prepare_items() 
	{			
		$this->get_query_vars();			
		$this->query_vars['cache_meta'] = true;			
		$this->query_vars['cache_thumbnail'] = true;
		$this->query_vars['cache_department'] = true;		
		$this->query_vars['source'] = 'employee';
		$this->query_vars['meta_query'] = array();
		if ( $this->tab == 'companies' )
			$this->query_vars['company_id'] = $this->id;		
		if ( empty($this->query_vars['include']) )
		{		
			$this->get_vars_query_filter();
			$selected = $this->get_filter_value( 'chat' );
			if ( $selected )
				$this->query_vars['meta_query'][] = ['key' => 'online_consultant', 'compare' => '=', 'value' => (bool)$selected, 'relation' => 'AND'];				
			$selected = $this->get_filter_value( 'department' );
			if ( $selected )
			{
				$department = array_map('intval', (array)$selected);
				$this->query_vars['meta_query'][] = ['key' => 'department', 'compare' => 'IN', 'value' => $department, 'relation' => 'AND', 'type' => 'NUMERIC'];				
			}
		}	
		$_contacts = new USAM_Contacts_Query( $this->query_vars );
		$this->items = $_contacts->get_results();				
		$this->total_items = $_contacts->get_total();
		$this->set_pagination_args( array( 'total_items' => $this->total_items, 'per_page' => $this->per_page ) );
	}
}
?>