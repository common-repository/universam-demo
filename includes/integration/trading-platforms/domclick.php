<?php 
require_once( USAM_FILE_PATH . '/includes/exchange/trading_platforms.class.php' );
/*
  Name: ДомКлик
 */
class USAM_domclick_Exporter extends USAM_Trading_Platforms_Exporter
{			
	protected function get_export_product( $post ) 
	{							
		$result .= "<complex>
			<id>$post->ID</id>
			<name>".$this->get_product_title( $post )."</name>
			<latitude>".$this->get_product_attribute( 'latitude' )."</latitude>
			<longitude>".$this->get_product_attribute( 'longitude' )."</longitude>
			<address>".$this->get_product_attribute( 'address' )."</address> 			
			<images>";
				$attachments = usam_get_product_images( $post->ID );				
				foreach ($attachments as $attachment)
				{						
					$image = wp_get_attachment_image_src($attachment->ID, 'full');			
					$result .= "<image>".$image[0]."</image>";
				}
			$result .= "</images>
			<description_main>
				<title>".$this->get_product_attribute( 'description_main_title' )."</title>
				<text>".$this->get_product_attribute( 'description_main_text' )."</text>
			</description_main>
			<description_secondary> <!-- Второстепенное описание -->
				<title>".$this->get_product_attribute( 'description_secondary_title' )."</title>
				<text>".$this->get_product_attribute( 'description_secondary_text' )."</text>
			</description_secondary>
			<infrastructure>
				<parking>".$this->get_product_attribute( 'parking' )."</parking>
				<security>".$this->get_product_attribute( 'security' )."</security>
				<sports_ground>".$this->get_product_attribute( 'sports_ground' )."</sports_ground>
				<playground>".$this->get_product_attribute( 'playground' )."</playground> 
				<school>".$this->get_product_attribute( 'school' )."</school> 
				<kindergarten>".$this->get_product_attribute( 'kindergarten' )."</kindergarten>
				<fenced_area>".$this->get_product_attribute( 'fenced_area' )."</fenced_area>
			</infrastructure><videos>";					
			$videos = usam_get_product_video( $post->ID );
			foreach ($videos as $video)
			{	
				$result .= "<video><type>youtube</type><url>https://www.youtube.com/embed/".$video."</url></video>";
			}
			$result .= "</videos>";
			/*
			<profits_main>
				<profit_main> <!-- УТП -->
					<title>".$this->get_product_attribute( 'playground' )."</title> <!-- Заголовок -->
					<text>".$this->get_product_attribute( 'playground' )."</text> <!-- Описание -->
					<image></image> <!-- Ссылка на изображение -->
				</profit_main>
			</profits_main>
			<profits_secondary> <!-- Второстепенные уникальные торговые предложения ЖК -->
				<profit_secondary> <!-- УТП -->
					<title></title> <!-- Заголовок -->
					<text></text> <!-- Описание -->
					<image></image> <!-- Ссылка на изображение -->
				</profit_secondary>
			</profits_secondary>
			<buildings> <!-- Корпуса -->
				<building> <!-- Корпус -->
					<id></id> <!-- ID корпуса в Домклик -->
					<fz_214></fz_214> <!-- 214ФЗ -->
					<name></name> <!-- Название корпуса -->
					<floors></floors> <!-- Количество этажей -->
					<floors_ready></floors_ready> <!-- Количество построенных этажей -->
					<building_state></building_state> <!-- Статус стройки -->
					<built_year></built_year> <!-- Год сдачи -->
					<ready_quarter></ready_quarter> <!-- Квартал сдачи -->
					<building_phase></building_phase> <!-- Очередь строительства -->
					<building_type></building_type> <!-- Материал стен -->
					<image></image> <!-- Фото корпуса на карте ЖК -->
					<flats> <!-- Квартиры -->
						<flat> <!-- Квартира -->
							<flat_id></flat_id> <!-- ID квартиры -->
							<apartment></apartment> <!-- Технический номер квартиры -->
							<floor></floor> <!-- Этаж -->
							<room></room> <!-- Количество комнат -->
							<plan></plan> <!-- Изображение планировки -->
							<balcony></balcony> <!-- Наличие балкона -->
							<renovation></renovation> <!-- Отделка -->
							<price></price> <!-- Цена -->
							<area></area> <!-- Площадь -->
							<kitchen_area></kitchen_area> <!-- Площадь кухни -->
							<living_area></living_area> <!-- Жилая кухни -->
							<window_view></window_view> <!-- Окна -->
							<bathroom></bathroom> <!-- Санузел -->
							<rooms_area>
								<area></area> <!-- Площадь комнаты -->
							</rooms_area>
						</flat>
					</flats>
				</building>
			</buildings>";
			
			
			$result .= "<discounts>
				<discount> 
					<name></name> <!-- Название акции -->
					<description></description> <!-- Описание акции -->
					<image></image> <!-- Ссылка на изображение акции -->
					<site></site> <!-- Ссылка на страницу акции на сайте застройщика -->
				</discount>
			</discounts>
			<sales_info>";
			*/
			$data = usam_get_storage( $this->rule['office'] );				
			$result .= "<sales_phone>".$data['phone']."</sales_phone>
				<responsible_officer_phone>".$this->rule['responsible_officer_phone']."</responsible_officer_phone> 
				<sales_address>".$data['address']."</sales_address>
				<sales_latitude>".usam_get_storage_metadata( $this->rule['office'], 'latitude')."</sales_latitude> 
				<sales_longitude>".usam_get_storage_metadata( $this->rule['office'], 'longitude')."</sales_longitude>
				<timezone></timezone> 
				<work_days>
					<work_day> 
						<day></day>
						<open_at></open_at> 
						<close_at></close_at> 
					</work_day>
				</work_days>
			</sales_info>
			<developer>
				<id>".$this->rule['developer_id']."</id>
				<name>".$this->rule['developer_name']."</name>
				<phone>".$this->rule['developer_phone']."</phone> 
				<site>".$this->rule['developer_site']."</site> 
				<logo>".$this->rule['developer_logo']."</logo>
			</developer>
			</complex>";
		return $result;				
	}
	
