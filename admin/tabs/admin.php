<?php
class USAM_Tab_Admin extends USAM_Tab
{	
	protected $views = ['simple'];
	public function display() 
	{		
		usam_add_box( 'usam_working_day', __('Рабочий день', 'usam'), array( $this, 'working_day' ) );		
		usam_add_box( 'usam_automation_working_day', __('Автоматизация рабочего дня', 'usam'), array( $this, 'automation_working_day' ) );		
	}	
	
	public function working_day() 
	{		
		$working_day = get_option('usam_working_day')
		?>		
		<div class='usam_setting_table edit_form'>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_time_start'><?php esc_html_e( 'Время рабочего дня', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php $times = array( '00:00', '00:30', '01:00', '01:30', '02:00', '02:30', '03:00', '03:30', '04:00', '04:30', '05:00', '05:30', '06:00', '06:30', '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30', '20:00', '20:30', '21:00', '21:30', '22:00', '22:30', '23:00', '23:30', ); ?>
					<select id='option_time_start' name="usam_options[working_day][time_start]">
						<?php									
						foreach ( $times as $time )
						{ 
							?><option value="<?php echo $time; ?>" <?php echo $working_day['time_start'] == $time ?'selected="selected"':''; ?>><?php echo $time; ?></option><?php									
						}
						?>
					</select> - 	
					<select name="usam_options[working_day][time_end]">
						<?php									
						foreach ( $times as $time )
						{ 
							?><option value="<?php echo $time; ?>" <?php echo $working_day['time_end'] == $time ?'selected="selected"':''; ?>><?php echo $time; ?></option><?php									
						}
						?>
					</select>						
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_priority'><?php esc_html_e( 'Дни недели', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php				
					$weekday = array( '1' => __('Понедельник','usam'), '2' => __('Вторник','usam'), '3' => __('Среда','usam'), '4' => __('Четверг','usam'), '5' => __('Пятница','usam'), '6' => __('Суббота','usam'), '0' => __('Воскресение','usam') );
					?>
					<div class="categorydiv">
						<div class="tabs-panel">
							<ul id="groups_checklist" class="categorychecklist form-no-clear">
								<?php echo usam_get_checklist( 'usam_options[working_day][days]', $weekday, $working_day['days'] ); ?>
							</ul>
						</div>							
					</div>					
				</div>
			</div>						
		</div>
		<?php		
	}
	
	public function automation_working_day() 
	{		
		$automation = get_option('usam_automation_working_day')
		?>		
		<div class='usam_setting_table edit_form'>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_priority'><?php esc_html_e( 'Запускать чат', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php				
					$args = array( 'orderby' => 'nicename', 'role__in' => array('shop_manager','administrator'), 'fields' => array( 'ID','display_name') );
					$users = get_users( $args );
					foreach( $users as $user )
					{						
						$consultants[$user->ID] = $user->display_name;
					}				
					?>	
					<div class="categorydiv">
						<div class="tabs-panel">
							<ul id="groups_checklist" class="categorychecklist form-no-clear">
								<?php echo usam_get_checklist( 'usam_options[automation_working_day][chat]', $consultants, $automation['chat'] ); ?>
							</ul>
						</div>							
					</div>	
				</div>
			</div>					
		</div>
		<?php		
	}
}
?>