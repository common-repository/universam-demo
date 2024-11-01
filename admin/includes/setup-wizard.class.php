<?php
/**
 *  Мастер установки
 */
class USAM_Admin_Setup_Wizard 
{
	private $step   = '';
	private $steps  = array();
	
	public function __construct()
	{
		if ( apply_filters( 'usam_enable_setup_wizard', true ) ) 
		{
			add_action( 'admin_menu', array( $this, 'admin_menus' ) );
			add_action( 'admin_init', array( $this, 'display' ) );
		}
	}

	/**
	 * Add admin menus/screens.
	 */
	public function admin_menus() {
		add_dashboard_page( '', '', 'manage_options', 'usam-setup', '' );
	}
	
	public function update_option( $key, $value) 
	{
		update_option( 'usam_'.$key, $value );
	}
	
	public function display()
	{
		set_current_screen( 'usam_setup_wizard' );
		
		$default_steps = array(
			'introduction' => array(
				'name'    => __('Начальная страница', 'usam'),
				'view'    => 'introduction',
			),		
			'information' => array(
				'name'    => __('О сайте', 'usam'),
				'view'    => 'information',
			),
			'company' => array(
				'name'    => __('Ваша компания', 'usam'),
				'view'    => 'company',
			),
			'shipping_payments' => array(
				'name'    => __('Доставка и оплата', 'usam'),
				'view'    => 'shipping_payments',
			),	
			'theme' => array(
				'name'    => __('Тема', 'usam'),
				'view'    => 'theme',
			),
			'next_steps' => array(
				'name'    => __('Готово', 'usam'),
				'view'    => 'ready',
			),
		);
		if ( ! current_user_can( 'install_themes' ) || ! current_user_can( 'switch_themes' ) || is_multisite() ) {
			unset( $default_steps['theme'] );
		}

		$this->steps = apply_filters( 'usam_setup_wizard_steps', $default_steps );
		$this->step = isset($_GET['step'] ) ? sanitize_key( $_GET['step'] ) : current( array_keys( $this->steps ) );	
	
		wp_enqueue_style( 'usam-setup', USAM_URL . '/admin/assets/css/setup-wizard.css', ['dashicons', 'install'], USAM_VERSION_ASSETS );	
		//wp_enqueue_script( 'usam-setup-wizard', USAM_URL . '/admin/assets/js/setup-wizard.js', ['vue'], USAM_VERSION_ASSETS );				
		
		wp_enqueue_style( 'usam-form' );	
		wp_enqueue_style( 'forms' );		
		
		wp_enqueue_style('themes');	
		
		$this->action_processing();

		ob_start();
		$this->setup_wizard_header();
		$this->setup_wizard_steps();
		$this->setup_wizard_content();
		$this->setup_wizard_footer();
		exit;		
	}
	
	public function action_processing()
	{		
		if ( !empty( $_POST['save_step'] ) && isset($this->steps[ $this->step ]['view'] ) )
		{			
			$method = 'controller_'.$this->steps[ $this->step ]['view'].'_save';			
			if ( method_exists($this, $method) )
			{ 
				check_admin_referer( 'usam_setup' );
				$this->$method();		
			}
			wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
			exit;
		}	
	}

	public function get_next_step_link( $step = '' ) 
	{
		if ( ! $step ) {
			$step = $this->step;
		}

		$keys = array_keys( $this->steps );
		if ( end( $keys ) === $step ) {
			return admin_url();
		}

		$step_index = array_search( $step, $keys );
		if ( false === $step_index ) {
			return '';
		}

		return add_query_arg( 'step', $keys[ $step_index + 1 ] );
	}

	public function setup_wizard_header() 
	{
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta name="viewport" content="width=device-width" />
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<title><?php esc_html_e( 'УНИВЕРСАМ &rsaquo; Мастер установки', 'usam'); ?></title>			
			<?php wp_print_scripts( 'usam_setup' ); ?>
			<?php do_action( 'admin_enqueue_scripts', 'usam_setup_wizard' ); ?>	
			<?php do_action( 'admin_print_styles' ); ?>
			<?php do_action( 'admin_print_scripts' ); ?>
			<?php do_action( 'admin_head' ); ?>	
		</head>
		<body class="usam_setup wp-core-ui">
			<h1 id="usam_logo"><a href="https://wp-universam.ru"><img src="<?php echo USAM_CORE_IMAGES_URL; ?>/universam.png" alt="Универсам"></a></h1>
			<form method="post">
		<?php
	}

	
	public function setup_wizard_footer() 
	{				
			?>
			<?php do_action("admin_footer", 'usam_setup_wizard'); ?>
			<?php do_action('admin_print_footer_scripts'); ?>		
			</body>
			</form>
		</html>
		<?php
	}

	
	public function setup_wizard_steps() 
	{
		$ouput_steps = $this->steps;
		array_shift( $ouput_steps );
		?>
		<ol class="usam_setup-steps">
			<?php foreach ( $ouput_steps as $step_key => $step ) : ?>
				<li class="<?php
					if ( $step_key === $this->step ) {
						echo 'active';
					} elseif ( array_search( $this->step, array_keys( $this->steps ) ) > array_search( $step_key, array_keys( $this->steps ) ) ) {
						echo 'done';
					}
				?>"><?php echo esc_html( $step['name'] ); ?></li>
			<?php endforeach; ?>
		</ol>
		<?php
	}

