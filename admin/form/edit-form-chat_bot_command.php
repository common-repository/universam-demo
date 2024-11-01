<?php		
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
require_once( USAM_FILE_PATH . '/includes/feedback/chat_bot_command.class.php' );
class USAM_Form_chat_bot_command extends USAM_Edit_Form
{
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = __('Изменить команду','usam');
		else
			$title = __('Добавить команду', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{					
		if ( $this->id !== null )
		{
			$this->data = usam_get_chat_bot_command( $this->id );
		}
		else	
		{
			$this->data = array( 'id' => 0, 'template_id' => absint($_REQUEST['n']), 'message' => '', 'time_delay' => 0, 'active' => 1 );
		}
		$this->url = add_query_arg( array('n' => $this->data['template_id'] ),$this->url );
	}	
	
	protected function get_url_go_back( ) 
	{
		$url = add_query_arg( array('n' => $this->data['template_id'] ) );
		return remove_query_arg( array( 'id', 'form', 'form_name' ), $url );		
	}
	
	private function output_row( $template = '' ) 
	{		
		?>
			<tr>
				<td><input type="text" name="templates[]" value="<?php echo $template; ?>" size="4" /></td>				
				<td class="column_actions">
					<?php 
					usam_system_svg_icon("plus", ["class" => "action add"]);
					usam_system_svg_icon("minus", ["class" => "action delete"]);
					?>
				</td>
			</tr>
		<?php
	}
	
	public function display_template( )
	{	
		$templates = usam_get_chat_bot_command_metadata( $this->id, 'templates' );
		?>	
		<div class="usam_table_container">
		<table class = "table_rate">
			<thead>
				<tr>
					<th><?php _e('Шаблоны для обработки сообщений клиентов', 'usam'); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>				
				<?php if ( !empty($templates) ): ?>
					<?php
						foreach( $templates as $id => $template )
							$this->output_row( $template );							
					?>
				<?php else: ?>
					<?php $this->output_row(); ?>
				<?php endif ?>
			</tbody>
		</table>
		</div>
      <?php
	}      

	function display_settings()
	{		
		?>
		<div class="edit_form">			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='message_chat_bot'><?php _e( 'Ответ на сообщение','usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<textarea name="message" id="message_chat_bot" style="height:200px;"><?php echo htmlspecialchars($this->data['message']); ?></textarea>	
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='time_delay_chat_bot'><?php esc_html_e( 'Задержка ответа в секундах', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='time_delay_chat_bot' name="time_delay" value="<?php echo $this->data['time_delay']; ?>">
				</div>
			</div>						
		</div>	
		<input type="hidden" name="n" value="<?php echo $this->data['template_id']; ?>">				
		<?php			
	}

	function display_left()
	{				
		usam_add_box( 'usam_template', __('Шаблон поиска','usam'), array( $this, 'display_template' ) );	
		usam_add_box( 'usam_settings', __('Настройки','usam'), array( $this, 'display_settings' ) );	
    }
	
	function display_right()
	{			
		$this->add_box_status_active( $this->data['active'] );					
    }
}
?>