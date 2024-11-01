<?php
class USAM_Tab_payment_orders extends USAM_Page_Tab
{	
	public function __construct()
	{
		$v = apply_filters('usam_reconciliation_documents', 'act' );
		if ( $v == 'act' )
			$this->blank_slate = true;
		$this->views = ['table', 'report'];
	}
	
	public function get_title_tab()
	{
		return __('Платежи', 'usam');
	}
	
	protected function get_tab_forms()
	{
		if ( !$this->blank_slate )
		{
			if ( $this->table == 'payments_received' )	
				return [['form' => 'edit', 'form_name' => 'payment_received', 'title' => __('Добавить поступление', 'usam'), 'capability' => 'add_payment_received']];		
			else	
				return [['form' => 'edit', 'form_name' => 'payment_order', 'title' => __('Новый платеж', 'usam'), 'capability' => 'add_payment_order']];
		}	
		return [];
	}
	
	public function get_tab_sections() 
	{ 
		$tables = [
			'payment_orders' => ['title' => __('Платежные поручения','usam'), 'type' => 'table', 'capability' => 'view_payment_order'], 
			'payments_received' => ['title' => __('Поступления','usam'), 'type' => 'table',	'capability' => 'view_payment_received'] 
		];			
		return $tables;
	}
	
	public function table_view() 
	{					
		if ( $this->blank_slate )
		{
			$buttons = array( 					
				array( 'title' => __("Открыть счёт в банке &#8220;Точка&#8221;","usam"), 'url' => "https://partner.tochka.com/?referer1=mateikinao" ),
				array( 'title' => __("Открыть ИП или ООО в банке &#8220;Точка&#8221;","usam"), 'url' => "https://partner.tochka.com/all-register-free/?referer1=mateikinao" ),
				array( 'title' => __("Подключить банк","usam"), 'url' => admin_url("admin.php?page=applications&tab=all_applications&s=Точка") ),			
			);
			?>
			<div class="blank_state">
				<img src="https://wp-universam.ru/wp-content/uploads/tochka.png" height='400' width='400' style="border-radius:50px;">
				<h2 class="blank_state__message"><?php _e('Интеграция с банком &#8220;Точка&#8221; позволит получать оплату от клиентов в платформу, привязывать оплаты к клиентам, уведомлять менеджеров об оплате и строить отчеты', 'usam'); ?></h2>				
				<div class="blank_state__buttons">
					<?php
					foreach ( $buttons as $key => $button )
					{											
						?><a href="<?php echo $button['url']; ?>" class="button" target="_blank"><?php echo $button['title']; ?></a><?php 
					}			
					?>			
				</div>
			</div>
			<?php	
		}
		else
		{
			$this->display_tab_sections();	
			$this->list_table->display_table(); 
		}
	}
}
?>