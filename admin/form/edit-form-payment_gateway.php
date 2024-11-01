<?php	
require_once( USAM_FILE_PATH .'/admin/includes/edit_form.class.php' );
class USAM_Form_payment_gateway extends USAM_Edit_Form
{		
	private $gateways = array();
			
	protected function get_title_tab()
	{ 	
		if ( $this->id != null )
			$title = sprintf( __('Изменить способ оплаты &#171;%s&#187;','usam'), $this->data['name'] );
		else
			$title = __('Добавить способ оплаты', 'usam');	
		return $title;
	}
	
	protected function get_data_tab(  )
	{			
		if ( $this->id != null )
		{
			$this->data = usam_get_payment_gateway( $this->id );			
		}
		else		
			$this->data = ['name' => '', 'description' => '', 'currency' => get_option('usam_currency_type'), 'active' => 0, 'img' => '', 'sort' => 10, 'ipn' => 1, 'debug' => 0, 'type' => 'c', 'bank_account_id' => '', 'handler' => ''];	
	}
	
	function display_right()
	{		
		$this->add_box_status_active( $this->data['active'] );		
		$this->display_imagediv( $this->data['img'], __('Миниатюра для способа оплаты', 'usam') );	
	}

	function display_left()
	{			
		$this->titlediv( $this->data['name'] );	
		$this->add_box_description( $this->data['description'] );	
		usam_add_box( 'usam_payment_general', __('Общие настройки', 'usam'), array( $this, 'payment_general' ) );			
		
		if ( $this->data['handler'] != '' )
			usam_add_box( 'usam_payment_handler_settings', __('Настройки обработчика', 'usam'), array( $this, 'payment_handler_settings' ) );		
		
		usam_add_box( 'usam_payment_document', __('Настройки документа оплаты', 'usam'), array( $this, 'payment_document_settings' ) );
		usam_add_box( 'usam_payment_handler_restrictions', __('Ограничения отображения способов оплаты', 'usam'), array( $this, 'payment_handler_restrictions' ) );	
		usam_add_box( 'usam_payment_message_completed', __('Успешная транзакция', 'usam'), array( $this, 'payment_message_completed' ) );
		usam_add_box( 'usam_payment_message_fail', __('Ошибка при оплате', 'usam'), array( $this, 'payment_message_fail' ) );		
    }
	
	private function message_success() 
	{
		ob_start();
		?><h2><?php _e('Спасибо за Ваш заказ в интернет-магазине', 'usam'); ?></h2>
 
		<p><?php _e('Обработка заказа занимает от 1 до 5 рабочих дней. Зарегистрированные пользователи могут просматривать информацию о готовности заказа в личном кабинете.', 'usam'); ?></p>
		 
		<p>Выбранный способ получения: %shipping_method_name%</p>
		<p>[if shipping_method_name=Самовывоз {Забрать из магазина %storage_address%</p>
		<p>График работы %storage_schedule%</p>
		<p>Телефон %storage_phone%</p>
		<p>Как только менеджер обработает заказ, он вам отправит письмо на электронную почту.}]</p>
		<p>Об изменениях статусов заказа Вы будете получать письмо на указанную при оформление заказа, почту.</p>
		 
		<p>Ваш номер заказа: № %order_id%</p>
		 
		 
		<p>Вы заказали эти товары:</p>
		%product_list%
		<p>Стоимость доставки: %total_shipping_currency%</p>
		<p>Итоговая стоимость: %total_price_currency%.</p>
		<?php
		return ob_get_clean();
	}
	
	public function message_fail() 
	{
		ob_start();
		?><h2><?php _e('Оплата заказа завершилось ошибкой', 'usam'); ?></h2>
		 
		<p><?php _e('К сожалению ваш заказ № %order_id% не был оплачен.', 'usam'); ?></p>
		<p><?php printf( __('Возможно на карте не достаточно средств. Повторите оплату или свяжитесь с нами. Нажмите <a href="%s">здесь</a>, чтобы вернуться к странице оформления заказа.', 'usam'), usam_get_url_system_page('checkout')); ?></p>
		<?php
		return ob_get_clean();
	}
	
	function payment_message_completed()
	{		
		usam_list_order_shortcode();
		$message_completed =  usam_get_payment_gateway_metadata( $this->id, 'message_completed' );	
		if ( $this->id === null && !$message_completed )
			$message_completed = $this->message_success();
		wp_editor(stripslashes(str_replace('\\&quot;','',$message_completed )),'usam_message_transaction_message_completed',array(
			'textarea_rows' => 20,
			'textarea_name' => 'message_completed',
			'media_buttons' => false,
			'tinymce' => array( 'theme_advanced_buttons3' => 'invoicefields,checkoutformfields', 'remove_linebreaks' => false )
			)	
		);
	}
	
	function payment_message_fail()
	{		
		usam_list_order_shortcode();
		$message_fail = usam_get_payment_gateway_metadata( $this->id, 'message_fail' );
		if ( $this->id === null && !$message_fail )
			$message_fail = $this->message_fail();		
		wp_editor(stripslashes(str_replace('\\&quot;','',$message_fail )),'usam_message_transaction_message_fail',array(
			'textarea_rows' => 20,
			'textarea_name' => 'message_fail',
			'media_buttons' => false,
			'tinymce' => array( 'theme_advanced_buttons3' => 'invoicefields,checkoutformfields', 'remove_linebreaks' => false )
			)	
		);
	}
	
