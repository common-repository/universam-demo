<?php		
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_email_clearing extends USAM_Edit_Form
{		
	protected $vue = true;
	
	protected function get_title_tab()
	{ 	
		return __('Очистка почты', 'usam');	
	}
	
	protected function get_data_tab( ) 	
	{	
		$this->data = ['day' => 14];
	}	
	
	public function manual_clearing() 
	{		
		global $mailbox_id;		
		$user_id = get_current_user_id();
		$mailboxes = usam_get_mailboxes(['user_id' => $user_id]);
		?>		
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='clearing_day'><?php esc_html_e( 'Удалять старше', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='text' id='clearing_day' size='3' maxlength='3' v-model="day"/> <?php _e('дней', 'usam') ?>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Электронная почта', 'usam'); ?>:</div>
				<div class ="edit_form__item_option">
					<select v-model="mailbox">
						<?php 																
						foreach ( $mailboxes as $mailbox )
						{	
							?><option value='<?php echo $mailbox->id; ?>' <?php selected( $mailbox->id, $mailbox_id ) ?>><?php echo "$mailbox->name ($mailbox->email)"; ?></option>	<?php 
						}
						?>	
					</select>
				</div>
			</div>
		</div>
		<?php	
	
	}
	
	function display_left()
	{					
		usam_add_box( 'usam_manual_clearing', __('Фильтры для очистки','usam'), array( $this, 'manual_clearing' ) );					
	}
	
	protected function get_toolbar_buttons( ) 
	{
		return [
			['vue' => ['@click="clearing"'], 'name' => __('Очистить','usam'), 'display' => 'all', 'primary' => true], 				
		];
	}
}
?>