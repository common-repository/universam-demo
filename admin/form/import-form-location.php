<?php	
require_once( USAM_FILE_PATH .'/admin/includes/importer.class.php' );	
class USAM_Form_location extends USAM_Importer
{			
	protected $rule_type = 'location_import';
	protected $template = false;
	protected function get_columns()
	{
		return usam_get_columns_location_import();
	}	
	
	public function get_url()
	{
		return admin_url('admin.php?page=shop_settings&tab=directories&view=settings&table=location');
	}
	
	public function process_file()
	{ 		
		$source = ['file' => __("Из вашего файла", 'usam'), 'vk' => __("вКонтакте", 'usam')];
		?>
		<h3><?php _e('Выбор источника импорта' , 'usam'); ?></h3>
		<div class ="edit_form">	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_html_e( 'Источник местоположений', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php
					foreach ( $source as $key => $name ) 
					{ ?>
						<label>
							<input type="radio" value="<?php echo $key; ?>" v-model='source'/>	
							<?php echo $name; ?>
						</label>		
					<?php } ?>					
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='delete_all_existing'><?php esc_html_e( 'Удалить все существующие местоположения', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='checkbox' id ="delete_all_existing" v-model='rule.delete_existing' value="1"/>
				</div>
			</div>		
		<?php $this->file_selection(); 
			if ( false ) 
			{		
				$locations = array( 'RU' => __('Россия', 'usam'), 'BY' => __('Беларусь', 'usam'), 'KZ' => __('Казахстан', 'usam'), 'UA' => __('Украина', 'usam'),  );
				?>	
				<div class ="edit_form">	
					<?php			
					foreach ( $locations as $key => $name )
					{		
						?>	
						<div class ="edit_form__item">
							<div class ="edit_form__item_name"><label for='location_<?php echo $key; ?>'><?php echo $name; ?>:</label></div>
							<div class ="edit_form__item_option">
								<input type='checkbox' id ="location_<?php echo $key; ?>" name="locations" value="<?php echo $key; ?>"/>
							</div>
						</div>
					<?php			
					}	
					?>								
				</div>			
				<?php		
			}	
			?>		
		</div>	
		<div class='actions'>	
			<button class="button button-primary" @click="next_step"><?php esc_html_e( 'Продолжить', 'usam'); ?></button>
		</div>
		<?php 	
	}
		
	public function process_settings()
	{	
		$results = usam_vkontakte_send_request( 'database.getCountries', array('need_all' => 1, 'count' => 1000, 'lang' => 'ru'));			
		?>
		<div v-show="source=='vk'">				
			<h3><?php _e('Выбор стран' , 'usam'); ?></h3>			
			<div class='edit_form'>					
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='option_lang'><?php esc_html_e( 'Язык', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<select v-model='rule.lang' id='option_lang'>
							<option value='ru'><?php _e('русский','usam') ?></option>
							<option value='uk'><?php _e('украинский','usam') ?></option>
							<option value='be'><?php _e('белорусский','usam') ?></option>
							<option value='en'><?php _e('английский','usam') ?></option>
							<option value='es'><?php _e('испанский','usam') ?></option>
							<option value='fi'><?php _e('финский','usam') ?></option>			
							<option value='de'><?php _e('немецкий','usam') ?></option>	
							<option value='it'><?php _e('итальянский','usam') ?></option>								
						</select>
					</div>
				</div>
				<?php 
				if ( !empty($results['items']) )
				{
					foreach ( $results['items'] as $key => $value ) { ?>
						<div class ="edit_form__item">
							<div class="edit_form__item_name"><label for='countries_<?php echo $value['id']; ?>'><?php echo $value['title']; ?>:</label></div>
							<div class="edit_form__item_option">
								<input type="checkbox" id ="countries_<?php echo $value['id']; ?>" value="<?php echo $value['id']; ?>" v-model='rule.countries'/>
							</div>
						</div>
					<?php } ?>
				<?php } ?>
			</div>
			<div class='actions'>				
				<button class="button button-primary" @click="next_step"><?php esc_html_e( 'Импортировать', 'usam'); ?></button>
			</div>	
		</div>	
		<div v-show="source=='novaposhta'">	
			<h3><?php _e('Выбор стран' , 'usam'); ?></h3>			
			<div class='edit_form'>					
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='option_lang'><?php esc_html_e( 'Язык', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<select name='lang' id='option_lang'>
							<option value='ru'><?php _e('русский','usam') ?></option>
							<option value='uk'><?php _e('украинский','usam') ?></option>											
						</select>
					</div>
				</div>				
			</div>
			<div class='actions'>				
				<button class="button button-primary" @click="next_step"><?php esc_html_e( 'Импортировать', 'usam'); ?></button>
			</div>
		</div>
		<?php 
		parent::process_settings( );
	}
}
?>