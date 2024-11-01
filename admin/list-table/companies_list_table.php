<?php
require_once( USAM_FILE_PATH . '/admin/includes/list_table/company_table.php' );
class USAM_List_Table_companies extends USAM_Companies_Table
{			
	protected $order = 'DESC';	
	protected function get_filter_tablenav( ) 
	{			
		$filters = ['interval' => ''];		
		static $my_count = null;
		$user_id = get_current_user_id();
		if ( $my_count === null )
			$my_count = (int)usam_get_companies(['manager_id' => $user_id, 'fields' => 'id', 'number' => 1]);
		if ( $my_count )
			$filters['manager'] = $user_id;
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
			if ( current_user_can('delete_company') )
				echo '<option value="delete">' . __('Удалить', 'usam') . "</option>\n";
			if ( get_option('usam_website_type', 'store' ) == 'marketplace' )
				echo '<option value="seller">' . __('Добавить в Продавцы', 'usam') . "</option>\n";
			echo '<option value="email">'.__('Отправить сообщение', 'usam')."</option>\n";
			echo '<option value="list">'.__('Список рассылки', 'usam')."</option>\n";
			echo '<option value="delete_lists">'.__('Удалить из всех списков', 'usam')."</option>\n";					
			if ( current_user_can('edit_company') )
				echo '<option value="bulk_actions">'.__('Открыть массовые действия', 'usam')."</option>\n";			
		echo "</select>\n";

		submit_button( __('Apply' ), 'action', '', false, array( 'id' => "doaction$two" ) );
		echo "\n";
	}
	
	function column_name( $item ) 
    {			
		$thumbnail = usam_get_company_logo( $item->id );
		echo "<div class='user_block'>";
		echo "<a href='".usam_get_company_url( $item->id )."' class='image_container usam_foto'><img src='".esc_url( $thumbnail )."' loading='lazy'></a>";
		echo "<div>";		
		$name = '<a class="row-title js-object-value" href="'.usam_get_company_url( $item->id ).'">'.$item->name.'</a>';
		$name .= "<div class='item_labels'>";
		if ( $item->type != 'customer' ) 
		{
			$name .= "<span class='".($item->type=='own'?"item_status_notcomplete":"item_status_valid")." item_status'>".usam_get_name_type_company( $item->type )."</span>";
			$company = usam_shop_requisites();			
			if ( $company['id'] === $item->id )
				$name .= "&nbsp;<span class='item_status_valid item_status'>".__('Основная', 'usam')."</span>";		
		}
		else
		{
			ob_start();
			usam_display_status( $item->status, 'company' );
			$name .= ob_get_clean();	
		}
		$name .= "</div>";
		echo $name;			
		echo "</div>";		
		echo "</div>";		
	}	

	public function single_row( $item ) 
	{		
		echo '<tr id = "company-'.$item->id.'" data-customer_id = "'.$item->id.'">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}	 
	
	function get_columns()
	{		
        $columns = [
			'cb'             => '<input type="checkbox" />',			
			'name'           => __('Компания', 'usam'),			
			'affairs'        => __('Дела', 'usam')					
        ];		
		if ( current_user_can('sale') )
		{
			$columns['sum'] = __('Всего куплено', 'usam');
		}
		if (  $this->window == 'available' )
			$columns['manager'] = __('Ответственный', 'usam');
        return $columns;
    }
		
	function prepare_items() 
	{			
		if ( current_user_can('view_company') )
		{
			$this->get_query_vars();
			$this->query_vars['cache_case'] = true;
			$this->query_vars['cache_meta'] = true;
			$this->query_vars['cache_thumbnail'] = true;		
			$this->query_vars['cache_results'] = true;			
			$this->query_vars['search_columns'] = ['id', 'name','inn', 'email', 'phone', 'site', 'group', 'login'];			
			if ( empty($this->query_vars['include']) )
			{				
				$this->get_vars_query_filter();
				$user_id = get_current_user_id();
				if ( $this->window == 'my' )
					$this->query_vars['manager_id'] = $user_id;	
			} 		
			$query = new USAM_Companies_Query( $this->query_vars );
			$this->items = $query->get_results();		
			$this->total_items = $query->get_total();
			$this->set_pagination_args(['total_items' => $this->total_items, 'per_page' => $this->per_page]);
		}
	}
}
?>