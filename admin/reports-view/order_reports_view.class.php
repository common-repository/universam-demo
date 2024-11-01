<?php
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
class USAM_Order_Reports_View extends USAM_Reports_View
{				
	protected function get_report_widgets( ) 
	{				
		$reports = [			
			[['title' => __('Пути клиента к покупке', 'usam'), 'key' => 'source', 'view' => 'way']],
			[['title' => __('Товары просмотренные во время покупки','usam'), 'key' => 'order_products_viewed', 'view' => 'loadable_table'],['title' => __('Категории просмотренные во время покупки','usam'), 'key' => 'order_category_viewed', 'view' => 'loadable_table']],
			[['title' => __('Аналитика', 'usam'), 'key' => 'order_analytics', 'view' => 'data']],
		];			
		return $reports;
	}	
		
	public function order_products_viewed_report_box()
	{	
		return [__('Товары','usam'), __('Цена','usam')];
	}
	
	public function order_category_viewed_report_box()
	{	
		return [__('Категории','usam'), ''];
	}
	
	public function order_analytics_report_box() 
	{
		?>
		<div class="edit_form" v-if="reports.order_analytics">
			<div class ="edit_form__item" v-if="Object.keys(reports.order_analytics.campaign).length">
				<div class ="edit_form__item_name"><?php esc_html_e( 'Рекламная компания', 'usam'); ?>:</div>
				<div class ="edit_form__item_option"><a :href="'<?php echo admin_url("admin.php?page=marketing&tab=advertising_campaigns&form=view&form_name=advertising_campaign&id="); ?>'+reports.order_analytics.campaign.id"v-html="reports.order_analytics.campaign.title"></a></div>
			</div>				
		</div>	
		<?php
	}
}
?>