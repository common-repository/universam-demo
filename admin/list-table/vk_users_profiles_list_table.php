<?php
require_once( USAM_FILE_PATH .'/admin/includes/list_table/social_network_table.php' );
class USAM_List_Table_vk_users_profiles extends USAM_List_Table_Social_Network
{	
	protected $type_social = 'vk_user';
	
	function column_title( $item ) 
    {
		$actions = $this->standart_row_actions( $item->id, 'vk_user' );
		$actions['see'] = '<a class="usam-see-link" href="https://m.vk.com/id'.$item->code.'">'.__('Посмотреть вКонтакте', 'usam').'</a>';		
		$this->row_actions_table( $item->name, $this->standart_row_actions( $item->id, 'vk_user' ) );	
	}
	
	function get_columns()
	{
        $columns = array(           
			'cb'         => '<input type="checkbox" />',	
			'image'      => '',			
			'title'      => __('Имя', 'usam'),
			'code'       => __('ID анкеты', 'usam'),			
			'birthday'   => __('Поздравлять с ДР', 'usam'),					
        );		
        return $columns;
    }
}
?>