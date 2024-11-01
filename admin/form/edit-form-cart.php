<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_cart extends USAM_Edit_Form
{	
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить корзину № %s', 'usam'), $this->id );
		else
			$title = __('Добавить корзину', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{	
		global $wpdb;
		if ( $this->id != null )
			$this->data = $wpdb->get_row( "SELECT * FROM ".USAM_TABLE_USERS_BASKET." WHERE id ='$this->id'", ARRAY_A );
		else	
			$this->data = [];
	}	
	
	function display_users_metabox()
	{		
		$this->display_user_block( $this->data['user_id'], 'user', __('Нет пользователя','usam') );
	}
		
	function display_left()
	{			
		$title = __('Личный кабинет','usam');
		$title_button = $this->data['contact_id']?__('Сменить','usam'):__('Выбрать','usam');		
		$title .= "<a href='' data-modal='select_user' data-screen='user' data-list='users'  class='js-modal'>$title_button</a>";	
		usam_add_box( 'usam_user', $title, array( $this, 'display_users_metabox' ) );	
    }	
}
?>