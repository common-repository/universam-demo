<?php
class USAM_Tab_search_engines extends USAM_Tab
{
	private $webmaster;
	public function __construct()
	{
		require_once( USAM_FILE_PATH . '/includes/seo/yandex/webmaster.class.php' );	
		require_once( USAM_FILE_PATH . '/includes/seo/keywords_query.class.php' );			
		if ( !empty($_REQUEST['table']) )
			$this->views = array( 'table' );
		else
		{
			$this->views = [];
			$this->webmaster = new USAM_Yandex_Webmaster();
			if ( current_user_can('view_seo_setting') )			
				$this->views[] = 'settings';
			$this->views[] = 'simple';				
		}			
	}	
		
	function get_title_tab() 
	{				
		if ( $this->view == 'settings' )
		{
			$title = __('Настройка мета-тегов', 'usam');
		}
		elseif ( !empty($_REQUEST['table']) )
		{			
			switch ( $this->table )
			{
				case "popular":
					$title = __('Популярные запросы', 'usam');	
				break;    
				case "external":
					$title = __('Внешние ссылки на сайт', 'usam');
				break;
				case "indexing_samples":
					$title = __('Загруженные страницы', 'usam');	
				break;				
			}				
		}		
		else
			$title = __('Вид в поисковиках', 'usam');
		
		return $title;
	}
	
	public function get_tab_sections() 
	{ 	
		if ( $this->view == 'settings' )
		{  
			$tables = $this->get_settings_tabs();
			array_unshift($tables, array('title' => __('Назад','usam') ) );	
		}	
		else
			$tables = [];
		return $tables;
	}
		
	public function simple_view() 
	{	
		if ( $this->webmaster->is_token() )
		{
			$this->summary_statistics();			
			usam_add_box( 'usam_statistics', __('Сводная информация', 'usam'), array( $this, 'diaplay_statistics' ) );
			if ( $this->webmaster->ready() )
			{				
				usam_add_box( 'usam_yandex_popular', __('Популярные запросы в Яндексе', 'usam'), array( $this, 'yandex_popular' ) );	
				usam_add_box( 'usam_yandex_external', __('Внешние ссылки на сайт', 'usam'), array( $this, 'yandex_external' ) );
				usam_add_box( 'usam_yandex_indexing_samples', __('Примеры загруженных страниц', 'usam'), array( $this, 'yandex_indexing_samples' ) );
								
				$this->webmaster_get_error();
			}			
		}
		else			
		{
			printf( __('Для выполнения действий на Яндекс Вебмастере от имени определенного пользователя клиентское приложение должно быть зарегистрировано на сервисе Яндекс.OAuth и токен сохранен в <a href="%s">настройках магазина</a> в разделе Паспорт в Яндексе.', 'usam'), admin_url("admin.php?page=seo&tab=search_engines") );
		}
	}
	
	public function webmaster_get_error()
	{       
		$errors = $this->webmaster->get_errors();								
		foreach ( $errors as $error )
		{ 
			echo "<div class=\"error\"><p>{$error}</p></div>";									
		}
	}	
	
	public function summary_statistics() 
	{	
		if ( $this->webmaster->ready() )
		{
			$statistics = $this->webmaster->get_statistics_site( );			
			if ( empty($statistics) )
			{			
				return '';
			}		
			?>
			<div class="crm-important-data">
				<div class="crm-start-row">
					<h3 class="title"><?php esc_html_e( 'Статистика из Яндекса', 'usam'); ?></h3>
					<div class="crm-start-row-result">
						<div class="crm-start-row-result-item">
							<div class="crm-start-row-result-item-title"><?php esc_html_e( 'Индекс качества сайта', 'usam'); ?></div>
							<div class="crm-start-row-result-item-total"><?php echo $statistics['sqi']; ?></div>
						</div>
						<div class="crm-start-row-result-item">
							<div class="crm-start-row-result-item-title"><?php esc_html_e( 'Страницы в поиске', 'usam'); ?></div>
							<div class="crm-start-row-result-item-total"><?php echo $statistics['searchable_pages_count']; ?></div>
						</div>
						<div class="crm-start-row-result-item">
							<div class="crm-start-row-result-item-title"><?php esc_html_e( 'Исключенные страницы', 'usam'); ?></div>
							<div class="crm-start-row-result-item-total"><?php echo $statistics['excluded_pages_count']; ?></div>
						</div>				
					</div>
				</div>
			</div>
			<?php
		}	
	}
	
