<?php 
require_once( USAM_FILE_PATH . '/includes/exchange/trading_platforms.class.php' );
/*
  Name: Авито
  Gategory: marketplaces
  Icon: avito
 */
class USAM_Avito_Exporter extends USAM_Trading_Platforms_Exporter
{		
	protected $contact_method = [0 => 'По телефону и в сообщениях', 1 => 'По телефону', 2 => 'В сообщениях'];
	
	protected function get_default_option( ) 
	{
		return ['contact_method' => 0, 'avito_group' => 0, 'avito_category' => '', 'avito_product_type' => '','address' => '', 'product_options' => ''];		
	}
	
	protected function get_export_product( $post ) 
	{	
		$price = usam_get_product_price( $post->ID, $this->rule['type_price'] );
		if ( $price <= 0 )
			return '';		
		ob_start();	
		?>
		<Ad>
			<Id><?php echo $post->ID; ?></Id>		
			<?php
			$avito_id = usam_get_product_meta( $post->ID, 'avito_id' ); 
			if ( $avito_id )
			{
				?><AvitoId><?php echo $post->ID; ?></AvitoId><?php			
			} ?>
			<?php if ( $this->rule['start_date'] ) { ?>
				<DateBegin><?php echo date('Y-m-d\TH:i:sO', strtotime($this->rule['start_date'])); ?></DateBegin>
			<?php } ?>
			<?php if ( $this->rule['end_date'] ) { ?>
				<DateEnd><?php echo date('Y-m-d\TH:i:sO', strtotime($this->rule['end_date'])); ?></DateEnd>		
			<?php } ?>
			<?php if ( $this->rule['phone'] ) { ?>
				<ContactPhone>+<?php echo usam_phone_format( $this->rule['phone'], '7 (999) 999 99 99' ); ?></ContactPhone>			
			<?php } ?>
			<Address><?php  echo $this->rule['address'] ?></Address>
			<AdType>Товар приобретен на продажу</AdType>	
			<AdStatus><?php  echo $this->rule['ad_status'] ?></AdStatus>	
			<ContactMethod><?php echo $this->contact_method[$this->rule['contact_method']]; ?></ContactMethod>		
			<?php if ( !empty($this->rule['manager']) ) { ?>
				<ManagerName><?php echo $this->rule['manager']; ?></ManagerName>		
			<?php } ?>	
			<Category><?php echo $this->rule['avito_category']; ?></Category>
			<?php if ( !empty($this->rule['avito_product_type']) ) { ?>
				<GoodsType><?php echo $this->rule['avito_product_type']; ?></GoodsType>
			<?php } ?>	
			<?php if ( usam_product_has_stock( $post->ID ) ) { ?>
				<Availability>В наличии</Availability>
			<?php } ?>				
			<Title><?php echo $this->get_product_title( $post ); ?></Title>
			<?php 
			$product_description = !empty($this->rule['product_description']) ? usam_get_product_attribute_display( $post->ID, $this->rule['product_description'] ) : $post->post_excerpt;
			?>
			<Description><![CDATA[<?php echo $this->text_decode($product_description); ?>]]></Description>
			<Price><?php echo $price; ?></Price>
			<?php
			$condition = usam_get_product_attribute_display( $post->ID, 'condition' );
			if ( $condition == 'new' )
				$condition = 'Новое';
			elseif ( $condition == 'used' )
				$condition = 'Б/у';
			elseif ( $condition != 'Новое' && $condition != 'Б/у' )
				$condition = 'Новое';
			?>
			<Condition><?php echo $condition; ?></Condition>			
			<Images><?php
				$urls = usam_get_product_images_urls( $post->ID );
				foreach ($urls as $url)
				{						
					?><Image url="<?php echo $url; ?>"/><?php
				}
				?>
			</Images>			
			<?php 
			$options = usam_get_product_attribute_display( $post->ID, $this->rule['product_options'], false );
			if ( $options )
			{
				?>
				<AdditionalOptions>
					<Option><?php echo implode('</Option><Option>', $options); ?></Option>
				</AdditionalOptions>
				<?php
			}
			$videos = usam_get_product_video( $post->ID );
			if ( $videos )
			{
				?><VideoURL>https://www.youtube.com/embed/<?php echo $videos[0]; ?></VideoURL><?php 
			} 
			?>	
		</Ad>
		<?php 	
		return ob_get_clean();				
	}
	
	protected function get_export_file( $xml_product ) 
	{
		$html  = '<Ads formatVersion="3" target="Avito.ru">';
		$html  .= $xml_product;
		$html  .= '</Ads>';
		return 	$html;
	}
	