	public function setup_wizard_content() 
	{
		echo '<div class="usam_setup-content">';		
		$method = 'setup_'.$this->steps[ $this->step ]['view'];	
		if ( method_exists($this, $method) )
		{			
			$this->$method();		
		}	
		echo '</div>';
		wp_nonce_field( 'usam_setup' ); 
	}

	
	public function setup_introduction()
	{
		?>
		<h1><?php esc_html_e( 'Добро пожаловать!', 'usam'); ?></h1>		
		<p><?php _e( 'В платформе &laquo;Универсам&raquo; вы сможете не только создать современный интернет-магазин, но и:', 'usam'); ?></p>
		<ul>
			<li><?php _e( 'создавать документы <strong>Счет на оплату, Акты, Акты сверки, Договоры, Приказы, Доверенности</strong>', 'usam'); ?></li>
			<li><?php _e( 'получать платежи на сайт и раскидывать их по клиентам, формировать <strong>Акты сверки</strong>', 'usam'); ?></li>
			<li><?php _e( 'подключить <strong>1С или Мой склад</strong>', 'usam'); ?></li>
			<li><?php _e( 'загружать <strong>письма</strong> прямо на сайт, они автоматически раскидаются по клиентам', 'usam'); ?></li>
			<li><?php _e( 'использовать <strong>CRM</strong>', 'usam'); ?></li>
			<li><?php _e( 'управлять курьерами через менеджер', 'usam'); ?></li>
			<li><?php _e( 'использовать свое облачное хранилище файлов', 'usam'); ?></li>			
			<li><?php _e( 'мощные инструменты для <strong>SEO продвижения</strong>', 'usam'); ?></li>
		</ul>
		
		<p><?php _e( 'Спасибо за то, что выбрали &laquo;Универсам&raquo;, самую большую платформу для <strong>управлением бизнесом и интернет-магазином</strong>, помогающую управлять любыми каналами продаж.', 'usam'); ?></p>
		
		
		<p><?php _e( 'Если вы что-то продаете, то &laquo;Универсам&raquo; поможет увеличить продажи и сократить расходы на облуживания вашего бизнеса. Произвести аналитику эффективности маркетинговых затрат на рекламу. Он поможет найти ваших конкурентов на рынке.', 'usam'); ?></p>
		<p><?php _e( '<strong>Этот помощник установки поможет произвести базовые настройки. Это необязательная, но очень важная процедура и она не должна занять много времени.</strong>.', 'usam'); ?></p>	
		
		<p><?php esc_html_e( "Продолжая использование платформы, вы принимаете ", 'usam'); ?><a href="https://wp-universam.ru/agreement/licenzionnoe-soglashenie-programma-dlya-evm-universam/" target="_blank" rel="noopener"><?php esc_html_e( "лицензионное соглашение", 'usam'); ?></a></p>
		
		<p class="usam_setup-actions step">
			<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button-primary button button-next"><?php esc_html_e( "Начать настройку", 'usam'); ?></a>
			<a href="<?php echo esc_url( admin_url('admin.php?page=personnel') ); ?>" class="button"><?php esc_html_e( 'Не сейчас', 'usam'); ?></a>
		</p>
		<?php
	}	
				