	public function diaplay_statistics() 
	{			
		?>
		<div class='yandex'>
			<div class='usam_document__container'>
				<div class='usam_document__container_title'><a href='https://webmaster.yandex.ru/site/dashboard/' target='_blank' rel="noopener"><?php _e( 'Войти в Яндекс Вебмастер', 'usam'); ?></a></div>				
				<?php
				if ( $this->webmaster->ready() )
				{
					$statistics = $this->webmaster->get_statistics_site( );			
					if ( !empty($statistics) )
					{												
						$fatal = !empty($statistics['site_problems']['FATAL'])?"<span class='item_status status_flagged'>".$statistics['site_problems']['FATAL']."</span>":"<span class='item_status item_status_valid '>0</span>";
						$critical = !empty($statistics['site_problems']['CRITICAL'])?"<span class='item_status status_flagged'>".$statistics['site_problems']['CRITICAL']."</span>":"<span class='item_status item_status_valid '>0</span>";
						$possible_problem = !empty($statistics['site_problems']['POSSIBLE_PROBLEM'])?"<span class='item_status item_status_notcomplete'>".$statistics['site_problems']['POSSIBLE_PROBLEM']."</span>":"<span class='item_status item_status_valid '>0</span>";
						?>
						<h4><?php _e('Проблемы сайта', 'usam'); ?></h4>
						<div class="view_data">		
							<div class="view_data__row">
								<div class="view_data__name"><?php _e('Фатальные проблемы', 'usam'); ?>:</div>
								<div class="view_data__option"><?php echo $fatal ?></div>
							</div>		
							<div class="view_data__row">
								<div class="view_data__name"><?php _e('Критичные проблемы', 'usam'); ?>:</div>
								<div class="view_data__option"><?php echo $critical ?></div>
							</div>			
							<div class="view_data__row">
								<div class="view_data__name"><?php _e('Возможные проблемы', 'usam'); ?>:</div>
								<div class="view_data__option"><?php echo $possible_problem ?></div>
							</div>	
							<?php
							if ( !empty($statistics['site_problems']['RECOMMENDATION']) )
							{
								?>
							<div class="view_data__row">
								<div class="view_data__name"><?php _e('Рекомендация', 'usam'); ?>:</div>								
								<div class="view_data__option"><a href='https://webmaster.yandex.ru/site/http:radov39.ru:80/diagnosis/checklist/#recommendation' target='_blank' rel="noopener"><?php _e('Смотреть рекомендации', 'usam'); ?> (<?php echo $statistics['site_problems']['RECOMMENDATION']; ?>)</a></div>
							</div>	
							<?php }	?>							
						</div>							
						<?php
					}
				}
				else
				{
					printf(__('Для выполнения действий на Яндекс.Вебмастере вы должны выбрать сайт в <a href="%s">настройках</a> в разделе Яндекс Вебмастер.', 'usam'), admin_url("admin.php?page=seo&tab=search_engines"));
				}
				?>
			</div>
		</div>
		<div class='google'>
			<div class='usam_document__container'>		
				<div class='usam_document__container_title'><a href='https://www.google.com/webmasters/tools/home/' target='_blank' rel="noopener"><?php _e( 'Войти в Google Вебмастер', 'usam') ?></a></div>		
				<p><?php printf( __('Войти в свой кабинет <a href="%s" target="_blank" rel="noopener">Google Вебмастер</a>.', 'usam'), "https://www.google.com/webmasters/tools/home" ) ?></p>		
			</div>
		</div>		
		<?php
	}

