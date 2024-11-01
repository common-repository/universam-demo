<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );	
class USAM_Form_advertising_campaign extends USAM_Edit_Form
{
	protected function get_title_tab()
	{ 			
		if ( $this->id != null )
		{
			$title = sprintf( __('Изменить компанию &laquo;%s&raquo;','usam'), $this->data['title'] );
		}
		else
			$title = __('Добавить рекламную компанию', 'usam');	
		return $title;
	}	
	
	protected function get_data_tab(  )
	{	
		if ( $this->id != null )
			$this->data = usam_get_advertising_campaign( $this->id );
		else
			$this->data = array( 'title' => '', 'description' => '', 'code' => '', 'source' => '', 'medium' => '', 'term' => '', 'content' => '', 'redirect' => get_bloginfo('url') );
	}	

	protected function toolbar_buttons( ) 
	{ 
		if ( $this->id != null )
		{	
			?>
			<div class="action_buttons__button"><?php submit_button( __('Сохранить','usam'), 'button button-primary', 'save', false, array( 'id' => 'submit-save' ) ); ?></div>
			<div class="action_buttons__button"><a href="<?php echo add_query_arg(['form' => 'view']); ?>" class="button"><?php _e('Посмотреть','usam'); ?></a></div>			
			<?php
			$this->delete_button();	
		}
	}	
		
	public function display_settings() 
	{		
		$sources = usam_get_traffic_sources();
		?>
		<div class="edit_form">				
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='code_campaign'><?php esc_html_e( 'Код компании', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" name="code" id="code_campaign" value="<?php echo $this->data['code']; ?>">
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='source_campaign'><?php esc_html_e( 'Источник', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name="source" id='source_campaign'>							
						<?php
						foreach ( $sources as $source => $title ) 
						{
							?><option value='<?php echo $source; ?>' <?php selected($source, $this->data['source']); ?>><?php echo $title; ?></option><?php
						}
						?>
					</select>
				</div>
			</div>		
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='medium_campaign'><?php esc_html_e( 'Канал компании', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" name="medium" id="medium_campaign" value="<?php echo $this->data['medium']; ?>">
				</div>
			</div>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='redirect_campaign'><?php esc_html_e( 'Перенаправлять на ссылку', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" name="redirect" id="redirect_campaign" value="<?php echo $this->data['redirect']; ?>">
				</div>
			</div>	
		</div>			
	   <?php   
	}
	
	public function display_other_settings() 
	{				
		?>
		<div class="edit_form">				
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='medium_content'><?php esc_html_e( 'Содержание объявления', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" name="content" id="medium_content" value="<?php echo $this->data['content']; ?>">
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='medium_term'><?php esc_html_e( 'Ключевое слово', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" name="term" id="medium_term" value="<?php echo $this->data['term']; ?>">
				</div>
			</div>		
		</div>			
	   <?php   
	}

	function display_left()
	{					
		$this->titlediv( $this->data['title'] );		
		$this->add_box_description( $this->data['description'] );	
		usam_add_box( 'usam_settings', __('Настройки рекламной компании','usam'), array( $this, 'display_settings' ));	
		usam_add_box( 'usam_other_settings', __('Настройки отслеживания в других системах','usam'), array( $this, 'display_other_settings' ));	
    }
}
?>