<?php
class USAM_Tab_universam_activation extends USAM_Page_Tab
{
	protected $views = ['simple'];
	public function get_title_tab()
	{
		return __('Активация UNIVERSAM', 'usam');
	}	

	function action_processing() 
	{						
		if ( !empty($_POST['submit_free_license']) ) 
		{
			$api = new USAM_Service_API();
			$api->set_free_license( );
		}
		elseif ( isset($_POST['license_delete']) ) 
		{ 
			update_option('usam_license', ['status' => '', 'type' => '', 'license' => '', 'name' => '', 'domain' => '']);		
		}
		else
		{
			if ( isset($_POST['usam_activation_key']) ) 
			{				
				$license_holder = sanitize_text_field(stripcslashes($_POST['usam_activation_name']));	
				$key = sanitize_title($_POST['usam_activation_key']);
				
				update_option( 'usam_license', ['license' => strtoupper($key), 'name' => $license_holder] );					
				if ( !empty($key) ) 
				{					
					$api = new USAM_Service_API();
					$request = $api->registration(['license' => $key, 'license_holder' => $license_holder]);
					if ( $request )
					{
						$license = get_option('usam_license');		
						$license['type'] = strtoupper($request['type']);
						$license['status'] = $request['status'];			
						if ( !empty($request['date']) )
						{
							$license['date'] = date( "Y-m-d", strtotime($request['date']));
							$license['domain'] = $_SERVER['SERVER_NAME'];
						}
						update_option ( 'usam_license', $license );	
					}							
				}
			}
		}	
	}		
	
	public function display() 
	{				
		$license = get_option ( 'usam_license', [] );	
		if ( !empty($license['status']) && $license['status'] )
			$disabled = 'disabled="disabled"';
		else
			$disabled = '';	
		if ( !empty($license['license']) )
		{
			$api = new USAM_Service_API();
			$_license = $api->get_license();
			if ( $_license )
				$license = array_merge($license, $_license );
		}
		else
			$license = array_merge(['name' => '', 'license' => ''], $license );				
		?>
		<form method='post' action=''>			
			<?php 
			if ( !usam_check_license() )
			{
				?><p><?php printf( esc_html__('Для полноценного использования интернет-магазина введите имя и ключ, который вы получили при покупке. Купить лицензию можно на сайте %s', 'usam'),'<a href="http://wp-universam.ru/buy" target="_blank" rel="noopener">wp-universam.ru</a>'); ?></p><br><br>
				<?php 
			}
			?>					
			<div class="edit_form">
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Тип лицензии', 'usam'); ?>:</div>
					<div class ="edit_form__item_option"><?php echo usam_get_name_type_license(); ?></div>
				</div>
				<?php if ( !empty($license['domain']) ) { ?>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Активирован для домена', 'usam'); ?>:</div>
						<div class ="edit_form__item_option"><?php echo $license['domain']; ?></div>
					</div>
				<?php } ?>	
				<?php if ( !empty($license['date']) ) { ?>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Срок лицензии', 'usam'); ?>:</div>
						<div class ="edit_form__item_option"><?php 
							$day = round((strtotime($license['date']) - time())/(60*60*24));	
							echo sprintf( __('Срок лицензии истекает через %s дней.', 'usam'), $day);
						?></div>
					</div>
				<?php } ?>	
				<?php if ( !empty($license['license_end_date']) ) { ?>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><?php esc_html_e( 'Обновления и поддержка', 'usam'); ?>:</div>
						<div class ="edit_form__item_option">
							<?php $day = round((strtotime($license['license_end_date']) - time())/(60*60*24)); ?>
							<span class="<?php echo $day > 30 ?'item_status_valid':'item_status_attention'; ?> item_status"><?php echo sprintf( _n('заканчивается через %s день', 'заканчивается через %s дней', 'usam', $day), $day); ?></span>
							<?php if ( $day < 6 ){
								?><a href="https://wp-universam.ru/product-category/licenses/"><?php _e('Продлить сейчас', 'usam'); ?></a><?php 
							} ?>	
							</div>
					</div>
				<?php } ?>	
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Ваше имя или название компании', 'usam'); ?>:</div>
					<div class ="edit_form__item_option"><input class='text' type='text' size='40' value='<?php echo esc_attr($license['name']); ?>' <?php echo $disabled; ?> name='usam_activation_name' id='activation_name'></div>
				</div>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><?php esc_html_e( 'Ключ', 'usam'); ?>:</div>
					<div class ="edit_form__item_option"><input class='text' type='text' size='40' value='<?php echo $license['license']; ?>' <?php echo $disabled; ?> name='usam_activation_key' id='activation_key'></div>
				</div>
				<div class ="edit_form__buttons">
					<?php 
					if ( empty($license['license']) ) 
					{
						?>								
						<input type='submit' class='button' value='<?php esc_html_e( 'Запросить лицензию', 'usam'); ?>' name='submit_free_license'>
						<input type='submit' class='button-primary' value='<?php esc_html_e( 'Активировать', 'usam'); ?>' name='submit'>
						<?php 
					}
					else
					{
						?>								
						<input type='submit' class='button' value='<?php esc_html_e( 'Сбросить', 'usam'); ?>' name='license_delete'>
						<?php 
					}	
					?>
				</div>
				
			</div>
			<?php
			global $current_screen;	
			wp_nonce_field('usam-'.$current_screen->id,'usam_name_nonce_field');
			?>			
		</form>
		<?php
	}
}