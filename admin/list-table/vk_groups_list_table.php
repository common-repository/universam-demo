<?php
require_once( USAM_FILE_PATH .'/admin/includes/list_table/social_network_table.php' );
class USAM_List_Table_vk_groups extends USAM_List_Table_Social_Network
{	
	protected $type_social = 'vk_group';
	
	function column_title( $item ) 
    {
		$actions = $this->standart_row_actions( $item->id, 'vk_group' );
		$actions['see'] = '<a class="usam-see-link" href="https://m.vk.com/'.$item->code.'">'.__('Посмотреть вКонтакте', 'usam').'</a>';		
		$this->row_actions_table( $item->name, $this->standart_row_actions( $item->id, 'vk_group' ) );	
	}
	
	function get_columns()
	{
        $columns = array(           
			'cb'         => '<input type="checkbox" />',	
			'image'      => '',			
			'title'      => __('Название', 'usam'),
			'code'       => __('ID группы', 'usam'),			
			'birthday'   => __('Поздравлять с ДР', 'usam'),					
        );		
        return $columns;
    }
}
?>