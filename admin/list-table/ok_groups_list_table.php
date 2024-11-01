<?php
require_once( USAM_FILE_PATH .'/admin/includes/list_table/social_network_table.php' );
class USAM_List_Table_ok_groups extends USAM_List_Table_Social_Network
{	
	protected $type_social = 'ok_group';
	
	function column_title( $item ) 
    {
		$actions = $this->standart_row_actions( $item->id, 'ok_group' );
		$actions['see'] = '<a class="usam-see-link" href="https://ok.ru/group/'.$item->code.'">'.__('Посмотреть в OK', 'usam').'</a>';		
		$this->row_actions_table( $item->name, $this->standart_row_actions( $item->id, 'ok_group' ) );	
	}
	
	function get_columns()
	{
        $columns = array(           
			'cb'         => '<input type="checkbox" />',	
			'image'      => '',			
			'title'      => __('Имя', 'usam'),
			'code'       => __('ID группы', 'usam'),			
			'birthday'   => __('Поздравлять с ДР', 'usam'),					
        );		
        return $columns;
    }
}
?>