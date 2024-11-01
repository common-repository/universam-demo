<?php
class USAM_Tab_facebook extends USAM_Tab
{		
	protected $views = ['table', 'settings'];	
	public function __construct()
	{		
		require_once( USAM_APPLICATION_PATH . '/social-networks/facebook_api.class.php' );			
		$api = get_option('usam_fb_api', array('client_id' => ''));		
		$this->blank_slate = empty($api['client_id'])?true:false;	
	}
	
	public function get_title_tab() 
	{ 				
		if ( $this->table == 'fb_groups' )		
			return __('Группы Facebook', 'usam');	
		elseif ( $this->table == 'fb_users_profiles' )		
			return __('Анкеты пользователей Facebook', 'usam');		
		elseif ( $this->view == 'settings' )	
			return __('Настройка публикацией Facebook', 'usam');	
		else
			return __('Управление публикацией Facebook', 'usam');	
	}	
	
	protected function get_tab_forms()
	{
		if ( $this->table == 'fb_groups' )		
			return [['form' => 'edit', 'form_name' => 'fb_group', 'title' => __('Добавить группу', 'usam') ]];		
		elseif ( $this->table == 'fb_users_profiles' )		
			return [['form' => 'edit', 'form_name' => 'fb_user', 'title' => __('Добавить анкету', 'usam') ]];
		return [];
	}
	
	public function get_tab_sections() 
	{ 
		if ( $this->view == 'settings' )
		{ 
			$tables = $this->get_settings_tabs();
			array_unshift($tables, array('title' => __('Назад','usam') ) );		
		}	
		else
			$tables = array( 'fb_products' => array( 'title' => __('Публикация товаров','usam'), 'type' => 'table' ), 'fb_posts' => array( 'title' => __('Публикация записей','usam'), 'type' => 'table' ) );		
		return $tables;
	}
			
	public function get_message()
	{		
		$message = '';		
		if( isset($_REQUEST['uploaded']) && $_REQUEST['uploaded'] > 0 )
			$message = sprintf( _n( 'Загружена %s фотография.', 'Загружена %s фотография.', $_REQUEST['uploaded'], 'usam'), $_REQUEST['uploaded'] );	
		if( isset($_REQUEST['public']) && $_REQUEST['public'] > 0 )
			$message .= sprintf( _n( 'Опубликован %s товар.', 'Опубликовано %s товаров.', $_REQUEST['public'], 'usam'), $_REQUEST['public'] );		
		return $message;
	} 
	