	protected function get_export_file( $complex ) 
	{
		$requisites = usam_shop_requisites_shortcode();
	//	$categories = $this->list_categories();		
		
		$date = date_i18n( "Y-m-d H:i" );
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
				<complexes>'.$complex.'</complexes>';		
		return $xml;	
	}
	
	function get_form( ) 
	{		
		$developer_id = !empty($this->rule['developer_id'])?$this->rule['developer_id']:'';
		$developer_name = !empty($this->rule['developer_name'])?$this->rule['developer_name']:'';
		$developer_phone = !empty($this->rule['developer_phone'])?$this->rule['developer_phone']:'';
		$developer_site = !empty($this->rule['developer_site'])?$this->rule['developer_site']:'';
		$developer_logo = !empty($this->rule['developer_logo'])?$this->rule['developer_logo']:'';
		$responsible_officer_phone = !empty($this->rule['responsible_officer_phone'])?$this->rule['responsible_officer_phone']:'';	
		$office = !empty($this->rule['office'])?$this->rule['office']:'';		
		?>		
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><label for='developer_id'><?php esc_html_e( 'ID Застройщика в ДомКлик', 'usam'); ?>:</label></div>
			<div class ="edit_form__item_option">
				<input type="text" name="developer_id" id="developer_id"  value="<?php echo $developer_id; ?>"/>
			</div>
		</div>
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><label for='developer_name'><?php esc_html_e( 'Название застройщика или ГК', 'usam'); ?>:</label></div>
			<div class ="edit_form__item_option">
				<input type="text" name="developer_name" id="developer_name"  value="<?php echo $developer_name; ?>"/>
			</div>
		</div>
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><label for='developer_phone'><?php esc_html_e( 'Телефон застройщика, с указанием кода города', 'usam'); ?>:</label></div>
			<div class ="edit_form__item_option">
				<input type="text" name="developer_phone" id="developer_phone"  value="<?php echo $developer_phone; ?>"/>
			</div>
		</div>
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><label for='developer_site'><?php esc_html_e( 'Ссылка на сайт', 'usam'); ?>:</label></div>
			<div class ="edit_form__item_option">
				<input type="text" name="developer_site" id="developer_site"  value="<?php echo $developer_site; ?>"/>
			</div>
		</div>
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><label for='developer_logo'><?php esc_html_e( 'Ссылка на логотип', 'usam'); ?>:</label></div>
			<div class ="edit_form__item_option">
				<input type="text" name="developer_logo" id="developer_logo"  value="<?php echo $developer_logo; ?>"/>
			</div>
		</div>
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><label for='responsible_officer_phone'><?php esc_html_e( 'Мобильный телефон сотрудника отдела продаж, зарегистрированного в Партнер Онлайн', 'usam'); ?>:</label></div>
			<div class ="edit_form__item_option">
				<input type="text" name="responsible_officer_phone" id="responsible_officer_phone"  value="<?php echo $responsible_officer_phone; ?>"/>
			</div>
		</div>
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><label for='sales_phone'><?php esc_html_e( 'Офис продаж', 'usam'); ?>:</label></div>
			<div class ="edit_form__item_option">
				<?php usam_get_storage_dropdown( $office, array('name' => 'office' ) ); ?>
				<p class="description"><?php esc_html_e( 'В офисе продаж должен быть указан адрес, телефон, координаты', 'usam'); ?></p>
			</div>
		</div>
		<?php
	}	
	
	function save_form( ) 
	{		
		$new_rule['developer_id'] = !empty($_POST['developer_id'])?sanitize_text_field($_POST['developer_id']):'';
		$new_rule['developer_name'] = !empty($_POST['developer_name'])?sanitize_text_field($_POST['developer_name']):'';
		$new_rule['developer_phone'] = !empty($_POST['developer_phone'])?sanitize_text_field($_POST['developer_phone']):'';
		$new_rule['developer_site'] = !empty($_POST['developer_site'])?sanitize_text_field($_POST['developer_site']):'';
		$new_rule['developer_logo'] = !empty($_POST['developer_logo'])?sanitize_text_field($_POST['developer_logo']):'';
		$new_rule['responsible_officer_phone'] = !empty($_POST['responsible_officer_phone'])?sanitize_text_field($_POST['responsible_officer_phone']):'';
		$new_rule['office'] = !empty($_POST['office'])?sanitize_text_field($_POST['office']):'';
		return $new_rule;
	}
}
?>