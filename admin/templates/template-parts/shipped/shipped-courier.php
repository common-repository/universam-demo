<div class="edit_form">
	<div class ="edit_form__item">
		<div class ="edit_form__item_name"><label><?php esc_html_e( 'Курьер', 'usam'); ?>:</label></div>
		<div class ="edit_form__item_option">	
			<select v-model="data.courier" v-if="edit">
				<option value="0"><?php _e('Нет','usam'); ?></option>
				<option :value="key" v-for="(contact, key) in couriers" v-html="contact"></option>
			</select>
			<span v-else v-html="couriers[data.courier]"></span>
		</div>
	</div>
	<div class ="edit_form__item" v-if="data.courier">
		<div class ="edit_form__item_name"><label><?php esc_html_e( 'Указания курьеру', 'usam'); ?>:</label></div>
		<div class ="edit_form__item_option">
			<textarea rows="5" type="text" v-if="edit" v-model="data.note"></textarea>
			<span v-else v-html="data.note.replace(/\n/g,'<br>')"></span>
		</div>
	</div>
	<div class ="edit_form__item" v-if="storagePickup.delivery_option==0">
		<div class ="edit_form__item_name"><label><?php esc_html_e( 'Дата и время доставки', 'usam'); ?>:</label></div>
		<?php 
		if ( !usam_check_current_user_role( 'courier' ) )
		{
			?>
			<div class ="edit_form__item_option" v-if="edit">
				<datetime-picker v-model="data.date_delivery"/>
			</div>
			<div class ="edit_form__item_option" v-else>{{localDate(data.date_delivery,'<?php echo get_option('date_format', 'Y/m/j'); ?> H:i')}}</div>
			<?php 
		} 
		else
		{
			?><div class ="edit_form__item_option">{{localDate(data.date_delivery,'<?php echo get_option('date_format', 'Y/m/j'); ?> H:i')}}</div><?php 
		}
		?>
	</div>	
	<div class ="edit_form__item" v-if="data.courier && data.delivery_problem">
		<div class ="edit_form__item_name"><label><?php esc_html_e( 'Проблема с доставкой', 'usam'); ?>:</label></div>
		<div class ="edit_form__item_option">								
			<?php 
			if ( usam_check_current_user_role( 'courier' ) )
			{
				?>
				<select v-model="data.courier" v-if="edit">
					<option value="0"><?php _e('Нет','usam'); ?></option>
					<option :value="key" v-for="(problem, key) in delivery_problems" v-html="problem"></option>
				</select>
				<span v-else v-html="delivery_problems[data.courier]"></span>
				<?php 
			} 
			else
			{
				?><span v-html="delivery_problems[data.courier]"></span><?php 
			}
			?>	
		</div>				
	</div>	
</div>