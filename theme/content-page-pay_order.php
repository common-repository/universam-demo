<?php
// Описание: Шаблон выводит подробные сведения выбранного заказа
?>
<div class='pay_the_order'>
<?php
if ( isset($_GET['id']) )	
{	
	$order_id = absint($_GET['id']);	
	$order = new USAM_Order( $order_id );		
	$order_data = $order->get_data();
	if ( !empty($order_data) && !usam_check_object_is_completed($order_data['status'], 'order') )
	{
		$args = array(	'type_price' => $order_data['type_price'] );
		if ( $order_data['paid'] != 2 )
		{
			$pay_up = usam_get_order_metadata($order_data['id'], 'date_pay_up' );
			if ( !empty($pay_up) && strtotime($pay_up) >= time() )
			{						
				$payment_status_sum = $order->get_payment_status_sum();				
				?>
				<h1><?php _e('Оплата заказа','usam'); ?> <span class ='order_id'># <?php echo $order_data['id']; ?></span> <?php _e('на сумму','usam'); ?> <span class ='order_sum'><?php echo usam_get_formatted_price( $order_data['totalprice'], $args ); ?></span></h1>
				<form method='POST' action=''>
					<div class="pay_the_order__name"><?php _e('Информация об оплате','usam'); ?></div>
					
					<div class="detail_amount">
						<div class="detail_amount__blok detail_amount__totalprice">
							<div class="detail_amount__name"><?php _e('Сумма заказа','usam'); ?></div>
							<div class="detail_amount__sum"><?php echo usam_get_formatted_price( $order_data['totalprice'], $args); ?></div>
						</div>
						<div class="detail_amount__blok detail_amount__paid">				
							<div class="detail_amount__name"><?php _e('Оплачено','usam'); ?></div>
							<div class="detail_amount__sum"><?php echo usam_get_formatted_price($payment_status_sum['total_paid'], $args); ?></div>
						</div>
						<div class="detail_amount__blok detail_amount__to_pay">			
							<div class="detail_amount__name"><?php _e('К оплате','usam'); ?></div>
							<div class="detail_amount__sum"><?php echo usam_get_formatted_price( $payment_status_sum['payment_required'], $args); ?></div>
						</div>
					</div>	
					<div class="pay_the_order__name"><?php _e('Доступные способы оплаты','usam'); ?></div>
					<div class = 'view_form pay_the_order__gateways'>
					<?php
					$user_id = get_current_user_id();	
					wp_nonce_field( 'purchase_'.$user_id, 'new_transaction' );			
					$checked = "checked ='checked'";
					require_once(USAM_FILE_PATH.'/includes/document/shipped_documents_query.class.php');
					$documents = usam_get_shipping_documents(['fields' => 'method', 'order' => 'DESC', 'order_id' => $order_id]);	
					$gateways = usam_get_customer_payment_gateways(['shipping' => $documents, 'type' => 'a', 'type_payer' => $order_data['type_payer'], 'products' => usam_get_products_order($order_id)]);			
					if ( !empty($gateways) )
					{
						foreach ($gateways as $gateway) 
						{		
							?>
							<div class="view_form__row">
								<label id='gateway_<?php echo $gateway->id; ?>'><input type='radio' class="option-input radio" id='gateway_<?php echo $gateway->id; ?>' value='<?php echo $gateway->id; ?>' <?php echo $checked; ?> name='gateway'/><?php echo $gateway->name; ?></label>						
							</div>
							<?php
							$checked = "";				
						}
					}
					else
					{
						?>
						<div class="view_form__row">
							<?php _e('Нет доступного способа оплаты','usam'); ?> 
						</div>
						<?php
					}
					$available = true;	
					if ( !$available )
						$disabled = 'disabled="disabled"';
					else
						$disabled = '';
					?>
					</div>
					<div class="pay_the_order__button">			
						<input type="hidden" value="<?php echo $order_id; ?>" name="order_id">		
						<input type="hidden" value="1" name="pay_the_order">		
						<input type="submit" value="<?php _e('Оплатить','usam'); ?>" <?php echo $disabled; ?> name="button" class="button main-button">
					</div>
				</form>
				<?php
			}
			else
			{
				?><h3><?php printf( __('Время оплаты заказа %s на сумму %s вышло.','usam'), $order_data['id'], usam_get_formatted_price( $order_data['totalprice'], $args)); ?></h3><?php	
			}
		}
		else
		{
			?><h3><?php printf( __('Заказ %s на сумму %s оплачен.','usam'), $order_data['id'], usam_get_formatted_price( $order_data['totalprice'], $args)); ?></h3><?php	
		}
	}
	else
	{
		?>
		<div class="empty_page">
			<div class="empty_page__icon"><?php usam_svg_icon('search') ?></div>
			<div class="empty_page__title"><?php  _e('Заказ не найден', 'usam'); ?></div>
		</div>
		<?php	
	}
}
else
{
	?>
	<form method='GET' action='' class="search_block">
		<input type="text" value="" name="id" class="option-input" placeholder="<?php _e('Введите заказ для оплаты','usam'); ?>" autocomplete="off">
		<?php usam_svg_icon('search', 'bt_search');?>
	</form>
	<?php	
}
?>
</div>	