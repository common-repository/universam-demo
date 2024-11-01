<?php
require_once( USAM_FILE_PATH .'/admin/includes/list_table/contact_table.php' );
class USAM_List_Table_contacts extends USAM_Contacts_Table
{	
	protected $order = 'DESC';
	
	protected function get_filter_tablenav( ) 
	{
		$filters = ['interval' => ''];		
		if( !defined('DOING_AJAX') || !DOING_AJAX )
		{
			static $my_count = null;		
			$user_id = get_current_user_id();
			if ( $my_count === null )
				$my_count = (int)usam_get_contacts(['manager_id' => $user_id, 'fields' => 'id', 'number' => 1]);
			if ( $my_count )
				$filters['manager'] = $user_id;			
		}
		return $filters;
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
			if ( current_user_can('delete_contact') )
				echo '<option value="delete">' . __('Удалить', 'usam') . "</option>\n";
			echo '<option value="email">'.__('Отправить сообщение', 'usam')."</option>\n";			
			echo '<option value="list">'.__('Список рассылки', 'usam')."</option>\n";
			echo '<option value="delete_lists">'.__('Удалить из всех списков', 'usam')."</option>\n";			
			echo '<option value="employee">'.__('Перевести в сотрудники', 'usam')."</option>\n";				
			if ( current_user_can('edit_contact') )
			{
				echo '<option value="bulk_actions">'.__('Открыть массовые действия', 'usam')."</option>\n";	
				echo '<option value="sex">'.__('Определить пол', 'usam')."</option>\n";			
				echo '<option value="coordinates">'.__('Определить координаты', 'usam')."</option>\n";				
			}
		echo "</select>\n";
		submit_button( __('Apply' ), 'action', '', false, ['id' => "doaction$two"]);
		echo "\n";
	}
	
	function get_columns()
	{		
        $columns = [          
			'cb'             => '<input type="checkbox" />',
			'name'           => __('Контакт', 'usam'),			
			'affairs'        => __('Дела', 'usam'),			
        ];		
		if ( current_user_can('sale') )
		{
			$columns['sum'] = __('Всего куплено', 'usam');
		}
		$columns['online'] = __('Онлайн', 'usam');
		if (  $this->window != 'my' )
			$columns['manager'] = __('Ответственный', 'usam');		
		//$columns['date'] = __('Добавлен', 'usam');
        return $columns;
    }	
}
?>