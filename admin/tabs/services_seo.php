<?php
class USAM_Tab_services_seo extends USAM_Page_Tab
{
	protected $views = ['simple'];	
	public function get_title_tab()
	{			
		return __('SEO оптимизация и продвижение', 'usam');	
	}
	
	public function display() 
	{
		?>	
		<div class="service">
			<div class="service_title">Продвигаем как молодые (только что созданные), так и давно опубликованные сайты в ТОП-10 белыми методами за 3 месяца. Предоставляем услугу проработки SEO на старте - то есть создание SEO-сайта с нуля.</div>			
			<div class="service_sections">
				<div class="service_section">
					<div class="service_section_title">Оптимизация</div>
					<div class="service_section_text">Техническая и семантическая оптимизация существующего (или создаваемого) сайта, включающая технический и коммерческий аудит, сборку ядра, построение семантической структуры сайта, настройку SEO-параметров, подготовку к продвижению.</div>
				</div>
				<div class="service_section">
					<div class="service_section_title">Продвижение</div>
					<div class="service_section_text">SEO-продвижение сайта в ТОП-10 по ядру ключевых запросов с упором на позиции и трафик. Увеличиваем поведенческие факторы за счёт проработки сайта и контента, запускаем контентный маркетинг, предоставляем человечный и грамотный SEO-копирайтинг, оптимизируем текст и страницы, расширяем сайт, создаём новые разделы и страницы, формируем публикации на других площадках, закупаем трастовые ссылки и т.д.</div>
				</div>
			</div>
			<div class="service__buttons">
				<a href="https://wp-universam.ru/order-seo-website-promotion/" class="button button-primary" target="_blank">Подробнее об услуге</a>			
			</div>
		</div>
		<?php
	}
}