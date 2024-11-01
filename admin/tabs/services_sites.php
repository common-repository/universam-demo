<?php
class USAM_Tab_services_sites extends USAM_Page_Tab
{
	protected $views = ['simple'];	
	public function get_title_tab()
	{			
		return __('Создаем интернет-магазины под ключ', 'usam');	
	}
	
	public function display() 
	{
		?>	
		<div class="service">		
			<div class="service_sections">
				<div class="service_section">
					<div class="service_section_title">Команда</div>
					<div class="service_section_text">У нас есть профессиональная команда для разработки интернет-магазина с нуля. Для индивидуальных проектов мы готовим уникальный интернет-магазина, учитывающий специфику товара и бизнес-процессы. Наши магазины великолепно смотрятся и удобны не только клиентам, но и роботам поисковиков, что дает вам преимущество перед конкурентами. Ваш интернет-магазин будет иметь кросбраузерную и полностью адаптированную структуру.</div>
				</div>
				<div class="service_section">
					<div class="service_section_title">Что вы получите</div>
					<div class="service_section_text">Создадим интернет-магазин с интернированной CRM, в котором настроим сервисы для ведения электронной торговли, аналитики и продвижения бизнеса.</div>
				</div>
			</div>
			<div class="service__buttons">
				<a href="https://wp-universam.ru/create-online-store/" class="button button-primary" target="_blank">Подробнее об услуге</a>			
			</div>
		</div>

		<?php
	}
}