	function payment_document_settings()
	{					
		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_code'><?php esc_html_e( 'Расчетный счет', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php usam_select_bank_accounts( $this->data['bank_account_id'], array('name' => 'payment_gateway[bank_account_id]') ) ?>
				</div>
			</div>
		</div>	
		<?php
    }				
	
	function payment_general()
	{			
		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for="option_sort"><?php esc_html_e( 'Сортировка', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<input type="text" id="option_sort" name="payment_gateway[sort]" maxlength = "3" size = "3" value="<?php echo $this->data['sort']; ?>" autocomplete="off"/>		
				</div>
			</div>
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label><?php esc_html_e( 'Отладка', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<label><input type="radio" name="payment_gateway[debug]" value="1" <?php checked($this->data['debug'], 1) ?>>
					<?php _e('Да', 'usam'); ?></label>&nbsp;&nbsp;
					<label><input type="radio" name="payment_gateway[debug]" value="0" <?php checked($this->data['debug'], 0); ?>/><?php _e('Нет', 'usam'); ?></label>
					<p class="description"><?php esc_html_e( 'Включить режим отладку', 'usam'); ?></p>
				</div>
			</div>			
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='option_handler'><?php esc_html_e( 'Обработчик', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<select name='payment_gateway[handler]' id='option_handler'>		
						<option value=''><?php esc_html_e( 'По умолчанию', 'usam'); ?></option>
						<?php	
						$gateways = usam_get_integrations( 'merchants' );	
						foreach ( $gateways as $code => $name ) 
						{										
							echo "<option ".selected($this->data['handler'], $code, false)." value='$code'>".$name."</option>";	
						}	
						?>
					</select>
				</div>
			</div>
		</div>	
		<?php
		$merchant_instance = usam_get_merchant_class( $this->data );
		?><input type='hidden' value='<?php echo $merchant_instance->get_type_operation(); ?>' name='payment_gateway[type]'/><?php
    }	
	
	function payment_handler_settings()
	{	
		$merchant_instance = usam_get_merchant_class( $this->data );		
		$user_account_url = $merchant_instance->get_user_account_url();	
		$form = $merchant_instance->get_form( );
		if ( $form )
		{
			?>			
			<div class="edit_form">
				<?php
				if ( $user_account_url )
				{	?>	
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label><?php esc_html_e( 'Кабинет пользователя', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<a href="<?php echo $user_account_url; ?>" target="_blank"><?php _e('Перейти в кабинет', 'usam'); ?></a>
						</div>
					</div>
					<?php
				}
				if ( $merchant_instance->get_ipn() )
				{ 	?>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label><?php esc_html_e( 'Уведомления платежей', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option">
							<input for='ipn1' type='radio' value='1' name='payment_gateway[ipn]' <?php checked(!empty($this->data['ipn'])) ?>> <label for='ipn1'><?php _e('Да', 'usam') ?></label> &nbsp;
							<input for='ipn2' type='radio' value='0' name='payment_gateway[ipn]' <?php checked(empty($this->data['ipn'])) ?>> <label for='ipn2'><?php _e('Нет', 'usam') ?></label>
							<p class="description"><?php _e( "Система будет автоматически обновлять заказы, когда оплата завершена успешно. Если не включен, статус заказов автоматически меняться не будет.", 'usam') ?></p>
						</div>
					</div>
					<div class ="edit_form__item">
						<div class ="edit_form__item_name"><label for='option_code'><?php esc_html_e( 'Ссылка для обратного вызова', 'usam'); ?>:</label></div>
						<div class ="edit_form__item_option"><span class="js-copy-clipboard"><?php echo usam_get_url_system_page('transaction-results').'/notification/'.$this->id; ?></span></div>
					</div>		
				<?php				
				}
				?>
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='option_code'><?php esc_html_e( 'Ссылка при успешном платеже', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option"><span class="js-copy-clipboard"><?php echo usam_get_url_system_page('transaction-results').'/success/'.$this->id; ?></span></div>
				</div>	
				<div class ="edit_form__item">
					<div class ="edit_form__item_name"><label for='option_code'><?php esc_html_e( 'Ссылка при ошибке', 'usam'); ?>:</label></div>
					<div class ="edit_form__item_option"><span class="js-copy-clipboard"><?php echo usam_get_url_system_page('transaction-results').'/fail/'.$this->id; ?></span></div>
				</div>	
				<?php echo $form; ?>
			</div>						
			<?php
		}		
    }	
	
	function payment_handler_restrictions()
	{			
		$shipping = usam_get_array_metadata( $this->id, 'payment_gateway', 'shipping' );
		$roles = usam_get_array_metadata( $this->id, 'payment_gateway', 'roles' );
		$sales_area = usam_get_array_metadata( $this->id, 'payment_gateway', 'sales_area' );
		$types_payers = usam_get_array_metadata( $this->id, 'payment_gateway', 'types_payers' );
		$units = usam_get_array_metadata( $this->id, 'payment_gateway', 'units' );		
		$brands = usam_get_array_metadata( $this->id, 'payment_gateway', 'brands' );
		$category = usam_get_array_metadata( $this->id, 'payment_gateway', 'category' );
		
		$this->checklist_meta_boxs(['roles' => $roles, 'units' => $units, 'brands' => $brands, 'category' => $category, 'selected_shipping' => $shipping, 'types_payers' => $types_payers, 'sales_area' => $sales_area]); 
    }
}
?>