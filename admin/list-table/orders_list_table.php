<?php
require_once( USAM_FILE_PATH .'/admin/includes/list_table/orders_table.php' );
class USAM_List_Table_Orders extends USAM_Table_Orders 
{			
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
		if ( empty($actions) )
			return;
		
		echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . __('Select bulk action' ) . '</label>';
		echo '<select name="action' . $two . '" id="bulk-action-selector-' . esc_attr( $which ) . "\">\n";
		echo '<option value="-1">' . __('Массовые действия', 'usam') . "</option>\n";		
		echo '<option value="email">'.__('Отправить сообщение', 'usam')."</option>\n";
		if ( current_user_can('edit_order') )
			echo '<option value="coordinates">'.__('Определить координаты', 'usam')."</option>\n";
		foreach ( $actions as $name => $title ) 
		{
			$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';
			echo "\t" . '<option value="' . $name . '"' . $class . '>' . $title . "</option>\n";
		}			
		if ( current_user_can('edit_order') )
			$groups = apply_filters('usam_bulk_actions_orders', [] );
		if ( !empty($groups) )
		{
			foreach ( $groups as $options )
			{		
				$group_name = false;
				foreach ( $options as $key => $option )
				{
					if ( $key === 'name' )
					{
						$group_name = true;
						echo '<optgroup label="' . $option . '">';	
					}
					else
						echo $option;			
				}
				if ( $group_name )
					echo '</optgroup>';	
			}
		}
		echo "</select>\n";

		submit_button( __('Apply' ), 'action', '', false, array( 'id' => "doaction$two" ) );
	}	
	
	protected function column_cb( $item ) 
	{			
		$checked = in_array($item->id, $this->records )?"checked='checked'":""; 
		echo "<input id='checkbox-".$item->id."' type='checkbox' name='cb[]' value='".$item->id."' ".$checked.">";
		if ( $item->source == 'vk' || $item->source == '1c' || $item->source == 'moysklad' )
			echo usam_system_svg_icon( $item->source, ['title' => usam_get_order_source_name($item->source)] );
    }
/* 
	Описание: Заголовок таблицы в журнале продаж
*/
	public function get_columns()
	{
		$columns = [
			'cb'       => '<input type="checkbox" />',
			'id'       => __('Номер', 'usam'),				
			'status'   => __('Статус', 'usam'),			
			'customer' => __('Клиент', 'usam'),	
			'last_comment'  => __('Последний комментарий', 'usam'),
			'shipping' => __('Доставка', 'usam'),				
			'payment'  => __('Способ оплаты', 'usam')		
		];
		if ( !current_user_can('edit_lead') && !current_user_can('delete_lead') )
			unset($columns['cb']);		
		if ( !usam_check_type_product_sold( 'product' ) )
			unset($columns['shipping']);	
		return $columns;
	}
}