	public function get_js_form_data( ) 
	{
		$data = [];
		$data['category_list'] = [
			['name' => 'Готовый бизнес и оборудование',
				'sub' => [
					['name' => 'Оборудование для бизнеса', 'id' => 1196107, 'types' => ['Промышленное','Логистика и склад','Для магазина','Для ресторана','Для салона красоты','Для автобизнеса','Расчётно-кассовое','Рекламное','Развлекательное','Ресепшены и офисная мебель','Майнинг','Киоски и передвижные сооружения','Киоски и передвижные сооружения','Лабораторное','Медицинское','Телекоммуникационное', 'Другое']], 
					['name' => 'Готовый бизнес', 'id' => 1196114, 'types' => ['Интернет-магазины и IT','Общественное питание','Производство','Развлечения','Сельское хозяйство','Строительство','Сфера услуг','Магазины и пункты выдачи заказов','Автобизнес','Красота и уход','Стоматология и медицина','Туризм','Другое']] 
				]
			],
		/*	['name' => 'Услуги', 
				'sub' => [['name' => 'Предложения услуг',
					'sub' => [
						['name' => 'Другое', 'id' => 1196127], 
						['name' => 'Мастер на час', 'id' => 1196128], 
						['name' => 'Искусство', 'id' => 1196129], 
						['name' => 'Уход за животными', 'id' => 1196130], 
						['name' => 'Транспорт, перевозки', 'sub' => [
							['name' => 'Автосервис', 'id' => 1196132], ['name' => 'Спецтехника', 'id' => 1196133], ['name' => 'Переезды', 'id' => 1196134], ['name' => 'Грузчики', 'id' => 1196135], ['name' => 'Коммерческие перевозки', 'id' => 1196136], ['name' => 'Аренда авто', 'id' => 1196137]
						]], 
						['name' => 'Сад, благоустройство', 'id' => 1196138],
						['name' => 'Ремонт, строительство', 'sub' => [
							['name' => 'Строительство бань, саун', 'id' => 1196140],
							['name' => 'Ремонт квартир и домов под ключ', 'id' => 1196142], 
							['name' => 'Строительство домов, коттеджей', 'id' => 1196143], 
							['name' => 'Ремонт офиса', 'id' => 1196144], 
							['name' => 'Сантехника', 'id' => 1196145], 
							['name' => 'Электрика', 'id' => 1196146], 
							['name' => 'Сборка и ремонт мебели', 'id' => 1196147], 
							['name' => 'Отделочные работы', 'id' => 1196148], 
							['name' => 'Остекление балконов', 'id' => 1196150], 
						]],
						['name' => 'Реклама, полиграфия', 'id' => 1196151], 	
						['name' => 'Праздники, мероприятия', 'id' => 1196154], 			
						['name' => 'Охрана, безопасность', 'id' => 1196155], 	
						['name' => 'Питание, кейтеринг', 'id' => 1196156], 
						['name' => 'Оборудование, производство', 'sub' => [
							['name' => 'Аренда оборудования', 'id' => 1196160], 
							['name' => 'Монтаж и обслуживание оборудования', 'id' => 1196161], 
							['name' => 'Производство, обработка', 'id' => 1196162]
						]],
						['name' => 'Деловые услуги', 'id' => 1196163], 
						['name' => 'Бытовые услуги', 'id' => 1196169], 
						['name' => 'Красота, здоровье', 'sub' => [
							['name' => 'Тату, пирсинг', 'id' => 1196176],
							['name' => 'Косметология, эпиляция', 'id' => 1196177],
							['name' => 'Другое', 'id' => 1196178],
							['name' => 'Услуги парикмахера', 'id' => 1196179],
							['name' => 'Макияж', 'id' => 1196180],
							['name' => 'СПА-услуги, здоровье', 'id' => 1196181],
							['name' => 'Маникюр, педикюр', 'id' => 1196182],
						]],
						['name' => 'IT, интернет, телеком', 'id' => 1196183],
						['name' => 'Обучение, курсы', 'id' => 1196188],
						['name' => 'Няни, сиделки', 'id' => 1196198],
						['name' => 'Курьерские поручения', 'id' => 1196199],
						['name' => 'Установка техники', 'id' => 1196200],
						['name' => 'Уборка', 'id' => 1196201],
						['name' => 'Ремонт и обслуживание техники', 'sub' => []], // пропустил
						['name' => 'Фото- и видеосъёмка', 'id' => 1196218],
					]]
				]
			], 
			['name' => 'Животные'], // пропустил */
			['name' => 'Для дома и дачи', 'sub' => [
				['name' => 'Мебель и интерьер', 'id' => 1196360, 'types' => ['Компьютерные столы и кресла', 'Кровати, диваны и кресла']], 
				['name' => 'Растения', 'id' => 1196371], 
				['name' => 'Продукты питания', 'id' => 1196372], 
				['name' => 'Ремонт и строительство', 'types' => ['Готовые строения и срубы', 'Стройматериалы', 'Сантехника, водоснабжение и сауна', 'Инструменты', 'Окна и балконы', 'Потолки', 'Двери', 'Камины и обогреватели', 'Камины и обогреватели', 'Садовая техника']],
				['name' => 'Посуда и товары для кухни', 'id' => 1196401, 'types' => ['Посуда', 'Товары для кухни']], 
				['name' => 'Бытовая техника', 'id' => 1196404, 'types' => ['Пылесосы', 'Стиральные машины', 'Утюги']], 
			]],
			['name' => 'Транспорт', 'sub' => [
				['name' => 'Мотоциклы и мототехника'], 
				['name' => 'Водный транспорт'], 
				['name' => 'Грузовики и спецтехника', 'types' => ['Другое','Строительная техника','Автобусы','Грузовики','Прицепы','Погрузчики','Бульдозеры','Автокраны','Экскаваторы','Автодома','Сельхозтехника','Техника для лесозаготовки','Навесное оборудование','Коммунальная техника','Тягачи','Лёгкий коммерческий транспорт']],
				['name' => 'Запчасти и аксессуары', 'types' => ['Масла и автохимия','GPS-навигаторы','Прицепы','Экипировка','Шины, диски и колёса','Аудио- и видеотехника','Аксессуары','Инструменты','Багажники и фаркопы','Тюнинг','Запчасти','Противоугонные устройства']], 
				['name' => 'Автомобили'],
			]],
			['name' => 'Хобби и отдых', 'sub' => []], // пропустил
			['name' => 'Личные вещи', 'sub' => []], // пропустил
			['name' => 'Недвижимость', 'sub' => []], // пропустил
			['name' => 'Электроника', 'sub' => [
				['name' => 'Ноутбуки', 'id' => 1205154, 'types' => ['Acer', 'Apple', 'ASUS', 'Compaq', 'Dell', 'Fujitsu', 'HP', 'Huawei', 'Lenovo', 'Microsoft', 'Packard Bell', 'Samsung', 'Sony', 'Toshiba', 'Xiaomi', 'Другой']], 
				['name' => 'Фототехника', 'id' => 1205155, 'types' => ['Компактные фотоаппараты', 'Зеркальные фотоаппараты', 'Плёночные фотоаппараты', 'Бинокли и телескопы', 'Объективы', 'Оборудование и аксессуары']],				
				['name' => 'Телефоны', 'id' => 1205162, 'types' => ['Аккумуляторы','Гарнитуры и наушники','Зарядные устройства','Кабели и адаптеры','Модемы и роутеры','Чехлы и плёнки','Запчасти','Acer','Alcatel','ASUS','BlackBerry','BQ','DEXP','Explay','Fly','Highscreen','HTC','Huawei','iPhone','Lenovo','LG','Meizu','Micromax','Microsoft','Motorola','MTS','Nokia','Panasonic','Philips','Prestigio','Samsung','Siemens','SkyLink','Sony','teXet','Vertu','Xiaomi','ZTE','Другие марки','Рации','Стационарные телефоны']], 	
				['name' => 'Планшеты и электронные книги', 'id' => 1205174, 'types' => ['Планшеты','Электронные книги','Аккумуляторы','Гарнитуры и наушники','Док-станции','Зарядные устройства','Кабели и адаптеры','Модемы и роутеры','Стилусы','Чехлы и плёнки','Другое']], 	
				['name' => 'Оргтехника и расходники', 'id' => 1205187, 'types' => ['МФУ, копиры и сканеры','Принтеры','Телефония','ИБП, сетевые фильтры','Уничтожители бумаг','Блоки питания и батареи','Болванки','Бумага','Кабели и адаптеры','Картриджи','Канцелярия']], 	
				['name' => 'Товары для компьютера', 'id' => 1205200, 'types' => ['Акустика', 'Веб-камеры', 'Джойстики и рули', 'Клавиатуры и мыши', 'CD, DVD и Blu-ray приводы', 'Блоки питания', 'Видеокарты', 'Жёсткие диски', 'Звуковые карты', 'Контроллеры', 'Материнские платы', 'Оперативная память', 'Процессоры', 'Системы охлаждения', 'Мониторы', 'Переносные жёсткие диски', 'Сетевое оборудование', 'ТВ-тюнеры', 'Флэшки и карты памяти', 'Аксессуары']], 			
				['name' => 'Настольные компьютеры', 'id' => 1205223], 	
				['name' => 'Игры, приставки и программы', 'id' => 1205224, 'types' => ['Игровые приставки','Игры для приставок','Компьютерные игры','Программы']], 			
				['name' => 'Аудио и видео', 'id' => 1205229, 'types' => ['MP3-плееры','Акустика, колонки, сабвуферы','Видео, DVD и Blu-ray плееры','Видеокамеры','Кабели и адаптеры','Микрофоны','Музыка и фильмы','Музыкальные центры, магнитолы','Наушники','Телевизоры и проекторы','Усилители и ресиверы','Аксессуары']], 					
			]],
		];
		return $data;
	}
	
