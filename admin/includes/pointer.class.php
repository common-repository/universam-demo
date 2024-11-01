<?php
/**
 * Указатели
 */
new USAM_Pointer();
class USAM_Pointer 
{	
	private $version = '';
	public function __construct() 
	{			
		add_action( 'admin_footer', array( $this, 'init' ), 5 );	
	}
	
	/**
	 * Загрузка скриптов
	 */
	public function init( ) 
	{		
		$screen = get_current_screen();		
		$pointer_contents = false;						
		if ( isset($screen->id) )
		{ 					
			switch ( $screen->id ) 
			{				
				case 'orders_orders_table':
					$pointer_contents = [
						['name' => 'main_menu_settings_button', 'position' => 'left', 'header' => __('Управления вкладками','usam'),'content' => __('Вы можете сортировать вкладки и скрывать ненужные, просто потащите любую вкладку сейчас.','usam')],
						['name' => 'button-switch-view', 'position' => 'right', 'header' => __('Варианты просмотра','usam'),'content' => __('Вы просматривать заказы в виде плитки, в виде таблицы или виде отчета.','usam')],	
						['name' => 'usam_settings_handle', 'position' => 'right', 'header' => __('Настройки оформления заказов','usam'),'content' => __('Здесь вы можете изменить статусы заказов, управлять настройками доставок или оплаты заказов и др.','usam')],						
						[ 'name' => 'usam_help_center_handle', 'position' => 'right', 'header' => __('Поиск по документации','usam'),'content' => __('Если у вас есть вопрос, нажмите последнюю кнопку с вопросом и пишите. Вы найдете ответ в базе знаний. Вы также можете написать в техническую поддержку.','usam') ],			
					];					
				break;	
				case 'feedback_chat_table':
					$pointer_contents = [ 	
						['name' => 'usam_settings_handle', 'position' => 'right', 'header' => __('Настройки омногоканальности','usam'),'content' => __('Здесь вы можете подключить мессенджеры и получать информацию в платформу с привязкой к клиенту.','usam')],
					];					
				break;	
				case 'feedback_email_table':
					$pointer_contents = [ 	
						['name' => 'navigation_tab_monitor', 'position' => 'right', 'header' => __('Монитор ваших  посетителей','usam'),'content' => __('Здесь вы можете посмотреть кто на вашем сайте и что смотрит прямо сейчас. Вы можете так же с ними связаться.','usam')],							
					];
				break;	
				case 'bookkeeping_payment_orders_table':
					$pointer_contents = [	
						['name' => 'navigation_tab_payment_orders', 'position' => 'left', 'header' => __('Ваши финансы','usam'),'content' => __('Здесь вы можете оплачивать счета не заходя в клиент-банк и получать информацию платежах, которые прикрепятся к вашим клиентам.','usam')],							
					];
				break;					
			}				
		}
		if ( $pointer_contents != false )
		{ 			
			wp_enqueue_style ('wp-pointer');
			wp_enqueue_script ('wp-pointer');					
			
			$user_pointer = (array)get_user_option( 'usam_pointer' );	
			?>
			<script>
			jQuery(document).ready(function($)
			{					
				// <! [CDATA [ 
				<?php					
					foreach ( $pointer_contents as $key => $pointer ) 
					{
						if ( empty($user_pointer[$screen->id]) || empty($user_pointer[$screen->id][$pointer['name']]) )
						{
							?>								
							$('#<?php echo $pointer['name']; ?>').pointer({
								content: '<div class="usam_pointer"><h3><?php echo $pointer['header']; ?></h3><p><?php echo $pointer['content']; ?></p></div>',
								position: {edge: '<?php echo $pointer['position']; ?>',	align: 'center'},								
								close: function() 
								{											
									<?php
									if ( isset($pointer_contents[$key+1]) )
									{
										?> $('#<?php echo $pointer_contents[$key+1]['name']; ?>').pointer('open'); <?php										
									}	
									?>
									if ( '<?php echo $pointer['name']; ?>' == 'usam_help_center_handle' )
										help_center.open = true;	
									usam_send({action: 'pointer_close', screen: '<?php echo $screen->id; ?>', key: '<?php echo $pointer['name']; ?>', nonce: '<?php echo usam_create_ajax_nonce( 'pointer_close' ); ?>'});										
								}
							});							
							<?php
							if ( $key == 0 )
							{
								?> $('#<?php echo $pointer['name']; ?>').pointer('open'); <?php
							}														
						}
					}
				?>
				//]]> 
			});
			</script>
			<?php
		}
	}
}
?>