	public function table_view() 
	{		
		$this->display_tab_sections();	
		if ( $this->table == 'fb_products' || $this->table == 'fb_posts' || $this->table == 'fb_wall' || $this->table == 'fb_users' )
		{
			$profiles = usam_get_social_network_profiles(['type_social' => ['fb_group', 'fb_user']]);	
			if ( empty($profiles) || $this->blank_slate )
			{
				$buttons = [
					['title' => __("Добавить группу","usam"), 'url' => admin_url("admin.php?page={$this->page_name}&tab={$this->tab}&view=settings&table=fb_groups")],
					['title' => __("Добавить анкету","usam"), 'url' => admin_url("admin.php?page={$this->page_name}&tab={$this->tab}&view=settings&table=fb_users_profiles")], 
					['title' => __("Настроить публикацию","usam"), 'url' => admin_url("admin.php?page={$this->page_name}&tab={$this->tab}&view=settings&section=application")], 
				];
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
		return ['fb_groups' => ['title' => __('Группы','usam'), 'type' => 'table'], 'fb_users_profiles' => ['title' => __('Анкеты пользователей','usam'), 'type' => 'table'], 'application' => ['title' => __('Настройки публикации','usam'), 'type' => 'section'], 'pixel' => ['title' => __('Пиксель','usam'), 'type' => 'section']];
	}
	
	public function display_section_pixel() 
	{				
		usam_add_box( 'usam_pixel', __('Настройка пикселя', 'usam'), array( $this, 'pixel_meta_box' ) );	
	}
	
	public function display_section_application() 
	{		
		usam_add_box( 'usam_application', __('Приложение', 'usam'), array( $this, 'application_meta_box' ) );
		usam_add_box( 'usam_decor', __('Оформление', 'usam'), array( $this, 'decor_meta_box' ) );	
		
		
	//	usam_add_box( 'usam_facebook_settings', __('Настройки Facebook API','usam'), array( $this, 'display_facebook' ) );	
	}
	
	public function application_meta_box()
	{
		$options = array(    	
			['key' => 'client_id', 'type' => 'input', 'title' => __('ID приложения', 'usam'), 'option' => 'fb_api'],		
			['key' => 'client_secret', 'type' => 'input', 'title' => __('Секретный ключ доступа', 'usam'), 'option' => 'fb_api'],		
			['key' => 'verify_token', 'type' => 'input', 'title' => __('Маркер подтверждения', 'usam'), 'option' => 'fb_api', 'attribute' => array( 'maxlength' => "50", 'size' => "50")],	
		//	['key' => 'verify_token', 'type' => 'input', 'title' => __('verify_token', 'usam'), 'option' => 'facebook_api', 'attribute' => array( 'maxlength' => "50", 'size' => "50")],	
		); 		  
		$this->display_table_row_option( $options ); 
	}
	
	public function pixel_meta_box()
	{
		$options = array(    	
			array( 'type' => 'radio', 'title' => __('Активировать', 'usam'), 'option' => 'facebook_pixel_active', 'default' => 1 ),	
			array( 'type' => 'input', 'title' => __('Facebook Pixel', 'usam'), 'option' => 'facebook_pixel', 'attribute' => array( 'maxlength' => "50", 'size' => "50") ),	
		); 		  
		$this->display_table_row_option( $options ); 
	}
		
	public function decor_meta_box()
	{		
		$prices = usam_get_prices( );
		$select_prices = array();
		foreach ( $prices as $price ) 
		{
			$select_prices[$price['code']] = $price['code']." - ".$price['title'];
		}			
		$options = array( 		
			['key' => 'from_signed', 'type' => 'checkbox', 'title' => __('Добавить пользователя', 'usam'), 'option' => 'fb_autopost', 'description' => __('Добавить к сообщению пользователя, опубликовавшего пост.', 'usam')],	
			['key' => 'add_link', 'type' => 'checkbox', 'title' => __('Добавить ссылку', 'usam'), 'option' => 'fb_autopost'],			
			['key' => 'upload_photo_count', 'type' => 'select', 'title' => __('Изображения', 'usam'), 'option' => 'fb_autopost', 'options' => [0,1,2,3,4,5], 'description' => __('Сколько изображений из статьи прикрепить к сообщению.', 'usam')],			
			['key' => 'excerpt_length', 'type' => 'input', 'title' => __('Анонс', 'usam'), 'option' => 'fb_autopost', 'description' => __('Сколько слов из статьи опубликовать в качестве анонса.', 'usam'), 'attribute' => ['maxlength' => "4", 'size' => "4"]],
			['key' => 'excerpt_length_strings', 'type' => 'input', 'title' => __('Анонс', 'usam'), 'option' => 'fb_autopost', 'description' => __('Сколько знаков из статьи опубликовать в качестве анонса. Не рекомендуется больше 2688.', 'usam'), 'attribute' => array( 'maxlength' => "4", 'size' => "4")],
			['key' => 'post_message', 'type' => 'textarea', 'title' => __('Сообщение для записей', 'usam'), 'option' => 'fb_autopost', 'description' => __('Маска сообщения для стены ВКонтакте: %title% - заголовок статьи, %excerpt% - анонс статьи, %link% - ссылка на статью', 'usam')],
			['key' => 'product_message', 'type' => 'textarea', 'title' => __('Сообщение для товаров', 'usam'), 'option' => 'fb_autopost', 'description' => __('Маска сообщения для стены ВКонтакте: %title% - заголовок статьи, %price_currency% - цена с валютой, %price% - цена, %old_price% - старая цена, %old_price_currency% - старая цена с валютой, %price_and_discont% - цена и скидка, если есть, %discont% - скидка, %excerpt% - анонс статьи, %link% - ссылка на статью, %name% - название сайта.','usam')],
			['key' => 'product_day_message', 'type' => 'textarea', 'title' => __('Сообщение для товара дня', 'usam'), 'option' => 'fb_autopost', 'description' => __('Маска сообщения для стены ВКонтакте: %title% - заголовок статьи, %price% - цена, %excerpt% - анонс статьи, %link% - ссылка на статью, %name% - название сайта.','usam')],
			['key' => 'reviews_message', 'type' => 'textarea', 'title' => __('Сообщение для отзывов', 'usam'), 'option' => 'fb_autopost', 'description' => __('Маска сообщения для стены ВКонтакте: %header% - заголовок, %review_title% - заголовок отзыва, %review_rating% - рейтинг, %review_author% - имя покупателя,%review_response% - ответ, %review_excerpt% - отзыв, %link% - ссылка на товар, %link_catalog% - ссылка на каталог.', 'usam')],			
			['key' => 'product_review_message', 'type' => 'textarea', 'title' => __('Сообщение для отзывов товара', 'usam'), 'option' => 'fb_autopost', 'description' => __('Маска сообщения для стены ВКонтакте: %review_title% - заголовок отзыва, %review_rating% - рейтинг, %review_author% - имя покупателя,%review_response% - ответ, %review_excerpt% - отзыв, %link% - ссылка на товар, %link_catalog% - ссылка на каталог.', 'usam')],			
			['key' => 'birthday', 'type' => 'textarea', 'title' => __('Сообщение с поздравлением ДР', 'usam'), 'option' => 'fb_autopost', 'description' => __('Маска сообщения для стены ВКонтакте: %user_link% - ссылка на страницу, %first_name% - имя, %last_name% - фамилия, %sex% - пол, %city% - город, %country% - страна, %photo_50% - фото, %photo_100% - фото. Все метки должны быть в {}, например {%user_link% %photo_100%}', 'usam')],	
		);	 
		$this->display_table_row_option( $options ); 
	}	
}