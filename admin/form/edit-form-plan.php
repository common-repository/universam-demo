<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
require_once( USAM_FILE_PATH .'/includes/personnel/departments_query.class.php' );
require_once( USAM_FILE_PATH .'/includes/personnel/department.class.php' );
require_once( USAM_FILE_PATH .'/includes/personnel/sales_plan.class.php' );		
class USAM_Form_plan extends USAM_Edit_Form
{	
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = __('Изменить план','usam');
		else
			$title = __('Добавить план', 'usam');	
		return $title;
	}
	
	protected function form_attributes( )
    {
		?>v-cloak<?php
	}
	
	protected function get_data_tab(  )
	{				
		$default = ['id' => 0, 'period_type' => 'month', 'from_period' => date('Y-m-d'), 'plan_type' => 'department', 'target' => ''];	
		if ( $this->id != null )
		{							
			$this->data = usam_get_sales_plan($this->id);
		}
		$this->data = array_merge( $default, $this->data );	
	}	     
	
	function display_left()
	{				
		usam_add_box( 'usam_settings', __('Настройка плана', 'usam'), array( $this, 'settings_meta_box' ) );	
		usam_add_box( 'usam_people', __('Суммы плана', 'usam'), array( $this, 'plan_amounts_meta_box' ) );	
    }		
	
	function settings_meta_box() 
	{
		$types_period = usam_get_types_period_sales_plan( );
		$plan_types = usam_get_plan_types();
		?>				
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for="period_type"><?php esc_html_e( 'Тип периода', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select v-model="data.period_type" name="period_type" id="period_type">					
						<?php						
						foreach( $types_period as $id => $name )
						{						
							?><option value="<?php echo $id; ?>"><?php echo $name; ?></option><?php
						}		
						?>				
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for="period_type"><?php esc_html_e( 'Период', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name="month" id ="period_month" class ="from_period" v-if="data.period_type=='month'">					
						<?php	
						$month = date('n', strtotime($this->data['from_period']));	
						for ($i=1; $i<=12;$i++)
						{	
							?><option value="<?php echo $i; ?>" <?php selected($i, $month) ?>><?php echo date_i18n('F', strtotime(date('Y')."-".$i."-01 00:00:00")); ?></option><?php
						}		
						?>					
					</select>
					<select name="quarter" id ="period_quarter" class ="from_period" v-if="data.period_type=='quarter'">				
						<?php $quarter = intval(($month+2)/3); ?>
						<option value="1" <?php selected(1, $quarter) ?>><?php esc_html_e( 'I квартал', 'usam'); ?></option>
						<option value="2" <?php selected(2, $quarter) ?>><?php esc_html_e( 'II квартал', 'usam'); ?></option>		
						<option value="3" <?php selected(3, $quarter) ?>><?php esc_html_e( 'III квартал', 'usam'); ?></option>	
						<option value="4" <?php selected(4, $quarter) ?>><?php esc_html_e( 'IV квартал', 'usam'); ?></option>							
					</select>
					<select name="half-year" id ="period_half-year" class ="from_period" v-if="data.period_type=='half-year'">					
						<?php $half_year = $month<=6?1:2; ?>
						<option value="1" <?php selected(1, $half_year) ?>><?php esc_html_e( 'I полугодие', 'usam'); ?></option>
						<option value="2" <?php selected(2, $half_year) ?>><?php esc_html_e( 'II полугодие', 'usam'); ?></option>
					</select>
					<select name="year" id ="period_year">				
						<?php	
						$year = date('Y');
						$select_year = date('Y', strtotime($this->data['from_period']));						
						for ($i=$year; $i<$year+3;$i++)
						{	
							?><option value="<?php echo $i; ?>" <?php selected($i, $select_year) ?>><?php echo $i; ?></option><?php
						}
						if ( $year>$select_year )
						{
							?><option value="<?php echo $select_year; ?>" selected="selected"><?php echo $select_year; ?></option><?php
						}
						?>				
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for="period_type"><?php esc_html_e( 'Цель', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name="target" v-model="data.target" id="option_target">					
						<option value="sum"><?php esc_html_e( 'Сумма продаж', 'usam'); ?></option>
						<option value="quantity"><?php esc_html_e( 'Количество сделок', 'usam'); ?></option>
					</select>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for="period_type"><?php esc_html_e( 'Тип плана', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select v-model="data.plan_type" name="plan_type" id="option_plan_type">					
						<?php						
						foreach( $plan_types as $id => $name )
						{						
							?><option value="<?php echo $id; ?>"><?php echo $name; ?></option><?php
						}		
						?>				
					</select>
				</div>
			</div>
		</div>		
		<?php 
	}
			
	function plan_amounts_meta_box() 
	{	
		$amounts = usam_get_sales_plan_amounts( $this->id );
		$subordinates = usam_get_subordinates( null, 'all' );
		?>			
		<div id="people_amounts" class="plan_amounts" v-if="data.plan_type=='people'">
			<div class="edit_form">
				<?php 
				if ( !empty($subordinates) )
				{
					foreach( $subordinates as $contact ) 
					{ 			
						$amount = !empty($amounts[$contact->user_id])?$amounts[$contact->user_id]:'';
						?>	
						<div class ="edit_form__item">
							<div class ="edit_form__item_name"><label for="department_<?php echo $contact->user_id; ?>"><?php echo $contact->appeal; ?>:</label></div>
							<div class ="edit_form__item_option"><input type="text" name="people[<?php echo $contact->user_id; ?>]" id="department_<?php echo $contact->user_id; ?>" value="<?php echo $amount; ?>"/></div>
						</div>
						<?php 
					} 
				}
				else 
					_e("У вас нет подчиненных, создайте отдел, сделайте себя руководителем и выберете подчиненных.","usam");
				?>				
			</div>
		</div>	
		<div id="department_amounts" class="plan_amounts"  v-if="data.plan_type=='department'">
			<?php	
			$departments = usam_get_departments();
			if ( !empty($departments) )
			{
				?>				
				<div class="edit_form">
					<?php 
					foreach( $departments as $department ) 
					{ 
						$amount = !empty($amounts[$department->id]) && $this->data['plan_type']=='department' ?$amounts[$department->id]:'';
						?>	
						<div class ="edit_form__item">
							<div class ="edit_form__item_name"><label for="department_<?php echo $department->id; ?>"><?php echo $department->name; ?>:</label></div>
							<div class ="edit_form__item_option"><input type="text" name="department[<?php echo $department->id; ?>]" id="department_<?php echo $department->id; ?>" value="<?php echo $amount; ?>"/></div>
						</div>
						<?php 
					} ?>				
				</div>		
				<?php 
			}	
			else 
				_e("Нет отделов в вашей компании. Создайте отделы.","usam");
			?>	
		</div>		
		<div id="company_amounts" class="plan_amounts" v-if="data.plan_type=='company'">
			<?php
			$companies = usam_get_companies(['type' => 'own']);
			if ( !empty($companies) )
			{
				?>				
				<div class="edit_form">
					<?php
					foreach( $companies as $company ) 
					{ 
						$amount = !empty($amounts[$company->id]) && $this->data['plan_type']=='company' ?$amounts[$company->id]:'';						
						?>	
						<div class ="edit_form__item">
							<div class ="edit_form__item_name"><label for="department_<?php echo $company->id; ?>"><?php echo $company->name; ?>:</label></div>
							<div class ="edit_form__item_option"><input type="text" name="company[<?php echo $company->id; ?>]" id="department_<?php echo $company->id; ?>" value="<?php echo $amount; ?>"/></div>
						</div>
						<?php 
					} 
					?>				
				</div>		
				<?php 
			}
			else 
				_e("Нет ваших компаний. Заведите свои компании и укажите тип &laquo;своя&raquo;.","usam");
			?>	
		</div>
		<?php 
	}
}
?>