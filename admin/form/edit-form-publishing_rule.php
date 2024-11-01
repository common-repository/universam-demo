<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_publishing_rule extends USAM_Edit_Form
{		
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить правило &laquo;%s&raquo;','usam'), $this->data['name'] );
		else
			$title = __('Добавить правило', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{							
		if ( $this->id != null )
		{				
			$this->data = usam_get_data( $this->id, 'usam_vk_publishing_rules' );
		}
		else
		{					
			$this->data = ['name' => '', 'campaign' => '', 'active' => 0, 'quantity' => 1, 'exclude' => 10, 'from_hour' => 7, 'to_hour' => 24, 'periodicity' => 4, 'quantity' => 1, 'start_date' => '', 'end_date' => '', 'pricemin' => 0, 'pricemax' => 0, 'minstock' => '', 'terms' => ['category' => [], 'brands' => [], 'category_sale' => []], 'vk_users' => [], 'vk_groups' => [], 'ok_groups' => []];
		} 
	}	
	
    public function display_settings( )
	{  				
		?>	
		<div class="edit_form" >			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='usam_date_picker-start'><?php esc_html_e( 'Интервал', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php usam_display_datetime_picker( 'start', $this->data['start_date'], 'hour' ); ?> - <?php usam_display_datetime_picker( 'end', $this->data['end_date'],  'hour' ); ?>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_html_e( 'Часы для публикации', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<span><?php esc_html_e( 'с', 'usam');  ?><span>
					<input type='text' class='text' size='10' maxlength='10' style='width:170px;' name='from_hour' value='<?php echo !empty($this->data['from_hour'])?$this->data['from_hour']:'';  ?>'>
					<span><?php esc_html_e( 'по', 'usam');  ?><span>
					<input type='text' class='text' size='10' maxlength='10' style='width:170px;' name='to_hour' value='<?php echo !empty($this->data['to_hour'])?$this->data['to_hour']:'';  ?>'>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_periodicity'><?php esc_html_e( 'Периодичность', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='text' id='option_periodicity' class='text' size='2' name='periodicity' value='<?php echo $this->data['periodicity'];  ?>'>	
					<p class ="description"><?php esc_html_e( 'Задайте час для повторения публикации', 'usam');  ?></p>	
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_exclude'><?php esc_html_e( 'Исключить', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='text' id='option_exclude' class='text' size='2' name='exclude' value='<?php echo $this->data['exclude'];  ?>'>	
					<p class ="description"><?php esc_html_e( 'Исключить опубликованные товары из повторной публикации на указанное количество дней.', 'usam');  ?></p>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_html_e( 'Диапазон цен', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<span><?php esc_html_e( 'От', 'usam');  ?><span>
					<input type='text' class='text' size='10' maxlength='10' style='width:170px;' name='pricemin' value='<?php echo number_format( $this->data['pricemin'], 2, '.', '' );  ?>'>
					<span><?php esc_html_e( 'До', 'usam');  ?><span>
					<input type='text' class='text' size='10' maxlength='10' style='width:170px;' name='pricemax' value='<?php echo number_format( $this->data['pricemax'], 2, '.', '' );  ?>'>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_minstock'><?php esc_html_e( 'Минимальный остаток', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type='text' for='option_minstock' class='text' size='10' name='minstock' value='<?php echo $this->data['minstock'];  ?>'>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_quantity'><?php esc_html_e( 'Товаров для публикации', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name='quantity' id='option_quantity'>
						<option <?php selected( $this->data['quantity'], 1 ); ?> value="1">1</option>
						<option <?php selected( $this->data['quantity'], 2 ); ?> value="2">2</option>
						<option <?php selected( $this->data['quantity'], 3 ); ?> value="3">3</option>
						<option <?php selected( $this->data['quantity'], 4 ); ?> value="4">4</option>
					</select>	
				</div>
			</div>
		</div>
      <?php
	}       
	
	public function terms_settings( ) 
	{		
		$this->checklist_meta_boxs( $this->data['terms'] ); 
	}
	
	protected function controller_get_condition_vk_users ( )
	{ 
		$results = usam_get_social_network_profiles( array( 'type_social' => 'vk_user', 'fields' => 'id=>name' ) );			
		return $results;
    }  
	
	protected function controller_get_condition_vk_groups( )
	{ 
		$results = usam_get_social_network_profiles( array( 'type_social' => 'vk_group', 'fields' => 'id=>name' ) );			
		return $results;
    }    
		
	public function vk_profiles( ) 
	{		
		$this->checklist_meta_boxs( array('vk_users' => $this->data['vk_users'],'vk_groups' => $this->data['vk_groups']));
	}
	
	protected function controller_get_condition_ok_groups( )
	{ 
		$results = usam_get_social_network_profiles( array( 'type_social' => 'ok_group', 'fields' => 'id=>name' ) );			
		return $results;
    }  
	
	public function ok_profiles( ) 
	{		
		$this->checklist_meta_boxs( array('ok_groups' => $this->data['ok_groups']));
	}
	
	public function advertising_campaigns( )
	{  				
		require_once( USAM_FILE_PATH . '/includes/analytics/advertising_campaigns_query.class.php' );
		$campaigns = usam_get_advertising_campaigns();
		?>	
		<select name='campaign'>
			<option <?php selected( $this->data['campaign'], '' ); ?> value=""><?php _e('Не выбрано', 'usam') ?></option>
			<?php 
			foreach ( $campaigns as $campaign ) 
			{
				?>	
				<option <?php selected( $this->data['campaign'], $campaign->id ); ?> value="<?php echo $campaign->id; ?>"><?php echo $campaign->title; ?></option>
				<?php 
			}
			?>	
		</select>	
      <?php
	}    
	
	function display_left()
	{				
		$this->titlediv( $this->data['name'] );
		usam_add_box( 'usam_settings', __('Параметры','usam'), array( $this, 'display_settings' ) );
		usam_add_box( 'usam_terms_settings', __('Группы товаров','usam'), array( $this, 'terms_settings' ) );
		usam_add_box( 'usam_vk_profiles', __('Профили пользователей и группы Вконтакте','usam'), array( $this, 'vk_profiles' ) );
		usam_add_box( 'usam_ok_profiles', __('Группы в Одноклассниках','usam'), array( $this, 'ok_profiles' ) );
	}
	
	function display_right()
	{			
		$this->add_box_status_active( $this->data['active'] );	
		usam_add_box( 'usam_advertising_campaigns', __('Рекламная компания','usam'), [$this, 'advertising_campaigns']);		
    }
}
?>