	public function yandex_popular() 
	{				
		$count = 10;
		$popular = $this->webmaster->get_popular( 'TOTAL_SHOWS', array( 'TOTAL_SHOWS','TOTAL_CLICKS','AVG_SHOW_POSITION','AVG_CLICK_POSITION') );
		if ( empty($popular) )
		{			
			return '';
		}
		$out = "<table class ='usam_list_table'>";
		$out .= "<thead>";
		$out .= "<tr>
				<td class='column-wordstat'></td>
				<td>".__('Популярные запросы', 'usam')."</td>
				<td>".__('Показы', 'usam')."</td>
				<td>".__('Клики', 'usam')."</td>
				<td>".__('Средняя позиция', 'usam')."</td>
				<td>".__('Средняя позиция клика', 'usam')."</td>
				</tr>
				</thead><tbody>";			
				$i = 0;
				foreach ( $popular as $value )
				{							
					$out .= "<tr><td><a href='https://wordstat.yandex.ru/#!/?words=".$value['query_text']."' target='_blank'  rel='noopener'><span class='dashicons dashicons-editor-paste-word'></span></a></td><td><a href='https://yandex.ru/search/?text=".$value['query_text']."' target='_blank'  rel='noopener'>".$value['query_text']."</a></td><td>".$value['indicators']['TOTAL_SHOWS']."</td><td>".$value['indicators']['TOTAL_CLICKS']."</td><td>".round($value['indicators']['AVG_SHOW_POSITION'],1)."</td><td>".round($value['indicators']['AVG_CLICK_POSITION'],1)."</td></tr>";
					$i++;
					if ( $i >= $count )
						break;
				}	 				
			$out .= "</tbody></table>";
		echo $out;
		?>
		<p><strong><a href="<?php echo admin_url("admin.php?page=seo&tab=search_engines&table=popular"); ?>"><?php _e( 'Посмотреть 500 популярных запросов', 'usam'); ?></a></strong></p>
		<?php
	}
	
	public function yandex_external() 
	{			
		$external = $this->webmaster->get_external( );
		if ( empty($external['links']) )
		{			
			return '';
		}		
		$out = "<table class ='usam_list_table'>";
		$out .= "<thead>";
		$out .= "<tr>
				<td>".__('Откуда', 'usam')."</td>
				<td>".__('Куда', 'usam')."</td>
				<td>".__('Дата обнаружения', 'usam')."</td>
				</tr>
				</thead><tbody>";			
				foreach ( $external['links'] as $value )
				{							
					$out .= "<tr><td><a href='".$value['source_url']."' target='_blank' rel='noopener'>".$value['source_url']."</a></td><td><a href='".$value['destination_url']."' target='_blank' rel='noopener'>".$value['destination_url']."</a></td><td>".date("d.m.Y",strtotime($value['discovery_date']))."</td></tr>";					
				}	 				
			$out .= "</tbody></table>";
		echo $out;
		?>
		<p><strong><a href="<?php echo admin_url("admin.php?page=seo&tab=search_engines&table=external"); ?>"><?php printf( __('Посмотреть все %s внешних ссылок', 'usam'), $external['count']); ?></a></strong></p>
		<?php
	}	
	
	
	public function yandex_indexing_samples() 
	{			
		$url = $this->webmaster->get_indexing_samples( );	
		if ( empty($url['samples']) )
		{			
			return '';
		}	
		$out = "<table class ='usam_list_table'>";
		$out .= "<thead>";
		$out .= "<tr>
					<td>".__('Ссылка', 'usam')."</td>
					<td>".__('Дата обнаружения', 'usam')."</td>
					<td>".__('Код', 'usam')."</td>
				</tr>
				</thead><tbody>";			
				foreach ( $url['samples'] as $value )
				{							
					$out .= "<tr><td><a href='".$value['url']."' target='_blank' rel='noopener'>".$value['url']."</a></td><td>".date("d.m.Y",strtotime($value['access_date']))."</td><td>".$value['http_code']."</td></tr>";					
				}	 				
			$out .= "</tbody></table>";
		echo $out;
		?>
		<p><strong><a href="<?php echo admin_url("admin.php?page=seo&tab=search_engines&table=indexing_samples"); ?>"><?php _e( 'Посмотреть больше загруженных страниц', 'usam'); ?></a></strong></p>
		<?php
	}
	
