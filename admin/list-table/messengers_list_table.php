<?php
require_once(USAM_FILE_PATH.'/includes/feedback/social_network_profiles_query.class.php');
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_messengers extends USAM_List_Table
{	
	function column_name( $item )
	{	
		echo "<div class='user_block'>";
		echo "<div class='usam_foto image_container'>";
		echo "<img src='".esc_url( $item->photo )."' loading='lazy'>";
		echo "</div>";	
		echo "<div>";	
		$name = $this->item_edit( $item->id, $item->name, $item->type_social );
		$this->row_actions_table( $name, $this->standart_row_actions( $item->id, $item->type_social ) );	
		echo "</div></div>";		
	}	
	
	function column_channel( $item ) 
	{			
		switch ( $item->type_social ) 
		{		
			case 'vk_user':
			case 'vk_group':
				echo usam_get_icon( 'vk' );
			break;			
			case 'ok_user':
			case 'ok_group':
				echo usam_get_icon( 'ok' );
			break;
			default:
				echo usam_get_icon( $item->type_social );
			break;
		}		
	}	
		   
	function get_sortable_columns()
	{
		$sortable = array(
			'name'      => array('name', false),			
			'type'      => array('type', false),			
			);
		return $sortable;
	}		
		
	function get_columns()
	{
        $columns = array(           
			'cb'        => '<input type="checkbox" />',					
			'name'      => __('Название', 'usam'),		
			'code'      => __('Код', 'usam'),			
			'subscribers_count' => __('Количество подписчиков', 'usam'),			
			'channel'   => '',	
			
        );		
        return $columns;
    }	
	
	public function get_number_columns_sql()
    {       
		return array('sort' );
    }
	
	function prepare_items() 
	{	
		$this->get_query_vars();		
	
		$this->query_vars['type_social'] = ['viber', 'telegram', 'skype', 'facebook'];	
		$query = new USAM_Social_Network_Profiles_Query( $this->query_vars );	
		$this->items = $query->get_results();		
			
		$total_items = $query->get_total();
		$this->set_pagination_args(['total_items' => $total_items, 'per_page' => $this->per_page]);
	}
}
?>