<?php
class USAM_Tab_Debug extends USAM_Page_Tab
{
	protected  $display_save_button = true;
	protected $views = ['simple'];	
	
	public function get_title_tab()
	{			
		return __('Отладка', 'usam');	
	}
		
	protected function action_processing()
    {	
		$user_id = get_current_user_id();
		if (isset($_POST['debug']))
		{					
			$debug = $_POST['debug'];			
			update_user_option( $user_id, 'usam_debug', $debug );	
		}
		if (isset($_POST['usam_show_server_load']))
		{			
			$show_server_load = !empty($_POST['usam_show_server_load'])?1:0;	
			update_user_option( $user_id, 'usam_show_server_load', $show_server_load );
		}
		if (isset($_POST['server_load_log']))
		{			
			$server_load_log = !empty($_POST['server_load_log'])?1:0;			
			update_option( 'usam_server_load_log', $server_load_log );
		}		
		if (isset($_POST['log']))
			update_option( 'usam_log', $_POST['log'] );	
		// Код для включения или отключения страницы отладок
		$cookie_key = 'usam_activate_debug';
		if ( !empty($_POST[$cookie_key]) && !$_COOKIE[$cookie_key] )
		{
			setcookie( $cookie_key, true, USAM_CUSTOMER_DATA_EXPIRATION, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
			$_COOKIE[$cookie_key] = true;
		}
		else
			setcookie( $cookie_key, false, USAM_CUSTOMER_DATA_EXPIRATION, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );			
		
		if ( isset($_POST['usam_stand_service'] ) )
		{
			if ( get_option('usam_stand_service' ) )
				update_option('usam_stand_service', 0 );
			else
				update_option('usam_stand_service', 1 );	
		}
		$this->sendback = add_query_arg( array( 'update' => '1' ), $this->sendback );			
		$this->redirect = true;
	}
	
	public function display_log() 
	{
		?>		
		<table class ="log_service_information">					
			<tr>
				<td><?php esc_html_e( 'Оплата заказов', 'usam'); ?><td>
				<td>
					<input type="hidden" name="log[submit_checkout]" value="0">
					<input type='checkbox' value='1' name='log[submit_checkout]' <?php echo !empty($log['submit_checkout']) ?"checked='checked'":'' ?>/>
				<td>
				<td><?php esc_html_e( 'Записывать, когда пользователь завершает оформление заказа', 'usam'); ?><td>
			</tr>					
		</table>
		<?php
	}
	
	public function display_service_information() 
	{
		$data_default = array( 'location' => 0, 'display' => array( 'sql' => 0, 'globals' => 0  ) );
		$debug = (array)get_user_option( 'usam_debug' );	
		$debug = array_merge($data_default, $debug);
		?>
		<div class ="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_attr_e( 'Отображение', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="radio" name="debug[location]" value="all" <?php if ( $debug['location'] == 'all' ) { echo "checked='checked'"; } ?> /><?php _e('Везде', 'usam'); ?>&nbsp;
					<input type="radio" name="debug[location]" value="admin" <?php if ( $debug['location'] == 'admin' ) { echo "checked='checked'"; } ?> /><?php _e('В адинке', 'usam'); ?>&nbsp;		
					<input type="radio" name="debug[location]" value="site" <?php if ( $debug['location'] == 'site' ) { echo "checked='checked'"; } ?> /><?php _e('На сайте', 'usam'); ?>&nbsp;
					<input type="radio" name="debug[location]" value="0" <?php if ( $debug['location'] == '0' ) { echo "checked='checked'"; } ?> /><?php _e('Нигде', 'usam'); ?>	
					<p class="description"><?php esc_html_e( 'Укажите где показывать служебную информацию.', 'usam'); ?></p>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_attr_e( 'SQL запросы', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="hidden" name="debug[display][sql]" value="0">
					<input type='checkbox' value='1' name='debug[display][sql]' <?php echo $debug['display']['sql'] == 1 ?"checked='checked'":'' ?>/>
					<p class="description"><?php esc_html_e( 'Вывести все SQL запросы.', 'usam'); ?></p>
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_attr_e( 'GLOBALS', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="hidden" name="debug[display][globals]" value="0">
					<input type='checkbox' value='1' name='debug[display][globals]' <?php echo $debug['display']['globals'] == 1 ?"checked='checked'":'' ?>/>
					<p class="description"><?php esc_html_e( 'Вывести содержимое GLOBALS.', 'usam'); ?></p>
				</div>
			</div>
		</div>		
		<?php
	}
	
	public function display() 
	{							
		$show_server_load = get_user_option( 'usam_show_server_load' );	
		$show_server_load = !empty($show_server_load)?1:0;			
		$server_load_log = get_option('usam_server_load_log', 0 );
		?>	
		<div class ="edit_form stand_service">
			<div class ="edit_form__item">								
				<div class ="edit_form__item_name">
					<?php if ( get_option('usam_stand_service', 0 ) == 0 ) { ?>							
						<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Встать на обслуживание', 'usam'); ?>" name="usam_stand_service">
					<?php } else { ?>							
						<input type="submit" class="button" value="<?php esc_attr_e( 'Включить нормальную работу', 'usam'); ?>" name="usam_stand_service">
					<?php } ?>
					<?php $this->nonce_field(); ?>
				</div>
				<div class ="edit_form__item_option">	
					<?php _e( 'Сайт будет закрыт для всех, кроме администраторов сайта.', 'usam'); ?>
				</div>	
			</div>
			<div class ="edit_form__item">									
				<div class ="edit_form__item_name">
					<?php if ( (isset($_COOKIE['usam_activate_debug']) && !$_COOKIE['usam_activate_debug']) || !isset($_COOKIE['usam_activate_debug']) ) { ?>
						<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Включить отладку', 'usam'); ?>" name="usam_activate_debug">
					<?php } else { ?>
						<input type="submit" class="button" value="<?php esc_attr_e( 'Отключить отладку', 'usam'); ?>" name="usam_activate_debug">
					<?php } ?>
					<?php $this->nonce_field(); ?>
				</div>
				<div class ="edit_form__item_option">
					<?php _e( 'Будет включена отладка сайта, и будет доступна техническая информация о текущей работе сайта.', 'usam'); ?>
				</div>	
			</div>	
			<div class ="edit_form__item">						
				<div class ="edit_form__item_name"><?php _e( 'Нагрузка на сервер:', 'usam'); ?></div>
				<div class ="edit_form__item_option">
					<input type="radio" name="usam_show_server_load" value="0" <?php checked($show_server_load, '0' ); ?> /><?php _e('Нет', 'usam'); ?>&nbsp;
					<input type="radio" name="usam_show_server_load" value="1" <?php checked($show_server_load, 1 ); ?> /><?php _e('Да', 'usam'); ?>	
				</div>	
			</div>			
			<div class ="edit_form__item">						
				<div class ="edit_form__item_name"><?php _e( 'Записывать в лог нагрузку на сервер:', 'usam'); ?></div>
				<div class ="edit_form__item_option">
					<input type="radio" name="server_load_log" value="0" <?php checked($server_load_log, '0' ); ?> /><?php _e('Нет', 'usam'); ?>&nbsp;
					<input type="radio" name="server_load_log" value="1" <?php checked($server_load_log, 1 ); ?> /><?php _e('Да', 'usam'); ?>	
				</div>	
			</div>	
		</div>	
		<br>
			<br>
		<?php
		usam_add_box( 'usam_service_information', __('Вывод служебной информации','usam'), array( $this, 'display_service_information' ) );	
	//	usam_add_box( 'usam_log', __('Вести логи','usam'), array( $this, 'display_log' ) );
	}
}