<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_vk_contest extends USAM_Edit_Form
{					                                                                            
    protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить конкурс &laquo;%s&raquo;','usam'), $this->data['title'] );
		else
			$title = __('Добавить конкурс', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{	
		if ( $this->id != null )
		{				
			$this->data = usam_get_data($this->id, 'usam_vk_contest');
		}
		else
		{
			$this->data = array( 'title' => '', 'message' => '', 'start_date' => '', 'end_date' => '', 'active' => 0,'in_group' => 0, 'winner_count' => 1, 'profile' => '', 'pin' => '' );
		} 				
	}
	
	public function box_in_group(  )
	{
        global $post;
        $checked = 'checked="checked"';           
          ?>				
		<div class="container_column">		
			<div class="column1" id="amtDescrip">
				<h4><?php _e('Должен ли участник состоять в группе?', 'usam')?></h4>
				<p><?php _e('Участник конкурса, совершивший репост, для победы может состоять в группе или не входить в нее.', 'usam')?>  </p>
			</div>
			<div class="column2" id="amtChoice">
				<h3><?php _e('Выберите вариант', 'usam')?></h3>
				<div id="amtRadio">
					<span id="amt-selected">
						<input type="radio" name="contest[in_group]" id = "in_group_0" class = "show_help" value="0" <?php if ( $this->data['in_group'] == 0 ) { echo "checked='checked'"; } ?>/><?php _e('Все учавствующие', 'usam'); ?><br />						
						<input type="radio" name="contest[in_group]" id = "in_group_1" class = "show_help" value="1" <?php if ( $this->data['in_group'] == 1 ) { echo "checked='checked'"; } ?> /><?php _e('Только состоящие в группе', 'usam'); ?><br />	
					</span>					
				</div>   
				<h3><?php _e('Количество победителей', 'usam')?></h3>
				<input type='text' value='<?php echo $this->data['winner_count']; ?>' name='contest[winner_count]'/>
			</div>			 
			<div class="column3">
				<div class="box_help_setting <?php if ( $this->data['in_group'] == 0 ) { echo "hidden"; } ?>" id="in_group_0">
					<h4><?php _e('Все совершившие репост', 'usam')?><span> - <?php _e('объяснение', 'usam')?></span></h4>
					<p><?php _e('Все совершившие репост и состоящие в группе', 'usam')?></p>
				</div>
				<div class="box_help_setting <?php if ( $this->data['in_group'] == 1 ) { echo "hidden"; } ?>" id="in_group_1">
					<h4><?php _e('Только состоящие в группе', 'usam')?><span> - <?php _e('объяснение', 'usam')?></span></h4>
					<p><?php _e('Победители будут выбранны только из состоящих в группе', 'usam') ?></p>
				</div>		
			</div>
		</div>	
      <?php
	}      

	function box_options( )
	{					
		?>		
		<div class="edit_form" >
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='group_page_id'><?php esc_html_e( 'Интервал', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php usam_display_datetime_picker( 'start', $this->data['start_date'] ); ?> - <?php usam_display_datetime_picker( 'end', $this->data['end_date'] ); ?>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='vk-profile'><?php esc_html_e( 'Анкета', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select id="vk-profile" name="contest[profile]">				
						<?php					
						$groups = usam_get_social_network_profiles( array( 'type_social' => 'vk_group' ) );
						foreach( $groups as $key => $profile )
						{	
							?>
							<option value="<?php echo $profile->id; ?>" <?php selected( $this->data['profile'], $profile->id); ?>><?php echo $profile->name; ?></option>
							<?php
						}
						?>
					</select>	
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='contest_pin1'><?php esc_html_e( 'Закрепить конкурс', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='radio' id = "contest_pin1" value='1' name='contest[pin]' <?php echo $this->data['pin'] == 1 ? 'checked="checked"':''; ?> /> <label for='contest_pin1'><?php _e( 'Да', 'usam');  ?></label> &nbsp;
					<input type='radio' id = "contest_pin2" value='0' name='contest[pin]' <?php echo $this->data['pin'] == 0 ? 'checked="checked"':''; ?> /> <label for='contest_pin2'><?php _e( 'Нет', 'usam');  ?></label>
				</div>
			</div>
		</div>			
		<?php
	}		
  	
	function display_left()
	{		
		$this->titlediv( $this->data['title'] );
		$this->add_box_description( $this->data['message'], 'message' );			
		usam_add_box( 'usam_in_group', __('Выбор победителя','usam'), array( $this, 'box_in_group' ));	
		usam_add_box( 'usam_main_options', __('Основные настройки','usam'), array( $this, 'box_options' ) );				
    }
	
	function display_right()
	{			
		$this->add_box_status_active( $this->data['active'] );		
    }
}
?>