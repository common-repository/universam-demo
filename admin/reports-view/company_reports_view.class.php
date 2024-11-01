<?php 
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
class USAM_company_Reports_View extends USAM_Reports_View
{		
	protected function get_report_widgets( ) 
	{				
		$reports = array(
			[['title' => __('Общие показатели', 'usam'), 'key' => 'company_total', 'view' => 'transparent']],		
			[['title' => __('Купленные товары', 'usam'), 'key' => 'purchased_products_company', 'view' => 'loadable_table'], ['title' => __('Записанные звонки', 'usam'), 'key' => 'recorded_calls_company', 'view' => 'loadable_table']],
			[['title' => __('Последний заказ', 'usam'), 'key' => 'company_last_order', 'view' => 'transparent']],
			[[ 'title' => __('Общая статистика по рассылке', 'usam'), 'key' => 'company_results_newsletter', 'view' => 'transparent']],				
			[['title' => __('Списки рассылок', 'usam'), 'key' => 'mailing_lists', 'view' => 'way']],	
			[['title' => __('Отправленные рассылки', 'usam'), 'key' => 'send_newsletters_company', 'view' => 'loadable_table'], ['title' => __('Открытые рассылки', 'usam'), 'key' => 'open_newsletters_company', 'view' => 'loadable_table']],
		);	
		return $reports;		
	}		
	
	public function send_newsletters_company_report_box()
	{	
		return array( __('Название','usam'), __('Дата отправки','usam') );
	}
	
	public function open_newsletters_company_report_box()
	{	
		return array( __('Название','usam'), __('Дата отправки','usam') );
	}
	
	public function recorded_calls_company_report_box()
	{	
		return array( __('Дата','usam'), __('Время','usam') );
	}
	
	public function purchased_products_company_report_box()
	{	
		return array( __('Название','usam'), __('Куплен','usam') );
	}
	
	public function mailing_lists_report_box()
	{
		global $wpdb;
		$properties = usam_get_properties(['type' => 'company', 'active' => 1, 'field_type' => array('email','mobile_phone', 'phone'), 'fields' => 'code']);			
		
		require_once( USAM_FILE_PATH .'/includes/feedback/mailing_list.php' );
		$statistics = (array)$wpdb->get_results( "SELECT meta.meta_key, meta.meta_value, sl.*, stat.*
		FROM ".USAM_TABLE_COMPANY_META." AS meta 
		LEFT JOIN ". USAM_TABLE_SUBSCRIBER_LISTS ." AS sl ON (sl.communication=meta.meta_value) 
		LEFT JOIN ". USAM_TABLE_NEWSLETTER_USER_STAT ." AS stat ON (sl.communication=stat.communication)
		WHERE meta.meta_value!='' AND meta.company_id ='$this->id' AND meta.meta_key IN('".implode("','",$properties)."')");	
		
		$communications = array();
		foreach ( $statistics as $statistic )
		{
			if ( empty($statistic->list) )
			{
				$communications[$statistic->meta_value][0] = array( 'last_departure' => '','opened_at' => 0,'clicked' => 0,'status' => 0, 'sent_at' => 0 );
			}	
			else
			{			
				if ( empty($communications[$statistic->meta_value][$statistic->list]) )
					$communications[$statistic->meta_value][$statistic->list] = array( 'last_departure' => '','opened_at' => 0,'clicked' => 0,'status' => 0, 'sent_at' => 0 );
			
				if ( !empty($statistic->sent_at) )
				{
					$communications[$statistic->meta_value][$statistic->list]['sent_at']++; 			
					if ( empty($communications[$statistic->meta_value][$statistic->list]['last_departure']) || $statistic->sent_at>$communications[$statistic->meta_value][$statistic->list]['last_departure'])
						$communications[$statistic->meta_value][$statistic->list]['last_departure'] = $statistic->sent_at;
				}
				if ( !empty($statistic->opened_at) )
					$communications[$statistic->meta_value][$statistic->list]['opened_at']++; 	
				if ( !empty($statistic->clicked) )
					$communications[$statistic->meta_value][$statistic->list]['clicked']++; 	
				
				$communications[$statistic->meta_value][$statistic->list]['status'] = $statistic->status;
			}
		}	
		if ( $communications )
		{
			?>	
			<div class="tree mailing_lists">
				<?php 
				foreach ( $communications as $communication => $statistics )
				{
					?>	
					<div class="tree__name"><?php echo $communication; ?></div>							
					<div class="tree__results">						
						<div class="tree__results_row">				
							<div class="tree__results_row_item tree__results_row_name"></div>
							<div class="tree__results_row_item"><?php esc_html_e( 'Статус подписки', 'usam'); ?></div>
							<div class="tree__results_row_item"><?php esc_html_e( 'Отправлено', 'usam'); ?></div>
							<div class="tree__results_row_item"><?php esc_html_e( 'Открыто', 'usam'); ?></div>
							<div class="tree__results_row_item"><?php esc_html_e( 'Нажато', 'usam'); ?></div>
							<div class="tree__results_row_item"><?php esc_html_e( 'Последняя отправка', 'usam'); ?></div>
						</div>	
						<?php		
						foreach ( $statistics as $list_id => $statistic )
						{
							$list = usam_get_mailing_list( $list_id );
							?>
							<div class="tree__results_row tree__results_row_statistic">						
								<div class="tree__results_row_item tree__results_row_name"><?php echo $list?$list['name']:__('Не подписан к рассылкам','usam'); ?></div>
								<?php 
								if ( $list ) 		
								{									
									?>
									<div class="tree__results_row_item"><?php echo usam_get_status_name_newsletter($statistic['status']); ?></div>
									<div class="tree__results_row_item tree__results_row_important"><?php echo $statistic['sent_at']; ?></div>
									<div class="tree__results_row_item tree__results_row_important"><?php echo $statistic['opened_at']; ?></div>
									<div class="tree__results_row_item tree__results_row_important"><?php echo $statistic['clicked']; ?></div>
									<div class="tree__results_row_item"><?php echo usam_local_date($statistic['last_departure']); ?></div>
									<?php 
								}							
								?>
							</div>		
							<?php	
						}
						?>						
					</div>										
					<?php	
				}
				?>
			</div>
			<?php 
		}
	}	
}
?>