<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_location extends USAM_Edit_Form
{	
	protected function get_title_tab()
	{ 
		if ( $this->id != null )
			$title = sprintf( __('Редактировать местоположение &#171;%s&#187;','usam'), $this->data['name'] );
		else
			$title = __('Добавить местоположение', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{	
		if ( $this->id != null )
			$this->data = usam_get_location( $this->id );
		else		
			$this->data = array( 'name' => '', 'parent' => 0, 'code' => '', 'sort' => 100 );	
	}
	
	public function back_to_list()
	{
		?><a href="<?php echo admin_url("options-general.php?page=shop_settings&tab=location"); ?>" class="back_to_list"><?php esc_html_e( 'Вернуться в список', 'usam'); ?></a><?php
	}
	
	function display_left()
	{				
		$this->titlediv( $this->data['name'] );
		usam_add_box( 'usam_location', __('Настройки местоположения','usam'), array( $this, 'location_setting' ) );	
    }
	
	function location_setting()
	{								
		$KLADR = usam_get_location_metadata( $this->id, 'KLADR' );
		$FIAS = usam_get_location_metadata( $this->id, 'FIAS' );		
		$latitude = usam_get_location_metadata( $this->id, 'latitude' );
		$longitude = usam_get_location_metadata( $this->id, 'longitude' );
		$timezone = usam_get_location_metadata( $this->id, 'timezone' );		
		$index = usam_get_location_metadata( $this->id, 'index' );
		$delivery_services = usam_get_delivery_services(['fields' => 'handler', 'groupby' => 'handler']);
		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_code'><?php esc_html_e( 'Тип местоположения', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name="code" id="option_code">				
					<?php $type_location = usam_get_types_location(); 	
					foreach ( $type_location as $type )			
					{									
						?><option value="<?php echo $type->code; ?>" <?php selected( $this->data['code'], $type->code ); ?>><?php echo $type->name; ?></option><?php
					}									
					?>	
					</select>			
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='search_location_1'><?php esc_html_e( 'Родитель', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php 
					$t = new USAM_Autocomplete_Forms();		
					$t->get_form_position_location( $this->data['parent'], array( 'code' => 'all' ) );
					?>				
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_KLADR'><?php esc_html_e( 'Код КЛАДР', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_KLADR' name="metas[KLADR]" size = "100" maxlength = "100" value="<?php echo $KLADR; ?>" autocomplete="off">
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_FIAS'><?php esc_html_e( 'Код ФИАС', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_KLADR' name="metas[FIAS]" size = "100" maxlength = "100" value="<?php echo $FIAS; ?>" autocomplete="off">
				</div>
			</div>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_KLADR'><?php esc_html_e( 'Долгота', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_KLADR' name="metas[longitude]" size = "100" maxlength = "100" value="<?php echo $longitude; ?>" autocomplete="off">
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_KLADR'><?php esc_html_e( 'Широта', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_KLADR' name="metas[latitude]" size = "100" maxlength = "100" value="<?php echo $latitude; ?>" autocomplete="off">
				</div>
			</div>
			<?php if ( $this->data['code'] == 'city' ) { ?>	
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='option_timezone'><?php esc_html_e( 'Часовой пояс', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<input type="text" id='option_timezone' name="metas[timezone]" size = "100" maxlength = "100" value="<?php echo $timezone; ?>" autocomplete="off">
					</div>
				</div>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='option_index'><?php esc_html_e( 'Индекс', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option">
						<input type="text" id='option_index' name="metas[index]" size = "100" maxlength = "100" value="<?php echo $index; ?>" autocomplete="off">
					</div>
				</div>
				<?php 
			}
			$gateways = [];
			foreach (usam_get_data_integrations( 'shipping', ['location_code' => 'Location code'] ) as $key => $item)
			{
				if ( $item['location_code'] == 'Да' )
					$gateways[] = $key;
			}
			foreach ( $delivery_services as $handler )
			{
				if ( in_array($handler, $gateways) )
				{
					$code = usam_get_location_metadata( $this->id, $handler );
					?>	
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label for='option_<?php echo $handler; ?>'><?php printf( esc_html__('Код %s', 'usam'), $handler); ?>:</label></div>
						<div class ="edit_form__item_option">
							<input type="text" id='option_<?php echo $handler; ?>' name="metas[<?php echo $handler; ?>]" size = "100" maxlength = "100" value="<?php echo $code; ?>" autocomplete="off">
						</div>
					</div>
					<?php
				}			
			}
			?>	
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_sort'><?php esc_html_e( 'Сортировка', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id='option_sort' name="sort" size = "100" maxlength = "100" value="<?php echo $this->data['sort']; ?>" autocomplete="off">
				</div>
			</div>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_html_e( 'Полное имя', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php
						if ( $this->id != null )
						{
							echo usam_get_full_locations_name($this->id);	
						}
					?>
				</div>
			</div>
		</div>
		<?php		
		
    }
}
?>