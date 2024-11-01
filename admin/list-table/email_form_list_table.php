<?php 
require_once( USAM_FILE_PATH . '/admin/includes/list_table/email_table.php' );
class USAM_List_Table_email_form extends USAM_Table_Email 
{	
    function get_bulk_actions_display() 
	{		
		$actions = array(
			'delete'       => __('Удалить', 'usam'),
			'read'         => __('Прочитано', 'usam'),
			'not_read'      => __('Не прочитано', 'usam'),
			'important'    => __('Важное', 'usam'),
			'not_important' => __('Не важное', 'usam'),		
		);
		return $actions;
	}	

	protected function column_cb( $item ) 
	{	
		echo '<div class="icons">';
		if ( $item->folder == 'outbox' )			
			echo '<span class="dashicons dashicons-lightbulb" title="'.__('Не отправлено', 'usam').'"></span>';
		else
		{
			if ( $item->type == 'sent_letter' )			
				echo '<span class="dashicons dashicons-undo" title="'.__('Отправлено', 'usam').'"></span>';
			else	
				echo '<span class="dashicons dashicons-redo" title="'.__('Входящие', 'usam').'"></span>';			
		}
		echo '</div>';
    }		
}