	public function get_form( ) 
	{				
		$phone = !empty($this->rule['phone'])?usam_get_phone_format($this->rule['phone']):'';
		$ad_status = !empty($this->rule['ad_status'])?$this->rule['ad_status']:'';	
		$manager = !empty($this->rule['manager'])?$this->rule['manager']:'';		
		?>		
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><label for='option_location'><?php esc_html_e( 'Адрес', 'usam'); ?>:</label></div>
			<div class ="edit_form__item_option">
				<textarea name='address'><?php echo htmlspecialchars($this->rule['address']); ?></textarea>
			</div>
		</div>		
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><label for='option_phone'><?php esc_html_e( 'Телефон для объявления', 'usam'); ?>:</label></div>
			<div class ="edit_form__item_option">
				<input type="text" name="phone" id="option_phone" value="<?php echo $phone; ?>" />
			</div>
		</div>
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><label for='option_manager'><?php esc_html_e( 'Имя менеджера', 'usam'); ?>:</label></div>
			<div class ="edit_form__item_option">
				<input type="text" name="manager" size='40' maxlength='40' id="option_manager" value="<?php echo $manager; ?>" />
				<p class ="description"><?php esc_html_e( 'Имя менеджера, контактного лица вашей компании по данному объявлению.', 'usam'); ?></p>	
			</div>					
		</div>
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php esc_html_e( 'Способ связи', 'usam'); ?>:</div>
			<div class ="edit_form__item_option">
				<select name="contact_method">							
					<?php						
					foreach( $this->contact_method as $key => $name )
					{						
						?><option value="<?php echo $key; ?>" <?php selected($key, $this->rule['contact_method']) ?>><?php echo $name; ?></option><?php
					}		
					?>	
				</select>
				<p class ="description"><?php esc_html_e( 'Возможность написать сообщение по объявлению через сайт.', 'usam'); ?></p>	
			</div>
		</div>		
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><label for='option_ad_status'><?php esc_html_e( 'Платная услуга', 'usam'); ?>:</label></div>
			<div class ="edit_form__item_option">
				<select id="option_ad_status" name="ad_status">							
					<?php						
					$selected = ['Free' => 'обычное объявление', 'Premium' => 'премиум-объявление', 'VIP' => 'VIP-объявление', 'PushUp' => 'поднятие объявления в поиске', 'Highlight' => 'выделение объявления', 'TurboSale' => 'применение пакета «Турбо-продажа»', 'QuickSale' => 'применение пакета «Быстрая продажа»'];
					foreach( $selected as $key => $name )
					{						
						?><option value="<?php echo $key; ?>" <?php selected($key, $ad_status) ?>><?php echo $name; ?></option><?php
					}		
					?>				
				</select>
			</div>
		</div>		
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php esc_html_e( 'Группа категорий', 'usam'); ?>:</div>
			<div class ="edit_form__item_option">
				<select v-model="data.avito_group" name="avito_group">							
					<option :value="k" v-for="(item,k) in data.category_list">{{item.name}}</option>
				</select>
			</div>
		</div>	
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><?php esc_html_e( 'Категория', 'usam'); ?>:</div>
			<div class ="edit_form__item_option">
				<select v-model="data.avito_category" name="avito_category">							
					<option :value="item.name" v-for="(item,k) in data.category_list[data.avito_group].sub">{{item.name}}</option>
				</select>
			</div>
		</div>		
		<div class ="edit_form__item" v-if="types.length">
			<div class ="edit_form__item_name"><?php esc_html_e( 'Вид товара', 'usam'); ?>:</div>
			<div class ="edit_form__item_option">
				<select v-model="data.avito_product_type" name="avito_product_type">							
					<option :value="name" v-for="name in types">{{name}}</option>
				</select>
			</div>
		</div>
		<div class ="edit_form__item">
			<div class ="edit_form__item_name"><label><?php esc_html_e( 'Опции', 'usam'); ?>:</label></div>
			<div class ="edit_form__item_option">
				<select-list @change="data.product_options=$event.id" :lists="attributes" :selected="data.product_options" :none="'<?php _e( 'Не требуется', 'usam'); ?>'"></select-list>
				<input type="hidden" name="product_options" v-model="data.product_options">	
			</div>
		</div>		
		<?php
	}	
	
