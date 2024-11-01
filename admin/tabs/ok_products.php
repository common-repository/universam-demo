<?php
class USAM_Tab_ok_products extends USAM_Tab
{
	protected $display_save_button = true;
	protected $views = ['table', 'settings'];
		
	public function get_title_tab() 
	{ 		
		if ( $this->view == 'settings' )
		{			
			if ( $this->table == 'ok_groups' )		
				$title = __('Группы', 'usam');
			else
			{					
				$title = __('Настройки интеграции с социальной сетью Одноклассники', 'usam');	
				if ( !empty($_REQUEST['section']) )
				{
					if ( $_REQUEST['section'] == 'application' )
						$title = __('Настройка публикацией', 'usam');
				}				
			}
		}
		else
			$title = __('Управление публикацией в Одноклассниках', 'usam');
		return $title;
	}
		
	protected function get_tab_forms()
	{ 
		if ( $this->table == 'ok_groups' )		
			return [['form' => 'edit', 'form_name' => 'ok_group', 'title' => __('Добавить группу', 'usam')]];		
		return array();
	}
	
	public function get_tab_sections() 
	{ 
		$tables = array();	
		if ( $this->view == 'settings' )
		{ 
			$tables = $this->get_settings_tabs();
			array_unshift($tables, array('title' => __('Назад','usam') ) );	
		}		
		return $tables;
	}		
	
	public function table_view() 
	{		
		$this->display_tab_sections();	
		if ( $this->table == 'ok_products' )
		{
			$profiles = usam_get_social_network_profiles( array( 'type_social' => array( 'ok_group', 'ok_user' ) ) );	
			if ( empty($profiles) || $this->blank_slate )
			{
				$buttons = array( 
					array( 'title' => __("Добавить группу","usam"), 'url' => admin_url("admin.php?page={$this->page_name}&tab={$this->tab}&view=settings&table=ok_groups") ),
				//	array( 'title' => __("Добавить анкету","usam"), 'url' => admin_url("admin.php?page={$this->page_name}&tab={$this->tab}&view=settings&table=ok_users_profiles") ), 
					array( 'title' => __("Настроить публикацию","usam"), 'url' => admin_url("admin.php?page={$this->page_name}&tab={$this->tab}&view=settings&section=application") ), 
				);
				$this->display_connect_service( __('Произведите настройку, чтобы увидеть потрясающие возможности публикации.', 'usam'), $buttons );	
				return;
			}
			else	
			{	
				?>
				<div class="profiles_panel">
					<div class="profiles_content">		
						<span class="name"><?php _e( 'Ваши анкеты и группы', 'usam'); ?>:</span>
						<div class="select_profile">							
							<form method='GET' action=''>
								<input type='hidden' value='<?php echo $this->page_name; ?>' name='page' />
								<input type='hidden' value='<?php echo $this->tab; ?>' name='tab' />
								<input type='hidden' value='<?php echo $this->table; ?>' name='table'>
								<select name ="profile_id" id ="profile_id" onchange="this.form.submit()">
									<?php
									$profile_id = isset($_REQUEST['profile_id'])?absint($_REQUEST['profile_id']):0;
									foreach ( $profiles as $profile ) 
									{	
										?><option value='<?php echo $profile->id; ?>' <?php selected( $profile_id, $profile->id ) ?>><img src="<?php echo $profile->photo; ?>"><?php echo $profile->name; ?></option><?php									
									}
									?>
								</select>
							</form>
						</div>
					</div>
				</div>
				<?php
			}
		}		
		$this->list_table->display_table(); 
	}
		
	public function get_settings_tabs() 
	{ 
		return array( 'ok_groups' => array('title' => __('Группы','usam'), 'type' => 'table' ), 'application' => array( 'title' => __('Настройки публикации','usam'), 'type' => 'section') );
	}
	
	public function display_section_application() 
	{	
		usam_add_box( 'usam_odnoklassniki_meta_box', __('Настройка подключения', 'usam'), array( $this, 'odnoklassniki_meta_box' ) );
		usam_add_box( 'usam_decor', __('Оформление', 'usam'), array( $this, 'decor_meta_box' ) );	
	}
		
