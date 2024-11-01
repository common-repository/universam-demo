<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Document_Status_Edit_Form extends USAM_Edit_Form
{	
	protected function get_data_tab(  )
	{	
		if ( $this->id != null )
			$this->data = usam_get_object_status( $this->id );
		else
			$this->data = ['id' => 0, 'internalname' => '', 'name' => '', 'type' => 'order', 'description' => '', 'short_name' => '', 'sort' => 100, 'visibility' => 1, 'pay' => '', 'close' => 0, 'color' => '', 'text_color' => '', 'active' => 1, 'subject_email' => '', 'email' => '', 'sms' => ''];			

		$anonymous_function = function() { 
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_style( 'wp-color-picker' );	
		}; 
		add_action('admin_footer', $anonymous_function );	
	}		

	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить статус &#171;%s&#187;','usam'), $this->data['name'] );
		else
			$title = __('Добавить статус', 'usam');	
		return $title;
	}	
	
	public function display_settings( )
	{
		?>	
		<div class="edit_form">			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='short_name'><?php esc_html_e( 'Множественное название', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id="short_name" name="short_name" maxlength='40' value="<?php echo $this->data['short_name']; ?>" autocomplete="off">
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='internalname'><?php esc_html_e( 'Код', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='internalname' name="internalname" value="<?php echo $this->data['internalname']; ?>" autocomplete="off">
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_sort'><?php esc_html_e( 'Сортировка', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_sort' name="sort" value="<?php echo $this->data['sort']; ?>" maxlength='3' autocomplete="off">
				</div>
			</div>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_color'><?php esc_html_e( 'Цвет фона', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_color' name="color" class="js-color background_color" size="7" maxlength="7" value="<?php echo $this->data['color']; ?>"/>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_color'><?php esc_html_e( 'Цвет текста', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_color' name="text_color" class="js-color background_color" size="7" maxlength="7" value="<?php echo $this->data['text_color']; ?>"/>
				</div>
			</div>
			<?php do_action( "usam_{$this->data['type']}_status_settings_edit_form", $this, $this->data ); ?>
		</div>		
		<?php
	} 
	
	public function message_status() 
	{		
		usam_list_order_shortcode();		
		//Это устарело используйте триггерные рассылки и автоматизацию
		?>		
		<div class="edit_form">				
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='status_subject'><?php esc_html_e( 'Тема письма', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id="status_subject" name="subject" value="<?php echo $this->data['subject_email']; ?>" autocomplete="off">
				</div>
			</div>
			<div class ="edit_form__item">
				<?php                  
					wp_editor(stripslashes(str_replace('\\&quot;','',$this->data['email'])),'status_email',array(
						'textarea_name' => 'email',
						'media_buttons' => false,
						'textarea_rows' => 10,
						'tinymce' => array( 'theme_advanced_buttons3' => 'invoicefields,checkoutformfields', 'remove_linebreaks' => false )
						)	
					);
				?>     
			</div>		
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='status_sms'><?php esc_html_e( 'СМС сообщение', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<textarea id="status_sms"  class="status_sms" rows="8" name="sms"><?php echo esc_textarea( $this->data['sms'] ); ?></textarea>
					<div id="characters" class="character"><?php echo mb_strlen( $this->data['sms'] ); ?></div>
				</div>
			</div>
		</div>
		<?php
	}
	
	function display_conditions()
	{
		$statuses = usam_get_array_metadata( $this->id, 'object_status', 'statuses' );
		$this->checklist_meta_boxs(['statuses' => $statuses]);
		?>	
		<div class="edit_form">			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_html_e( 'Отображение только если выбран', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<label><input type="radio" <?php checked($this->data['visibility'], 1); ?> name="visibility" value="1"> <?php _e( 'Да', 'usam'); ?></label>&nbsp;&nbsp;
					<label><input type="radio" <?php checked($this->data['visibility'], 0); ?>name="visibility" value="0"> <?php _e( 'Нет', 'usam'); ?></label>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_html_e( 'Конечный статус', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<label><input type="radio" <?php checked($this->data['close'], 1); ?> name="close" value="1"> <?php _e( 'Да', 'usam'); ?></label>&nbsp;&nbsp;
					<label><input type="radio" <?php checked($this->data['close'], 0); ?>name="close" value="0"> <?php _e( 'Нет', 'usam'); ?></label>
				</div>
			</div>
			<?php if ( $this->data['type'] == 'order' ) { ?>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_html_e( 'Возможность оплатить', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<label><input type="radio" <?php checked($this->data['pay'], 1); ?> name="pay" value="1"> <?php _e( 'Да', 'usam'); ?></label>&nbsp;&nbsp;
					<label><input type="radio" <?php checked($this->data['pay'], 0); ?>name="pay" value="0"> <?php _e( 'Нет', 'usam'); ?></label>
				</div>
			</div>	
			<?php } ?>			
		</div>
		<?php		
	}
	
	function display_right()
	{			
		$this->add_box_status_active( $this->data['active'] );	
    }
}
?>