	function save_form( ) 
	{
		$new_rule['phone'] = !empty($_POST['phone'])?preg_replace("[^0-9]",'',$_POST['phone']):'';
		$new_rule['address'] = !empty($_POST['address'])?sanitize_textarea_field(stripslashes($_POST['address'])):'';		
		$new_rule['ad_status'] = !empty($_POST['ad_status'])?sanitize_text_field($_POST['ad_status']):0;
		$new_rule['avito_category'] = !empty($_POST['avito_category'])?sanitize_text_field(stripslashes($_POST['avito_category'])):'';	
		$new_rule['avito_group'] = !empty($_POST['avito_group'])?absint($_POST['avito_group']):0;
		$new_rule['avito_product_type'] = !empty($_POST['avito_product_type'])?sanitize_text_field(stripslashes($_POST['avito_product_type'])):'';	
		$new_rule['contact_method'] = isset($_POST['contact_method'])?absint($_POST['contact_method']):1;
		$new_rule['manager'] = !empty($_POST['manager'])?sanitize_text_field($_POST['manager']):'';
		$new_rule['product_options'] = !empty($_POST['product_options'])?sanitize_text_field($_POST['product_options']):'';				
		return $new_rule;		
	}
}

function usam_get_categories_avito(  ) 
{		
	$category = array( 'auto' => array( 
		'title' => 'Запчасти и аксессуары', 
		'subcategory' => array(	 
			'11-618' => 'Запчасти / Для автомобилей / Автосвет',
			'11-619' => 'Запчасти / Для автомобилей / Аккумуляторы',
			'16-827' => 'Запчасти / Для автомобилей / Двигатель / Блок цилиндров, головка, картер',
			'16-828' => 'Запчасти / Для автомобилей / Двигатель / Вакуумная система',
			'16-829' => 'Запчасти / Для автомобилей / Двигатель / Генераторы, стартеры',
			'16-830' => 'Запчасти / Для автомобилей / Двигатель / Двигатель в сборе',
			'16-831' => 'Запчасти / Для автомобилей / Двигатель / Катушка зажигания, свечи, электрика',
			'16-832' => 'Запчасти / Для автомобилей / Двигатель / Клапанная крышка',
			'16-833' => 'Запчасти / Для автомобилей / Двигатель / Коленвал, маховик',
			'16-834' => 'Запчасти / Для автомобилей / Двигатель / Коллекторы',
			'16-835' => 'Запчасти / Для автомобилей / Двигатель / Крепление двигателя',
			'16-836' => 'Запчасти / Для автомобилей / Двигатель / Масляный насос, система смазки',
			'16-837' => 'Запчасти / Для автомобилей / Двигатель / Патрубки вентиляции',
			'16-838' => 'Запчасти / Для автомобилей / Двигатель / Поршни, шатуны, кольца',
			'16-839' => 'Запчасти / Для автомобилей / Двигатель / Приводные ремни, натяжители',
			'16-840' => 'Запчасти / Для автомобилей / Двигатель / Прокладки и ремкомплекты',
			'16-841' => 'Запчасти / Для автомобилей / Двигатель / Ремни, цепи, элементы ГРМ',
			'16-842' => 'Запчасти / Для автомобилей / Двигатель / Турбины, компрессоры',
			'16-843' => 'Запчасти / Для автомобилей / Двигатель / Электродвигатели и компоненты',
			'11-621' => 'Запчасти / Для автомобилей / Запчасти для ТО',
			'16-805' => 'Запчасти / Для автомобилей / Кузов / Балки, лонжероны',
			'16-806' => 'Запчасти / Для автомобилей / Кузов / Бамперы',
			'16-807' => 'Запчасти / Для автомобилей / Кузов / Брызговики',
			'16-808' => 'Запчасти / Для автомобилей / Кузов / Двери',
			'16-809' => 'Запчасти / Для автомобилей / Кузов / Заглушки',
			'16-810' => 'Запчасти / Для автомобилей / Кузов / Замки',
			'16-811' => 'Запчасти / Для автомобилей / Кузов / Защита',
			'16-812' => 'Запчасти / Для автомобилей / Кузов / Зеркала',
			'16-813' => 'Запчасти / Для автомобилей / Кузов / Кабина',
			'16-814' => 'Запчасти / Для автомобилей / Кузов / Капот',
			'16-815' => 'Запчасти / Для автомобилей / Кузов / Крепления',
			'16-816' => 'Запчасти / Для автомобилей / Кузов / Крылья',
			'16-817' => 'Запчасти / Для автомобилей / Кузов / Крыша',
			'16-818' => 'Запчасти / Для автомобилей / Кузов / Крышка, дверь багажника',
			'16-819' => 'Запчасти / Для автомобилей / Кузов / Кузов по частям',
			'16-820' => 'Запчасти / Для автомобилей / Кузов / Кузов целиком',
			'16-821' => 'Запчасти / Для автомобилей / Кузов / Лючок бензобака',
			'16-822' => 'Запчасти / Для автомобилей / Кузов / Молдинги, накладки',
			'16-823' => 'Запчасти / Для автомобилей / Кузов / Пороги',
			'16-824' => 'Запчасти / Для автомобилей / Кузов / Рама',
			'16-825' => 'Запчасти / Для автомобилей / Кузов / Решетка радиатора',
			'16-826' => 'Запчасти / Для автомобилей / Кузов / Стойка кузова',
			'11-623' => 'Запчасти / Для автомобилей / Подвеска',
			'11-624' => 'Запчасти / Для автомобилей / Рулевое управление',
			'11-625' => 'Запчасти / Для автомобилей / Салон',
			'16-521' => 'Запчасти / Для автомобилей / Система охлаждения',
			'11-626' => 'Запчасти / Для автомобилей / Стекла',
			'11-627' => 'Запчасти / Для автомобилей / Топливная и выхлопная системы',
			'11-628' => 'Запчасти / Для автомобилей / Тормозная система',
			'11-629' => 'Запчасти / Для автомобилей / Трансмиссия и привод',
			'11-630' => 'Запчасти / Для автомобилей / Электрооборудование',
			'6-401' => 'Запчасти / Для мототехники',
			'6-406' => 'Запчасти / Для спецтехники',
			'6-411' => 'Запчасти / Для водного транспорта',
			'4-943' => 'Аксессуары',
			'21' => 'GPS-навигаторы',
			'4-942' => 'Автокосметика и автохимия',
			'20' => 'Аудио- и видеотехника',
			'4-964' => 'Багажники и фаркопы',
			'4-963' => 'Инструменты',
			'4-965' => 'Прицепы',
			'11-631' => 'Противоугонные устройства / Автосигнализации',
			'11-632' => 'Противоугонные устройства / Иммобилайзеры',
			'11-633' => 'Противоугонные устройства / Механические блокираторы',
			'11-634' => 'Противоугонные устройства / Спутниковые системы',
			'22' => 'Тюнинг',
			'10-048' => 'Шины, диски и колёса / Шины',
			'10-047' => 'Шины, диски и колёса / Мотошины',
			'10-046' => 'Шины, диски и колёса / Диски',
			'10-045' => 'Шины, диски и колёса / Колёса',
			'10-044' => 'Шины, диски и колёса / Колпаки',
			'6-416' => 'Экипировка',
		)),
		'home' => array( 
			'title' => 'Ремонт и строительство', 
			'subcategory' => array(	
				'home_1' => 'Двери',
				'home_2' => 'Инструменты',
				'home_3' => 'Камины и обогреватели',
				'home_4' => 'Окна и балконы',
				'home_5' => 'Потолки',
				'home_6' => 'Садовая техника',
				'home_7' => 'Сантехника и сауна',
				'home_8' => 'Стройматериалы'
			)
		),		
		'furniture' => array( 
			'title' => 'Мебель и интерьер', 
			'subcategory' => array(	
				'furniture_1' => 'Компьютерные столы и кресла',
				'furniture_2' => 'Кровати, диваны и кресла',
				'furniture_3' => 'Кухонные гарнитуры',
				'furniture_4' => 'Освещение',
				'furniture_5' => 'Подставки и тумбы',
				'furniture_6' => 'Предметы интерьера, искусство',
				'furniture_7' => 'Столы и стулья',
				'furniture_8' => 'Текстиль и ковры',
				'furniture_9' => 'Шкафы и комоды',
				'furniture_10' => 'Другое'
			)
		),
		'equipment' => array( 
			'title' => 'Бытовая техника', 
			'subcategory' => array(	
				'equipment_1' => 'Пылесосы',
				'equipment_2' => 'Стиральные машины',
				'equipment_3' => 'Утюги',
				'equipment_4' => 'Швейные машины',
				'equipment_5' => 'Бритвы и триммеры',
				'equipment_6' => 'Машинки для стрижки',
				'equipment_7' => 'Фены и приборы для укладки',
				'equipment_8' => 'Эпиляторы',
				'equipment_9' => 'Вытяжки',
				'equipment_10' => 'Мелкая кухонная техника',
				'equipment_11' => 'Микроволновые печи',
				'equipment_12' => 'Плиты',
				'equipment_13' => 'Посудомоечные машины',
				'equipment_14' => 'Холодильники и морозильные камеры',
				'equipment_15' => 'Вентиляторы',
				'equipment_16' => 'Кондиционеры',
				'equipment_17' => 'Обогреватели',
				'equipment_18' => 'Очистители воздуха',
				'equipment_19' => 'Термометры и метеостанции',
				'equipment_20' => 'Другое'
			)
		),			
		'kitchen' => array( 
			'title' => 'Посуда и товары для кухни', 
			'subcategory' => array(	
				'kitchen_1' => 'Посуда',
				'kitchen_2' => 'Товары для кухни'
			)
		),
	);	
	return $category;
} 