	public function odnoklassniki_meta_box()
	{	
		$options = array( 									
			array( 'key' => 'client_id', 'type' => 'input', 'title' => __('ID приложения', 'usam'), 'option' => 'odnoklassniki', 'description' => '<a href="https://ok.ru/vitrine/myuploaded/">'.__('Ваши приложения можете создать или посмотреть здесь.','usam').'</a>' ),		
			array( 'key' => 'application_key', 'type' => 'input', 'title' => __('Публичный ключ приложения', 'usam'), 'option' => 'odnoklassniki' ),		
			array( 'key' => 'client_secret', 'type' => 'input', 'title' => __('Секретный ключ приложения', 'usam'), 'option' => 'odnoklassniki' ),	
			array( 'key' => 'secret_session_key', 'type' => 'input', 'title' => 'secret session key', 'option' => 'odnoklassniki' ),	
			array( 'key' => 'access_token', 'type' => 'input', 'title' => 'access token', 'option' => 'odnoklassniki' ),
		);	  
		$this->display_table_row_option( $options );		
	}		
	
	public function decor_meta_box()
	{			
		$options = [ 													
			['key' => 'add_link', 'type' => 'checkbox', 'title' => __('Добавить cсылку', 'usam'), 'option' => 'ok_autopost'],	
			['key' => 'fix_product_day', 'type' => 'checkbox', 'title' => __('Закреплять Товар Дня', 'usam'), 'option' => 'ok_autopost', 'description' => __('Закреплять Товар Дня.', 'usam')],
			['key' => 'upload_photo_count', 'type' => 'select', 'title' => __('Изображения', 'usam'), 'option' => 'ok_autopost', 'options' => [0,1,2,3,4,5], 'description' => __('Сколько изображений из статьи прикрепить к сообщению.', 'usam')],			
			['key' => 'excerpt_length', 'type' => 'input', 'title' => __('Анонс', 'usam'), 'option' => 'ok_autopost', 'description' => __('Сколько слов из статьи опубликовать в качестве анонса.', 'usam'), 'attribute' => ['maxlength' => "4", 'size' => "4"]],		
			['key' => 'excerpt_length_strings', 'type' => 'input', 'title' => __('Анонс', 'usam'), 'option' => 'ok_autopost', 'description' => __('Сколько знаков из статьи опубликовать в качестве анонса. Не рекомендуется больше 2688.', 'usam'), 'attribute' => ['maxlength' => "4", 'size' => "4"]],
			['key' => 'post_message', 'type' => 'textarea', 'title' => __('Сообщение для записей', 'usam'), 'option' => 'ok_autopost', 'description' => __('Маска сообщения для стены : %title% - заголовок статьи, %excerpt% - анонс статьи, %link% - ссылка на статью', 'usam')],
			['key' => 'product_message', 'type' => 'textarea', 'title' => __('Сообщение для товаров', 'usam'), 'option' => 'ok_autopost', 'description' => __('Маска сообщения для стены : %title% - заголовок статьи, %price_currency% - цена с валютой, %price% - цена, %old_price% - старая цена, %old_price_currency% - старая цена с валютой, %price_and_discont% - цена и скидка, если есть, %discont% - скидка, %excerpt% - анонс статьи, %link% - ссылка на статью, %name% - название сайта.')],
			['key' => 'product_day_message', 'type' => 'textarea', 'title' => __('Сообщение для товара дня', 'usam'), 'option' => 'ok_autopost', 'description' => __('Маска сообщения для стены : %title% - заголовок статьи, %price% - цена, %excerpt% - анонс статьи, %link% - ссылка на статью, %name% - название сайта.','usam')],
			['key' => 'reviews_message', 'type' => 'textarea', 'title' => __('Сообщение для отзывов', 'usam'), 'option' => 'ok_autopost', 'description' => __('Маска сообщения для стены : %header% - заголовок, %review_title% - заголовок отзыва, %review_rating% - рейтинг, %review_author% - имя покупателя,%review_response% - ответ, %review_excerpt% - отзыв, %link% - ссылка на товар, %link_catalog% - ссылка на каталог.', 'usam')],			
			['key' => 'product_review_message', 'type' => 'textarea', 'title' => __('Сообщение для отзывов товара', 'usam'), 'option' => 'ok_autopost', 'description' => __('Маска сообщения для стены : %review_title% - заголовок отзыва, %review_rating% - рейтинг, %review_author% - имя покупателя,%review_response% - ответ, %review_excerpt% - отзыв, %link% - ссылка на товар, %link_catalog% - ссылка на каталог.', 'usam')],			
			['key' => 'birthday', 'type' => 'textarea', 'title' => __('Сообщение с поздравлением ДР', 'usam'), 'option' => 'ok_autopost', 'description' => __('Маска сообщения для стены : %user_link% - ссылка на страницу, %first_name% - имя, %last_name% - фамилия, %sex% - пол, %city% - город, %country% - страна, %photo_50% - фото, %photo_100% - фото. Все метки должны быть в {}, например {%user_link% %photo_100%}', 'usam')],			
		];	 
		$this->display_table_row_option( $options ); 
	}	
}