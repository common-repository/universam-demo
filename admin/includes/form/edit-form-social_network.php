<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_Social_Network extends USAM_Edit_Form
{	
	protected function get_data_tab(  )
	{					
		if ( $this->id != null )
		{				
			$this->data = usam_get_social_network_profile( $this->id );
		}
		else
		{					
			$this->data = array('name' => '', 'photo' => '', 'code' => '', 'app_id' => '', 'uri' => '', 'contact_group' => '', 'birthday' => 0, 'access_token' => '', 'from_group' => 1, 'type_price' => '' );
		}
	}	
	
	public function display_settings( )
	{  				
		?>	
		<div class="edit_form" >
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_code'><?php esc_html_e( 'ID вашей группы', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input value = "<?php echo $this->data['code'] ?>" type='text' name='code' id = "option_code"/>
				</div>
			</div>			
		</div>
      <?php
	}      

    public function social_network_publish_settings( )
	{  		
		$publish_reviews = usam_get_social_network_profile_metadata( $this->id, 'publish_reviews' );
		$publish_product_day = usam_get_social_network_profile_metadata( $this->id, 'publish_product_day' );	
		?>	
		<div class="edit_form" >
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_html_e( 'Поздравлять с ДР', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="radio" value="1" name="birthday" id="birthday0" <?php checked($this->data['birthday'], 1 ) ?>>  
					<label for="birthday0"><?php esc_html_e( 'Да', 'usam'); ?></label> &nbsp;						
					<input type="radio" value="0" name="birthday" id="birthday1" <?php checked($this->data['birthday'], 0 ) ?>>
					<label for="birthday1"><?php esc_html_e( 'Нет', 'usam'); ?></label>		
					<p class="description"><?php esc_html_e( 'Публиковать поздравления с днем рождения участников группы.', 'usam'); ?></p>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_html_e( 'Публиковать товар дня', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="radio" value="1" name="metas[publish_product_day]" id="publish_product_day0" <?php checked($publish_product_day, 1 ) ?>>  
					<label for="publish_product_day0"><?php esc_html_e( 'Да', 'usam'); ?></label> &nbsp;						
					<input type="radio" value="0" name="metas[publish_product_day]" id="publish_product_day1" <?php checked($publish_product_day, 0 ) ?>>
					<label for="publish_product_day1"><?php esc_html_e( 'Нет', 'usam'); ?></label>		
					<p class="description"><?php esc_html_e( 'Публиковать товар товар дня.', 'usam'); ?></p>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_html_e( 'Публиковать отзывы', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="radio" value="1" name="metas[publish_reviews]" id="publish_reviews0" <?php checked($publish_reviews, 1 ) ?>>  
					<label for="group_autopublish0"><?php esc_html_e( 'Да', 'usam'); ?></label> &nbsp;						
					<input type="radio" value="0" name="metas[publish_reviews]" id="publish_reviews1" <?php checked($publish_reviews, 0 ) ?>>
					<label for="group_autopublish1"><?php esc_html_e( 'Нет', 'usam'); ?></label>	
					<p class="description"><?php esc_html_e( 'Публиковать отзывы вКонтакте, когда они будут опубликованы на вашем сайте.', 'usam'); ?></p>	
				</div>
			</div>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for="type_price_option"><?php esc_html_e( 'Цена для публикации', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select id='type_price_option' name='type_price'>
						<?php												
						$prices = usam_get_prices( );
						foreach ( $prices as $price ) 
						{
							?><option value='<?php echo $price['code']; ?>' <?php selected($this->data['type_price'], $price['code']); ?>><?php echo $price['code']." - ".$price['title']; ?></option><?php
						}
						?>
					</select>
				</div>
			</div>			
		</div>
      <?php
	}   
		
	public function crm_settings( )
	{  		
		$groups = usam_get_groups( array('type' => 'contact') ); 
		?>
		<div class="edit_form" >
			<select id='contact_group' name='contact_group'>
				<option value='0' <?php selected($this->data['contact_group'],0); ?>><?php esc_html_e( 'Не прикреплять', 'usam'); ?></option>
				<?php												
				foreach ($groups as $group)
				{
					?><option value='<?php echo $group->id; ?>' <?php selected($this->data['contact_group'], $group->id); ?>><?php echo $group->name; ?></option><?php
				}
				?>
			</select>
			<p class="description"><?php esc_html_e( 'Добавлять нового участника группу в контакте в выбранную группу в CRM.', 'usam'); ?></p>	
		</div>
		<?php
	}   
	
	public function displsy_connection( )
	{
		$contact_id = usam_get_contact_id();	
		printf( __('Для подключения отправьте %s', 'usam'),"user_id_{$contact_id}"); 
	}   
	
	function display_left()
	{	
		if ( $this->data['name'] )
		{
			?> 
			<div class="profile">
				<img class="profile__image" src="<?php echo $this->data['photo']; ?>">
				<div class="profile__title"><?php echo $this->data['name']; ?></div>
			</div>			
			<?php
		}
		usam_add_box( 'usam_settings', __('Параметры','usam'), array( $this, 'display_settings' ) );
		usam_add_box( 'usam_publish_settings', __('Параметры публикаций','usam'), array( $this, 'social_network_publish_settings' ) );
    }
	
	function display_right()
	{
		usam_add_box( 'usam_crm_settings', __('Добавление новых контактов','usam'), array( $this, 'crm_settings' ) );
		usam_add_box( 'usam_connection', __('Подключение','usam'), array( $this, 'displsy_connection' ) );
	}
}
?>