	public function setup_information()
	{
		$currency_default = get_option( 'usam_currency_type' );
		$currency_pos   = get_option( 'usam_currency_sign_location' );
		$decimal_sep    = get_option( 'usam_decimal_separator' );	
		$thousand_sep   = get_option( 'usam_thousands_separator' );		
		$currencies     = usam_get_currencies();		
		$website_type   = get_option( 'usam_website_type', 'store' );	
		$demo_data = usam_get_products(['posts_per_page' => 1, 'fields' => 'ids']);	
		$privacy_policy = $demo_data ? 0 : 1;
		$user_agreement = $demo_data ? 0 : 1;		
		
		$location_name = usam_get_locations(['fields' => 'name', 'parent' => 0]);	
		$locations = ['RU' => __('Россия', 'usam'), 'BY' => __('Беларусь', 'usam'), 'KZ' => __('Казахстан', 'usam'), 'UA' => __('Украина', 'usam'), 'others'  => __('Другие', 'usam')];
		
		$right = 'company';
		?>
		<h1><?php esc_html_e( 'Основные настройки', 'usam'); ?></h1>	
		<table class="form-table">
			<tr>
				<th scope="row"><label for="website_type"><?php esc_html_e( 'Как вы работаете?', 'usam'); ?></label></th>
				<td>					
					<div class="usam_radio">
						<div class="usam_radio__item usam_radio-company <?php echo $right == 'company'?'checked':''; ?>">
							<div class="usam_radio_enable">
								<input type="radio" name="installation" class="input-radio" value="company" <?php checked($right, 'company'); ?>/>
								<label><?php _e('Несколько менеджеров будут работать на сайте', 'usam'); ?></label>
							</div>										
						</div>	
						<div class="usam_radio__item usam_radio-ip <?php echo $right == 'ip'?'checked':''; ?>">
							<div class="usam_radio_enable">
								<input type="radio" name="installation" class="input-radio" value="ip" <?php checked($right, 'ip'); ?>/>
								<label><?php _e('Я буду работать один', 'usam'); ?></label>
							</div>										
						</div>						
					</div>			
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="website_type"><?php esc_html_e( 'Вариант сайта', 'usam'); ?></label></th>
				<td>					
					<select id="website_type" name="website_type">
						<?php 
						$options = array( 'crm' => __('Корпоративный сайт с CRM', 'usam'), 'store' => __('Интернет-магазин', 'usam'), 'marketplace' => __('Маркетплейс', 'usam') );
						foreach ( $options as $key => $name ) : 							
						?>
							<option value="<?php echo $key; ?>" <?php selected( $key, $website_type ); ?>><?php echo $name; ?></option>
						<?php endforeach; ?>
					</select>					
				</td>
			</tr>	
			<tr>
				<th scope="row"><label for="products"><?php esc_html_e( 'Что будите продавать?', 'usam'); ?></label></th>
				<td>					
					<div class="usam_checked">
						<?php 
						$select_products = get_option('usam_types_products_sold', ['product', 'services']);
						$products = usam_get_types_products_sold();
						foreach ( $products as $key => $type ) : 							
							?>
							<div class="usam_checked__item js-checked-item usam_checked-<?php echo esc_attr( $key ); ?> <?php echo in_array($key, $select_products)?'checked':''; ?>">
								<div class="usam_checked_enable">
									<input type="checkbox" name="types_products_sold[]" class="input-checkbox" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array($key, $select_products), true ); ?>/>
									<label><?php echo esc_html( $type['plural'] ); ?></label>
								</div>										
							</div>
						<?php endforeach; ?>
					</div>											
				</td>
			</tr>	
			<tr>
				<th scope="row"><label for="decimal_sep"><?php esc_html_e( 'Где будите продавать?', 'usam'); ?></label></th>
				<td>					
					<div class="usam_checked">
						<?php 
						foreach ( $locations as $key => $name ) : 						
							if ( $key == 'others' )
								$checked = in_array('США', $location_name);
							else
								$checked = in_array($name, $location_name);
							?>
							<div class="usam_checked__item js-checked-item usam_checked-<?php echo esc_attr( $key ); ?> <?php echo $checked?'checked':''; ?>">
								<div class="usam_checked_enable">
									<input type="checkbox" name="codes[]" class="input-checkbox" value="<?php echo esc_attr( $key ); ?>" <?php checked( $checked, true ); ?>/>
									<label><?php echo esc_html( $name ); ?></label>
								</div>										
							</div>
						<?php endforeach; ?>
					</div>					
				</td>
			</tr>			
			<tr>
				<th scope="row"><label for="decimal_sep"><?php esc_html_e( 'Добавить демо-данные?', 'usam'); ?></label></th>
				<td>					
					<div class="usam_checked">
						<div class="usam_checked__item js-checked-item <?php echo !$demo_data?'checked':''; ?>">
							<div class="usam_checked_enable">
								<input type="checkbox" name="demo" class="input-checkbox" value="1" <?php checked( !$demo_data ); ?>/>
								<label><?php esc_html_e( 'Добавить данные', 'usam'); ?></label>
							</div>										
						</div>
					</div>					
				</td>
			</tr>			
			<tr>
				<th scope="row"><label for="decimal_sep"><?php esc_html_e( 'Обновить страницу?', 'usam'); ?></label></th>
				<td>					
					<div class="usam_checked">
						<div class="usam_checked__item js-checked-item <?php echo !$privacy_policy?'checked':''; ?>">
							<div class="usam_checked_enable">
								<input type="checkbox" name="privacy_policy" class="input-checkbox" value="1" <?php checked( !$privacy_policy ); ?>/>
								<label><?php esc_html_e( 'Политика конфиденциальности', 'usam'); ?></label>
							</div>										
						</div>
					</div>					
				</td>
			</tr>	
			<tr>
				<th scope="row"><label for="decimal_sep"><?php esc_html_e( 'Обновить страницу?', 'usam'); ?></label></th>
				<td>					
					<div class="usam_checked">
						<div class="usam_checked__item js-checked-item <?php echo !$user_agreement?'checked':''; ?>">
							<div class="usam_checked_enable">
								<input type="checkbox" name="user_agreement" class="input-checkbox" value="1" <?php checked( !$user_agreement ); ?>/>
								<label><?php esc_html_e( 'Пользовательское соглашение', 'usam'); ?></label>
							</div>										
						</div>
					</div>					
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="currency_type"><?php esc_html_e( 'Основная валюта', 'usam'); ?></label></th>
				<td>
					<select id="currency_type" name="currency_type" style="width:100%;" data-placeholder="<?php esc_attr_e( 'Выберите валюту…', 'usam'); ?>" class="chzn-select">
						<option value=""><?php esc_html_e( 'Choose a currency&hellip;', 'usam'); ?></option>
						<?php
						foreach ( $currencies as $currency ) {
							echo '<option value="' . esc_attr( $currency->code ) . '" ' . selected( $currency_default, $currency->code, false ) . '>' . $currency->code." ($currency->name)" . '</option>';
						}							
						?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="currency_sign_location"><?php esc_html_e( 'Позиция валюты', 'usam'); ?></label></th>
				<td>
					<select id="currency_sign_location" name="currency_sign_location">
						<option value="3" <?php selected( $currency_pos, '3' ); ?>><?php esc_html_e( 'Слева', 'usam'); ?></option>
						<option value="1" <?php selected( $currency_pos, '1' ); ?>><?php esc_html_e( 'Справа', 'usam'); ?></option>
						<option value="4" <?php selected( $currency_pos, '4' ); ?>><?php esc_html_e( 'Слева с пробелом', 'usam'); ?></option>
						<option value="2" <?php selected( $currency_pos, '2' ); ?>><?php esc_html_e( 'Справа с пробелом', 'usam'); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="thousands_separator"><?php esc_html_e( 'Разделитель тысяч', 'usam'); ?></label></th>
				<td><input type="text" id="thousands_separator" name="thousands_separator" size="2" value="<?php echo esc_attr( $thousand_sep ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="decimal_separator"><?php esc_html_e( 'Десятичный разделитель', 'usam'); ?></label></th>
				<td><input type="text" id="decimal_separator" name="decimal_separator" size="2" value="<?php echo esc_attr( $decimal_sep ); ?>"></td>
			</tr>			
		</table>
	