/*

<div class ="edit_form__item" v-for="(column, name) in data.default_columns">
			<div class ="edit_form__item_name">{{name}}:</div>
			<div class ="edit_form__item_option">
				<input type="text" :name="column" value="data[column]"/>
			</div>
		</div>
		
<div class="desktop-1j2ovdc"><a href="1196183">IT, интернет, телеком<a href="1196188">Обучение, курсы<a href="1196198">Няни, сиделки<a href="1196199">Курьерские поручения<a href="1196200">Установка техники<a href="1196201">УборкаРемонт и обслуживание техники</div><div class="desktop-gng7vz"><a href="1196211">Фото-, аудио-, видеотехника</a></div><div class="desktop-gng7vz"><a href="1196212">Мобильные устройства</a></div><div class="desktop-gng7vz"><a href="1196213">Компьютерная техника</a></div><div class="desktop-gng7vz"><a href="1196214">Игровые приставки</a></div><div class="desktop-gng7vz"><a href="1196215">Телевизоры</a></div><div class="desktop-gng7vz"><a href="1196216">Крупная бытовая техника</a></div><div class="desktop-gng7vz"><a href="1196217">Мелкая бытовая техника<a href="1196218">Фото- и видеосъёмка</a></div><div class="desktop-wo1fz3">Животные<a href="1196220">Кошки</a><a href="1196256">Птицы</a><a href="1196257">Аквариум</a><a href="1196258">Другие животные</a><a href="1196267">Товары для животных</a><a href="1196268">Собаки</a></div><div class="desktop-wo1fz3">Для дома и дачи<a href="1196360">Мебель и интерьер</a><a href="1196371">Растения</a><a href="1196372">Продукты питания</a>Ремонт и строительство</div><div class="desktop-1j2ovdc"><a href="1196374">Готовые строения и срубыСтройматериалы</div><div class="desktop-gng7vz"><a href="1196376">Изоляция</a></div><div class="desktop-gng7vz"><a href="1196377">Крепёж</a></div><div class="desktop-gng7vz"><a href="1196378">Кровля и водосток</a></div><div class="desktop-gng7vz"><a href="1196379">Лаки и краски</a></div><div class="desktop-gng7vz"><a href="1196380">Металлопрокат</a></div><div class="desktop-gng7vz"><a href="1196382">Отделка</a></div><div class="desktop-gng7vz"><a href="1196383">Пиломатериалы</a></div><div class="desktop-gng7vz"><a href="1196384">Строительные смеси</a></div><div class="desktop-gng7vz"><a href="1196385">Строительство стен</a></div><div class="desktop-gng7vz"><a href="1196386">Электрика</a></div><div class="desktop-gng7vz"><a href="1196387">Другое</a></div><div class="desktop-gng7vz"><a href="1196388">Листовые материалы</a></div><div class="desktop-gng7vz"><a href="1196389">Лестницы и комплектующие</a></div><div class="desktop-gng7vz"><a href="1196390">Ворота, заборы и ограждения</a></div><div class="desktop-gng7vz"><a href="1196391">Сыпучие материалы</a></div><div class="desktop-gng7vz"><a href="1196392">Железобетонные изделия</a></div><div class="desktop-gng7vz"><a href="1196393">Сваи<a href="1196394">Сантехника, водоснабжение и сауна<a href="1196395">Инструменты<a href="1196396">Окна и балконы<a href="1196397">Потолки<a href="1196398">Двери<a href="1196399">Камины и обогреватели<a href="1196400">Садовая техника</a><a href="1196401">Посуда и товары для кухни</a><a href="1196404">Бытовая техника</a></div><div class="desktop-wo1fz3">ТранспортМотоциклы и мототехника</div><div class="desktop-1j2ovdc"><a href="1196431">Мопеды и скутеры<a href="1196432">Вездеходы<a href="1196433">Квадроциклы<a href="1196434">Мотоциклы<a href="1196440">Картинг<a href="1196441">Багги<a href="1196442">Снегоходы</a>Водный транспорт</div><div class="desktop-1j2ovdc"><a href="1196444">Каяки и каноэ<a href="1196445">Моторные лодки и моторы<a href="1196446">Гидроциклы<a href="1196447">Катера и яхты<a href="1196448">Вёсельные лодки<a href="1196449">Надувные лодки</a>Грузовики и спецтехника</div><div class="desktop-1j2ovdc"><a href="1196451">Другое<a href="1196452">Строительная техника<a href="1196453">Автобусы<a href="1196454">Грузовики<a href="1196455">Прицепы<a href="1196456">Погрузчики<a href="1196457">Бульдозеры<a href="1196458">Автокраны<a href="1196459">Экскаваторы<a href="1196460">Автодома<a href="1196461">Сельхозтехника<a href="1196462">Техника для лесозаготовки<a href="1196463">Навесное оборудование<a href="1196464">Коммунальная техника<a href="1196465">Тягачи<a href="1196466">Лёгкий коммерческий транспорт</a>Запчасти и аксессуары</div><div class="desktop-1j2ovdc"><a href="1196468">Масла и автохимия<a href="1196469">GPS-навигаторы<a href="1196470">Прицепы<a href="1196471">ЭкипировкаШины, диски и колёса</div><div class="desktop-gng7vz"><a href="1219057">Легковые шины</a></div><div class="desktop-gng7vz"><a href="1219058">Колпаки</a></div><div class="desktop-gng7vz"><a href="1219059">Диски</a></div><div class="desktop-gng7vz"><a href="1219060">Колёса</a></div><div class="desktop-gng7vz"><a href="1219061">Мотошины</a></div><div class="desktop-gng7vz"><a href="1286352">Шины для грузовиков и спецтехники<a href="1197725">Аудио- и видеотехника<a href="1197731">Аксессуары<a href="1197732">Инструменты<a href="1197733">Багажники и фаркопы<a href="1197734">ТюнингЗапчасти</div><div class="desktop-gng7vz"><a href="1218899">Для мототехники</a></div><div class="desktop-gng7vz"><a href="1218900">Для автомобилей</a></div><div class="desktop-gng7vz"><a href="1218901">Для грузовиков и спецтехники</a></div><div class="desktop-gng7vz"><a href="1218902">Для водного транспорта<a href="1219062">Противоугонные устройства</a>Автомобили</div><div class="desktop-1j2ovdc"><a href="1219063">Новые<a href="1219064">С пробегом</a></div><div class="desktop-wo1fz3">Хобби и отдых<a href="1201975">Охота и рыбалка</a><a href="1201976">Билеты и путешествия</a><a href="1201984">Музыкальные инструменты</a><a href="1201993">Велосипеды</a><a href="1201999">Книги и журналы</a><a href="1202003">Коллекционирование</a><a href="1202027">Спорт и отдых</a></div><div class="desktop-wo1fz3">Личные вещи<a href="1202042">Красота и здоровье</a>Одежда, обувь, аксессуары</div><div class="desktop-1j2ovdc"><a href="1202052">Сумки, рюкзаки и чемоданы<a href="1202059">Мужская обувь<a href="1202075">Женская обувь<a href="1202094">Аксессуары<a href="1202095">Мужская одежда<a href="1202118">Женская одежда</a>Детская одежда и обувь</div><div class="desktop-1j2ovdc"><a href="1202149">Для девочек<a href="1202159">Для мальчиков</a><a href="1202168">Товары для детей и игрушки</a><a href="1202178">Часы и украшения</a></div><div class="desktop-wo1fz3">НедвижимостьДома, дачи, коттеджиКвартирыЗемельные участкиНедвижимость за рубежомКоммерческая недвижимостьГаражи и машиноместаКомнаты</div>
*/
?>