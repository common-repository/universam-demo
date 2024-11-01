<?php
class USAM_Tab_documentation extends USAM_Page_Tab
{	
	protected $views = ['simple'];
	public function get_title_tab()
	{
		return __('Документация', 'usam');
	}
	
	public function display() 
	{		
		?>
		<h3><?php _e( 'Помощь', 'usam'); ?></h3>
		<ul>
			<li><a target='blank' rel="noopener" href="https://docs.wp-universam.ru/document/category/users"><?php _e( 'База знаний', 'usam'); ?></a></li>
			<li><a target='blank' rel="noopener" href="https://docs.wp-universam.ru/document/using-your-theme"><?php _e( 'Использование своей темы', 'usam'); ?></a></li>
			<li><a target='blank' rel="noopener" href="https://docs.wp-universam.ru/document/category/users"><?php _e( 'Узнать больше о том как начать', 'usam'); ?></a></li>
			<li><a target='blank' rel="noopener" href="https://docs.wp-universam.ru/document/exchange-online-store-and-1c-8"><?php _e( 'Подключить 1С', 'usam'); ?></a></li>
			<li><a target='blank' rel="noopener" href="https://docs.wp-universam.ru/document/ozon"><?php _e( 'Подключить озон', 'usam'); ?></a></li>
			<li><a target='blank' rel="noopener" href="https://docs.wp-universam.ru/document/podklyuchenie-kassy-evotor-k-internet-magazinu"><?php _e( 'Подключение кассы Эвотор', 'usam'); ?></a></li>
			<li><a target='blank' rel="noopener" href="https://docs.wp-universam.ru/document/advanced-notifications"><?php _e( 'Уведомления для персонала или партнеров', 'usam'); ?></a></li>
		</ul>
		<h3><?php _e( 'Разработчикам', 'usam'); ?></h3>
		<ul>
			<li><a target='blank' rel="noopener" href="https://docs.wp-universam.ru/document/category/razrabotchikam"><?php _e( 'База знаний', 'usam'); ?></a></li>
			<li><a target='blank' rel="noopener" href="https://docs.wp-universam.ru/document/category/razrabotchikam/api">API</a></li>
			<li><a target='blank' rel="noopener" href="https://docs.wp-universam.ru/document/category/razrabotchikam/funktsii"><?php _e( 'Функции платформы', 'usam'); ?></a></li>
			<li><a target='blank' rel="noopener" href="https://docs.wp-universam.ru/document/category/razrabotchikam/shablony"><?php _e( 'Шаблоны', 'usam'); ?></a></li>
		</ul>
		<h3><?php _e( 'О платформе', 'usam'); ?></h3>
		<ul>
			<li class="learn-more"><a target='blank' rel="noopener" href="https://wp-universam.ru/capabilities/"><?php _e( 'Возможности', 'usam'); ?></a></li>
			<li class="newsletter"><a target='blank' class="button" rel="noopener" href="https://wp-universam.ru/support/"><?php _e( 'Задать вопрос', 'usam'); ?></a></li>
		</ul>	
		<?php	
	}		
}