	public function get_settings_tabs() 
	{
		return ['virtual_pages' => array('title' => __('Виртуальные страницы','usam'), 'type' => 'section'), 'post_types' => array('title' => __('Типы содержимого','usam'), 'type' => 'section'), 'taxonomies' => array('title' => __('Таксономии','usam'), 'type' => 'section')];	
	}
	
	public function display_section_virtual_pages() 
	{
		$this->meta_tags('pages');	
	}	
	
	public function display_section_post_types() 
	{	
		$this->meta_tags('post_types');		
	}	
	
	public function display_section_taxonomies() 
	{				
		$this->meta_tags('terms');	
	}	
	
	public function meta_tags( $type ) 
	{	
		?>
		<div class="postbox usam_box" v-for="(item, k) in metas.<?php echo $type; ?>">
			<div class="handlediv" title="<?php _e('Нажмите, чтобы переключить','usam'); ?>"></div>
			<h3 class="usam_box__title ui-sortable-handle"><span>{{item.name}}</span></h3>
			<div class="inside">
				<div class = "edit_form">				
					<div class="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e('Предпросмотр', 'usam'); ?>:</div>
						<div class ="option google_search_preview">
							<div class="google_search_preview__domain"><?php echo home_url(); ?></div>
							<div class="google_search_preview__title" v-html="item.title"></div>
							<div class="google_search_preview__description" v-html="item.description"></div>
						</div>
					</div>					
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e('Шорткоды', 'usam'); ?>:</div>
						<div class ="option add_metatags">
							<div class ="add_metatags__tag" v-for="(title, k) in item.shortcode" @click="insert(title, k)">{{title}}</div>
						</div>
					</div>
					<div class="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e('Заголовок', 'usam'); ?>:</div>
						<div ref="title" class ="shortcode_editor" contenteditable="true" v-html="item.title" @blur="blur('<?php echo $type; ?>',k,'title')"></div>	
					</div>		
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e('Мета-описание', 'usam'); ?>:</div>
						<div ref="description" class ="shortcode_editor shortcode_editor_description" @blur="blur('<?php echo $type; ?>',k,'description')" contenteditable="true" v-html="item.description"></div>
					</div>		
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e('Показывать в поиске?', 'usam'); ?>:</div>
						<selector v-model="item.noindex" :items="[{id:'', name:'<?php _e('Да', 'usam'); ?>'},{id:'1', name:'<?php _e('Нет', 'usam'); ?>'}]"></selector>
					</div>	
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e('Должны ли поисковые системы проходить по ссылкам?', 'usam'); ?>:</div>
						<selector v-model="item.nofollow" :items="[{id:'', name:'<?php _e('Да', 'usam'); ?>'},{id:'1', name:'<?php _e('Нет', 'usam'); ?>'}]"></selector>
					</div>	
					<div class="edit_form__title"><?php esc_html_e( 'Мета-теги для социальных сетей', 'usam'); ?></div>					
					<div class="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e('Заголовок', 'usam'); ?>:</div>
						<div ref="opengraph_title" class ="shortcode_editor" contenteditable="true" v-html="item.opengraph_title" @blur="blur('<?php echo $type; ?>',k,'opengraph_title')"></div>	
					</div>		
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e('Описание', 'usam'); ?>:</div>
						<div ref="opengraph_description" class ="shortcode_editor shortcode_editor_description" @blur="blur('<?php echo $type; ?>',k,'opengraph_description')" contenteditable="true" v-html="item.opengraph_description"></div>
					</div>					
				</div>
			</div>
		</div>
		<?php
	}	
}