		<div class="tracker">
			<p class="checkbox">
				<input type="checkbox" id="tracker_checkbox" name="tracker" value="1" checked>
				<label for="tracker_checkbox"><?php esc_html_e( 'Помогите &laquo;Универсам&raquo; сделать лучше — поделитесь данными об использовании.', 'usam'); ?></label>
			</p>
			<p>
			<?php
			esc_html_e( 'Устанавливая этот флажок, вы помогаете улучшать &laquo;Универсам&raquo;: информация о вашем магазине будет использоваться для оценки новых функций, качества обновлений и целесообразности улучшений. Если вы не поставите этот флажок, мы не станем собирать информацию об использовании вами нашего продукта. ', 'usam');
			echo ' <a target="_blank" rel="noopener" href="http://wp-universam.ru/usage-tracking/">' . esc_html__('Прочитайте информацию о том, какие данные мы собираем.', 'usam') . '</a>';
			?>
			</p>
		</div>				
		
		<p class="usam_setup-actions step">
			<input type="submit" class="button-primary button button-next" value="<?php esc_attr_e( 'Продолжить', 'usam'); ?>" name="save_step">
			<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-next"><?php esc_html_e( 'Пропустить этот шаг', 'usam'); ?></a>
		</p>
		<?php
	}
			
	public function controller_information_save() 
	{							
		require_once( USAM_FILE_PATH . '/includes/directory/country_query.class.php'  );	
		$locations_db = usam_get_locations( array('fields' => 'id=>name', 'parent' => 0 ) );	
		$locations = array( 'RU' => __('Россия', 'usam'), 'BY' => __('Беларусь', 'usam'), 'KZ' => __('Казахстан', 'usam'), 'UA' => __('Украина', 'usam'), 'others'  => __('Другие', 'usam') );
		
		$locations_code = array();
		if ( !empty($locations_db) )
		{
			foreach ( $locations as $code => $name1 ) 
			{
				foreach ( $locations_db as $id => $name2 ) 
				{					
					if ( $name1 == $name2 )
						$locations_code[$code] = $id;
				}
			}
		}		
		$select_codes = !empty($_REQUEST['codes'])?$_REQUEST['codes']:array();
		$delete_location = array();
		$codes = array();	
		foreach ( $locations as $code => $name ) 
		{			
			if (  in_array($code, $select_codes) )
			{				
				if ( $code == 'others' )
				{					
					if ( !in_array('США', $locations_db) )
						$codes[] = $code;					
				}
				elseif ( !in_array($name, $locations_db) )					
					$codes[] = $code;
			}
			elseif ( in_array($name, $locations_db) && !empty($locations_code[$code]) )
			{				
				$delete_location[] = $locations_code[$code];
			}
			elseif ( $name == 'Другие' )					
			{		
				foreach ( $locations_db as $id => $name2 ) 
				{					
					if ( !in_array($name2, $locations) )
						$delete_location[] = $id;
				}				
			}
		} 	
		if ( !empty($codes) )
			usam_install_locations( $codes );		
		if ( !empty($delete_location) )
			usam_delete_locations( $delete_location );
		
		$currency_type    = sanitize_text_field( $_POST['currency_type'] );
		$currency_sign_location   = sanitize_text_field( $_POST['currency_sign_location'] );
		$thousands_separator   = sanitize_text_field( $_POST['thousands_separator'] );
		$decimal_separator   = sanitize_text_field( $_POST['decimal_separator'] );
		$website_type   = sanitize_text_field( $_POST['website_type'] );
		$allow_tracking   = !empty( $_POST['tracker'] )?1:0;
		$types_products_sold = array_map('sanitize_title', (array)$_POST['types_products_sold']);

		$this->update_option( 'types_products_sold', $types_products_sold );
		$this->update_option( 'website_type', $website_type );
		$this->update_option( 'currency_type', $currency_type );
		$this->update_option( 'currency_sign_location', $currency_sign_location );
		$this->update_option( 'thousands_separator', $thousands_separator );
		$this->update_option( 'decimal_separator', $decimal_separator );
		$this->update_option( 'allow_tracking', $allow_tracking );	
		
		if ( !empty($_REQUEST['demo']) )
		{ 		
			wp_schedule_single_event(time() + 30, 'usam_install_default_db_data');
		}	
		if ( !empty($_REQUEST['privacy_policy']) )
		{ 		
			$privacy_policy_page_id     = (int) get_option( 'wp_page_for_privacy-policy' );
			$content = file_get_contents(USAM_FILE_PATH . '/admin/db/db-install/privacy-policy.txt');
			wp_update_post(['ID' => $privacy_policy_page_id, 'post_content' => $content]);
		}
		if ( !empty($_REQUEST['user_agreement']) )
		{ 		
			$user_agreement_page_id = (int) get_option( 'page_for_user_agreement' );
			$content = file_get_contents(USAM_FILE_PATH . '/admin/db/db-install/user-agreement.txt');
			if( $user_agreement_page_id )
				wp_update_post(['ID' => $user_agreement_page_id, 'post_content' => $content]);
			else
				wp_insert_post(['post_title' => __( 'Пользовательское соглашение', 'usam'), 'post_status'  => 'draft', 'post_type' => 'page', 'post_content' => $content]);				
		}		
	}
	
	public function setup_company()
	{						
		$location = get_option( 'usam_shop_location' );	
		$shop_company = get_option( 'usam_shop_company' ); 		
		$return_email = get_option( 'usam_return_email' ); 		
		
		$mailboxes = usam_get_mailboxes( );	
		?>
		<h1><?php esc_html_e( 'Ваш бизнес', 'usam'); ?></h1>	
		<table class="form-table">
			<?php if ( $shop_company ) { ?>
			<tr>
				<th scope="row"><label for="shop_company_sep"><?php esc_html_e( 'Ваша компания', 'usam'); ?></label></th>
				<td><?php usam_select_bank_accounts( $shop_company, array('name' => "shop_company") ); ?>	</td>
			</tr>	
			<?php } else { ?>
			<tr>
				<th scope="row"><label for="company_name"><?php esc_html_e( 'Название компании', 'usam'); ?></label></th>
				<td><input type="text" id="company_name" name="company_name" value="ООО Красная заря"></td>
			</tr>	
			<tr>
				<th scope="row"><label for="company_inn"><?php esc_html_e( 'ИНН', 'usam'); ?></label></th>
				<td><input type="text" id="company_inn" name="company_meta[inn]" value="123456789012"></td>
			</tr>	
			<tr>
				<th scope="row"><label for="company_phone"><?php esc_html_e( 'Контактный телефон', 'usam'); ?></label></th>
				<td><input type="text" id="company_phone" name="company_phone" value="89218518900"></td>
			</tr>	
			<tr>
				<th scope="row"><label for="company_email"><?php esc_html_e( 'email', 'usam'); ?></label></th>
				<td><input type="text" id="company_email" name="company_email" value="<?php echo get_bloginfo('admin_email'); ?>"></td>
			</tr>	
			<tr>
				<th scope="row"><label for="company_rs"><?php esc_html_e( 'Название банка', 'usam'); ?></label></th>
				<td><input type="text" id="company_rs" name="company_acc[name]" value="Сбербанк"></td>
			</tr>			
			<tr>
				<th scope="row"><label for="company_rs"><?php esc_html_e( 'Расчетный счет', 'usam'); ?></label></th>
				<td><input type="text" id="company_rs" name="company_acc[number]" value="111111111111111111111"></td>
			</tr>	
			<?php } ?>			
			<tr>
				<th scope="row"><label for="store_location"><?php esc_html_e( 'Местоположение магазина', 'usam'); ?></label></th>
				<td>					
				<?php
				$autocomplete = new USAM_Autocomplete_Forms( );
				$autocomplete->get_form_position_location( $location );
				?>	
				</td>
			</tr>			
			<?php  if ( !empty($mailboxes) ) { ?>
			<tr>
				<th scope="row"><label for="email_sep"><?php esc_html_e( 'Email магазина', 'usam'); ?></label></th>
				<td>
					<select id="return_email" name="return_email" style="width:100%;" data-placeholder="<?php esc_attr_e( 'Выберите…', 'usam'); ?>">							
						<?php
						foreach ( $mailboxes as $mailbox )
						{
							echo '<option value="' . esc_attr( $mailbox->email ) . '" ' . selected( $return_email, $mailbox->email, false ) . '>' . $mailbox->name.' ('.$mailbox->email.')</option>';
						}
						?>
					</select>										
				</td>
			</tr>	
			<?php } else { ?>	
			</table>
			<h2><?php esc_html_e( 'Почта магазина', 'usam'); ?></h2>	
			<table class="form-table">
				<tr>
					<th scope="row"><label for="score_email"><?php esc_html_e( 'Email магазина', 'usam'); ?></label></th>
					<td><input type="text" id="score_email" name="mailbox[email]" value="<?php echo get_bloginfo('admin_email'); ?>"></td>
				</tr>	
				<tr>
					<th scope="row"><label for="score_email_name"><?php esc_html_e( 'Имя отправителя', 'usam'); ?></label></th>
					<td><input type="text" id="score_email_name" name="mailbox[name]" value="ООО Красная заря"></td>
				</tr>					
			<?php } ?>		
		</table>
		<p class="usam_setup-actions step">
			<input type="submit" class="button-primary button button-next" value="<?php esc_attr_e( 'Продолжить', 'usam'); ?>" name="save_step">
			<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-next"><?php esc_html_e( 'Пропустить этот шаг', 'usam'); ?></a>
		</p>
		<?php
	}
			
	public function controller_company_save() 
	{		
		global $wpdb;
		$shop_location  = absint( $_POST['location'] );
		if ( !empty($_POST['company_name']) )
		{
			$company_name   = sanitize_text_field( $_POST['company_name'] );			
			$meta    = $_POST['company_meta'];
			$meta['phone']  = sanitize_text_field( $_POST['company_phone'] );
			$meta['email']  = sanitize_text_field( $_POST['company_email'] );			
			$meta['contactlocation'] = $shop_location;
			$meta['legallocation'] = $shop_location;
			$meta['full_company_name'] = $company_name;
			$meta['company_name'] = $company_name;
			
			$user_id = get_current_user_id();
			$company = new USAM_Company(['name' => $company_name, 'type' =>'own','manager_id' => $user_id]);
			$company->save();
			$company_id	= $company->get('id');				
			foreach ( $meta as $meta_key => $value ) 
			{		
				$meta_value = trim( wp_unslash( $value ) );					
				if ( !empty($meta_value) )						
					$update = usam_update_company_metadata( $company_id, $meta_key, $meta_value );
			}			
			$new_company_acc = $_POST['company_acc'];			
			$new_company_acc['company_id'] = $company_id;
			usam_insert_bank_account( $new_company_acc );
			$shop_company = $company_id;
		}		
		else
			$shop_company = !empty($_POST['shop_company'])?absint( $_POST['shop_company'] ):0;		
		
		if ( !empty($_POST['mailbox']) )
		{
			$mailbox = $_POST['mailbox'];
			$return_email = $mailbox['email'];			
			$mailbox_id = usam_insert_mailbox( $mailbox );	
			$users = get_users(['role' => 'administrator', 'fields' => 'ID']);
			$sql = "INSERT INTO `".USAM_TABLE_MAILBOX_USERS."` (`id`,`user_id`) VALUES ('%d','%d') ON DUPLICATE KEY UPDATE `user_id`='%d'";	
			foreach ( $users as $userid ) 
			{
				$wpdb->query( $wpdb->prepare($sql, $mailbox_id, $userid, $userid ));	
			}
		}
		else
			$return_email = !empty($_POST['return_email'])?sanitize_text_field($_POST['return_email']):'';
		
		
		$this->update_option( 'shop_company', $shop_company );
		$this->update_option( 'shop_location', $shop_location );
		$this->update_option( 'return_email', $return_email );
	}

	public function setup_shipping_payments() 
	{
		$delivery_service = usam_get_delivery_services( array('active' => 'all') );
		$gateways = usam_get_payment_gateways( array('active' => 'all') );		
		$types_payers = usam_get_group_payers( );
		?>
		<h1><?php esc_html_e( 'Оформление заказа', 'usam'); ?></h1>	
		<table class="form-table">
			<tr>
				<th scope="row"><label for="decimal_sep"><?php esc_html_e( 'Типы плательщиков', 'usam'); ?></label></th>
				<td>					
					<div class="usam_checked">
						<?php foreach ( $types_payers as $types_payer ) : ?>
							<div class="usam_checked__item js-checked-item usam_checked-<?php echo esc_attr( $types_payer['id'] ); ?> <?php echo $types_payer['active']==1?'checked':''; ?>">
								<div class="usam_checked_enable">
									<input type="checkbox" name="types_payers[]" class="input-checkbox" value="<?php echo esc_attr( $types_payer['id'] ); ?>" <?php checked( $types_payer['active'] ); ?>/>
									<label><?php echo esc_html( $types_payer['name'] ); ?></label>
								</div>										
							</div>
						<?php endforeach; ?>
					</div>		
					<p><?php _e( 'Выберите типы плательщиков, которым вы будете продавать на сайте. Для разных типов плательщиков возможны различные способы оплаты и доставки, а также различный набор свойств заказа.','usam'); ?></p>					
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="decimal_sep"><?php esc_html_e( 'Способы доставки', 'usam'); ?></label></th>
				<td>					
					<div class="usam_checked">
						<?php 
						foreach ( $delivery_service as $shipping ) : 		
							
							?>
							<div class="usam_checked__item js-checked-item usam_checked-<?php echo esc_attr( $shipping->id ); ?> <?php echo $shipping->active?'checked':''; ?>">
								<div class="usam_checked_enable">
									<input type="checkbox" name="shipping[]" class="input-checkbox" value="<?php echo esc_attr( $shipping->id ); ?>" <?php checked( $shipping->active, 1 ); ?>/>
									<label><?php echo esc_html( $shipping->name ); ?></label>
								</div>										
							</div>
						<?php endforeach; ?>
					</div>					
				</td>
			</tr>		
			<tr>
				<th scope="row"><label for="decimal_sep"><?php esc_html_e( 'Варианты оплаты', 'usam'); ?></label></th>
				<td>					
					<div class="usam_checked">
						<?php 
						foreach ( $gateways as $gateway ) : 		
							
							?>
							<div class="usam_checked__item js-checked-item usam_checked-<?php echo esc_attr( $gateway->id ); ?> <?php echo $gateway->active?'checked':''; ?>">
								<div class="usam_checked_enable">
									<input type="checkbox" name="gateways[]" class="input-checkbox" value="<?php echo esc_attr( $gateway->id ); ?>" <?php checked( $gateway->active, 1 ); ?>/>
									<label><?php echo esc_html( $gateway->name ); ?></label>
								</div>										
							</div>
						<?php endforeach; ?>
					</div>	
				</td>
			</tr>			
		</table>
		<p class="usam_setup-actions step">
			<input type="submit" class="button-primary button button-next" value="<?php esc_attr_e( 'Продолжить', 'usam'); ?>" name="save_step">
			<input type="submit" class="button button-next" value="<?php esc_attr_e( 'Пропустить этот шаг', 'usam'); ?>" name="save_step">
		</p>
		<?php
	}

	public function controller_shipping_payments_save() 
	{
		global $wpdb;
		
		require_once(USAM_FILE_PATH.'/includes/basket/payment_gateways_query.class.php');
		require_once(USAM_FILE_PATH.'/includes/basket/delivery_services_query.class.php');

		$delivery_service = usam_get_delivery_services(['active' => 'all']);
		$gateways = usam_get_payment_gateways(['active' => 'all']);	
		foreach ( $delivery_service as $shipping )  
		{
			$where = array('id' => $shipping->id);
			if ( !empty($_POST['shipping']) && in_array($shipping->id, $_POST['shipping']) )
				$insert = array( 'active' => 1 );	
			else
				$insert = array( 'active' => 0 );
			
			$result = $wpdb->update( USAM_TABLE_DELIVERY_SERVICE, $insert, $where );	
		}
		foreach ( $gateways as $gateway ) 
		{
			$where = array('id' => $gateway->id);
			if ( !empty($_POST['gateways']) && in_array($gateway->id, $_POST['gateways']) )
				$insert = array( 'active' => 1 );	
			else
				$insert = array( 'active' => 0 );
			
			$result = $wpdb->update( USAM_TABLE_PAYMENT_GATEWAY, $insert, $where );	
		}
		$types_payers_ids = !empty($_POST['types_payers'])?array_map('intval', $_POST['types_payers']):array(); 
		$types_payers = usam_get_group_payers( );
		foreach ( $types_payers as $types_payer )
		{				
			if ( in_array($types_payer['id'], $types_payers_ids ) )
				$types_payer['active'] = 1;		
			else
				$types_payer['active'] = 0;	
			usam_edit_data( $types_payer, $types_payer['id'], 'usam_types_payers', false );	
		}	
		
	}		
	
	/**
	 * Установка темы
	 */
	private function setup_theme() 
	{		
		$api = new USAM_Service_API();
		$themes = $api->get_themes( );
		?>		
		<div id = "feedback" class = "usam_tabs usam_tabs_style2">
			<div class = "header_tab">
				<a class = "tab" href="#paid_themes"><?php _e( 'Платные темы', 'usam'); ?></a>
				<a class = "tab" href="#free_themes"><?php _e( 'Бесплатные темы', 'usam'); ?></a>			
			</div>	
			<div class = "countent_tabs">		
				<div id="paid_themes" class = "tab">	
					<h2><?php esc_html_e( 'Платные темы', 'usam'); ?></h2>
					<div class="themes">
						<?php foreach ( $themes as $theme ) { ?>
							<div class="theme">
								<div class="theme__thumbnail">
									<a href="https://themes.wp-universam.ru/" target="_blank" rel="noopener"><img src="<?php echo $theme['thumbnail']; ?>"/></a>
								</div>
								<div class="theme__title">
									<a href="https://themes.wp-universam.ru/" target="_blank" rel="noopener"><?php echo $theme['post_title']; ?></a>
								</div>
								<div class="theme__price">
										<?php echo usam_currency_display( $theme['price'], ['currency' => 'RUB', 'decimal_point' => false]); ?>
									</div>
								<div class="theme__button">
									<?php		
									if( !empty($theme['attr']['demo']) && !empty($theme['attr']['demo']['value'][0]) )				
									{
										?><a class="button button_save" href="<?php echo $theme['attr']['demo']['value'][0]; ?>" class="button-primary button button-next" target="_blank">Demo</a><?php	
									}
									?>
									<a href="https://wp-universam.ru/basket?product_id=<?php echo $theme['ID']; ?>&usam_action=buy_product" class="button-primary button button-next" target="_blank" rel="noopener"><?php esc_html_e( 'Купить', 'usam'); ?></a>
								</div>
							</div>
						<?php } ?>
					</div>	
					<p class="usam_setup-actions step">
						<input type="submit" class="button-primary button button-next" value="<?php esc_attr_e( 'Продолжить установку', 'usam'); ?>" name="save_step">
						<?php wp_nonce_field( 'usam_setup' ); ?>
					</p>					
				</div>
				<div id="free_themes" class = "tab">				
					<h2><?php esc_html_e( 'Использовать бесплатную тему Domino', 'usam'); ?></h2>
					<p class="usam_wizard_theme_intro">
						<?php echo wp_kses_post( __('<strong>Domino</strong> - это бесплатная тема для WordPress, которая разработана и поддерживается создателями Универсам.', 'usam') ); ?>
						<img src="http://wp-universam.ru/wp-content/uploads/sl/downloadables/wp-theme/domino/screenshot.png" alt="domino">
					</p>

					<ul class="usam_wizard_theme_capabilities">
						<li class="usam_wizard_theme first"><?php echo wp_kses_post( __('<strong>Безотказная интеграция:</strong> вы можете не сомневаться в надежности интеграции между Универсам, расширениями Универсам и Domino.', 'usam') ); ?></li>		
						<li class="usam_wizard_theme last"><?php echo wp_kses_post( __('<strong>Оптимизировано для поиска:</strong> Актуальная семантическая разметка для улучшенной поисковой оптимизации.', 'usam') ); ?></li>
					</ul>		
					<p class="usam_setup-actions step">
						<input type="submit" class="button-primary button button-next" value="<?php esc_attr_e( 'Установить и активировать', 'usam'); ?>" name="save_step">
						<a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-next"><?php esc_html_e( 'Пропустить этот шаг', 'usam'); ?></a>
						<?php wp_nonce_field( 'usam_setup' ); ?>
					</p>		
				</div>						
			</div>
		</div>		
		<?php

	}
	
	private function controller_theme_save()
	{		
		wp_schedule_single_event( time(), 'usam_theme_installer', ['domino'] );
	}
	
	public function setup_ready() 
	{		
		?>
		<h1><?php esc_html_e( 'Ваш магазин готов!', 'usam'); ?></h1>
		<p >
			<?php printf( __('Вы можете продолжить настройку в разделе <a href="%s" target="_blank">настройки магазина</a> в любой момент. Вы можете в любой момент задать нам вопрос. Для этого нажмите на &laquo;Центр подержки&raquo; на любой странице.', 'usam'), admin_url('admin.php?page=shop_settings') ); ?>
		</p>		
		<div class="usam_setup-next-steps">
			<div class="usam_setup-next-steps-first">
				<h2><?php esc_html_e( 'Следующие шаги', 'usam'); ?></h2>
				<ul>
					<li class="setup-first"><a class="button button-primary" href="<?php echo admin_url('admin.php?page=help'); ?>" target="_blank"><?php esc_html_e( 'Перейти в платформу', 'usam'); ?></a></li>
					<?php 
					$license = get_option ( 'usam_license', ['name' => '', 'license' => '']);	
					if ( empty($license['license']) ) { ?>
						<li class="setup-first"><a class="button button-primary" href="<?php echo admin_url('index.php?page=usam-license'); ?>" target="_blank"><?php esc_html_e( 'Активируйте лицензию!', 'usam'); ?></a></li>
					<?php } ?>
					<?php 					
					if ( usam_check_current_user_role( 'administrator' ) ) { ?>
						<li class="setup-first"><a class="button" href="<?php echo admin_url('options-general.php?page=shop_settings&tab=admin_menu'); ?>" target="_blank"><?php esc_html_e( 'Включите или отключите возможности', 'usam'); ?></a></li>
					<?php } ?>
					<li class="setup-first"><a class="button" href="<?php echo admin_url('post-new.php?post_type=usam-product'); ?>" target="_blank"><?php esc_html_e( 'Создайте свой первый товар!', 'usam'); ?></a></li>
					<li class="setup-first"><a class="button" href="<?php echo admin_url('admin.php?page=exchange&tab=product_importer'); ?>" target="_blank"><?php esc_html_e( 'Импорт товаров', 'usam'); ?></a></li>					
				</ul>
			</div>			
			<div class="usam_setup-next-steps-last">
				<h2><?php _e( 'Узнать больше', 'usam'); ?></h2>
				<ul>					
					<li class="learn-more"><a target='blank' rel="noopener" href="https://docs.wp-universam.ru/document/using-your-theme"><?php esc_html_e( 'Использование своей темы', 'usam'); ?></a></li>
					<li class="learn-more"><a target='blank' rel="noopener" href="https://docs.wp-universam.ru/document/category/users"><?php esc_html_e( 'Узнать больше о том как начать', 'usam'); ?></a></li>
					<li class="learn-more"><a target='blank' rel="noopener" href="https://wp-universam.ru/capabilities/"><?php esc_html_e( 'Возможности', 'usam'); ?></a></li>
					<li class="newsletter"><a target='blank' rel="noopener" href="https://wp-universam.ru/support/"><?php esc_html_e( 'Задать вопрос', 'usam'); ?></a></li>					
				</ul>
			</div>
		</div>
		<?php
	}
}
new USAM_Admin_Setup_Wizard();
?>