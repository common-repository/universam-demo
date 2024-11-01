<?php 
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
class USAM_contacts_Reports_View extends USAM_Reports_View
{		
	protected $period = 'last_365_day';	
	protected function get_report_widgets( ) 
	{				
		$reports = array(
			[['title' => __('Общие показатели', 'usam'), 'key' => 'contacts_total', 'view' => 'transparent']],
			[['title' => __('Портрет вашего клиента', 'usam'), 'key' => 'client_portrait', 'view' => 'way']],
			[['title' => __('Распределение по полу', 'usam'), 'key' => 'sex', 'view' => 'graph'],['title' => __('Статусы клиентов', 'usam'), 'key' => 'contacts_statuses', 'view' => 'graph']],
			[['title' => __('Клиенты онлайн', 'usam'), 'key' => 'online_contacts', 'view' => 'loadable_table'],['title' => __('Города клиентов', 'usam'), 'key' => 'city_contacts', 'view' => 'loadable_table']],
			[['title' => __('Динамика роста базы контактов', 'usam'), 'key' => 'contact_base', 'view' => 'graph'], ['title' => __('Количество регистраций', 'usam'), 'key' => 'user_registered', 'view' => 'graph']],	
			[['title' => __('Возраст', 'usam'), 'key' => 'contact_age', 'view' => 'graph']],	
			[['title' => __('Источник','usam'), 'key' => 'contacts_source', 'view' => 'graph'],['title' => __('Количество контактов по менеджерам','usam'), 'key' => 'contacts_load_managers', 'view' => 'graph']],
			[['title' => __('Рейтинг клиентов по количеству обращений', 'usam'), 'key' => 'rating_the_number_requests_contacts', 'view' => 'loadable_table'],['title' => __('Клиенты с низким уровнем удовлетворенности', 'usam'), 'key' => 'low_satisfaction_contacts', 'view' => 'loadable_table']],
		);	
		return $reports;		
	}
	
	public function client_portrait_report_box( ) 
	{
		$query_vars = [ 'meta_query' => [], 'cache_meta' => true ]; 
		$query_vars['meta_query'][] = ['key' => 'birthday', 'value' => '1900-01-01', 'compare' => '>=', 'type' => 'DATE'];
		$query_vars['meta_query'][] = ['relation' => 'OR', ['key' => 'sex', 'value' => 'm', 'compare' => '='], ['key' => 'sex', 'value' => 'f', 'compare' => '=']];	
		if ( isset($_REQUEST['manager']) )
			$query_vars['user_id'] = absint($_REQUEST['manager']);	
		$query_vars['fields'] = 'id';
		$orders = usam_get_orders(['fields' => ['contact_id', 'totalprice'], 'contacts__not_in' => 0]);
		$sum = [];
		$totalprice = 0;
		foreach ( $orders as $order )
		{
			$sum[$order->contact_id] = isset($sum[$order->contact_id])?$sum[$order->contact_id]+$order->totalprice:$order->totalprice;	
			$totalprice += $order->totalprice;
		}
		$contacts = usam_get_contacts( $query_vars );
		$groups = [];
		$groups_totalprice = 0; 
		foreach ( $contacts as $key => $contact_id )
		{	
			$birthday = usam_get_contact_metadata( $contact_id, 'birthday' );
			if ( $birthday )
			{
				$sex = usam_get_contact_metadata($contact_id, 'sex');
				$day = round((current_time('timestamp') - strtotime(get_date_from_gmt( $birthday)))/31536000,0);
				$group = round($day/5,0)*5;
				if ( empty($groups[$group]) )
					$groups[$group] = ['sum' => 0, 'f' => 0, 'm' => 0 ];				
				if ( $sex && isset($groups[$group][$sex]) )
					$groups[$group][$sex]++;				
				if ( !empty($sum[$contact_id]) )
				{
					$groups[$group]['sum'] += $sum[$contact_id];
					$groups_totalprice += $sum[$contact_id];
				}
				unset($contacts[$key]);
			}
			wp_cache_delete( $contact_id, 'usam_contact_meta' );			
		}		
		if ( !empty($groups) )
		{				
			$p = 0;
			$t = 0;
			$contact_profile = ['f' => 0, 'm' => 0, 'from_year' => 0, 'to_year' => 0];
			do 
			{
				$sum = 0;
				$code = 0;				
				foreach ( $groups as $year => $group )
				{
					if( $group['sum'] > $sum )
					{
						$code = $year;
						$sum = $group['sum'];
					}
				}
				$contact_profile['f'] += $groups[$code]['f'];
				$contact_profile['m'] += $groups[$code]['m'];
				if ( $contact_profile['from_year'] )
				{
					if ( $code < $contact_profile['from_year'] )
						$contact_profile['from_year'] = $code;
					elseif ( $code >= $contact_profile['to_year'] )
						$contact_profile['to_year'] = $code+5;
				}
				else
				{
					$contact_profile['from_year'] = $code;
					$contact_profile['to_year'] = $code+5;
				}
				$p = $p+round($groups[$code]['sum']*100/$groups_totalprice, 1);
				$t = $t+round($groups[$code]['sum']*100/$totalprice, 1);
				unset($groups[$code]);				
			} 
			while($p < 70);		
					
			$people = $contact_profile['m'] *100 / ($contact_profile['m'] + $contact_profile['f']);
			?>			
			<div class="contact_profile">				
				<?php if ( $people > 40 && $people < 60 ) { ?>	
					<div class="contact_profile__man"></div><div class="contact_profile__women"></div>
				<?php } elseif ( $contact_profile['m'] > $contact_profile['f'] ) { ?>	
					<div class="contact_profile__man"></div>
				<?php } else { ?>			
					<div class="contact_profile__women"></div>
				<?php } ?>				
				<div class="contact_profile__data">
					<?php if ( $people > 40 && $people < 60 ) { ?>
						<div class="contact_profile__sex"><?php _e("Мужчины и женщины","usam") ?></div><div class="contact_profile__year"><?php printf( __("от %d до %d","usam"),$contact_profile['from_year'],$contact_profile['to_year']); ?></div>
					<?php } elseif ( $contact_profile['m'] > $contact_profile['f'] ) { ?>	
						<div class="contact_profile__sex"><?php _e("Мужчины","usam") ?></div><div class="contact_profile__year"><?php printf( __("от %d до %d","usam"),$contact_profile['from_year'],$contact_profile['to_year']); ?></div>
					<?php } else { ?>			
						<div class="contact_profile__sex"><?php _e("Женщины","usam") ?></div><div class="contact_profile__year"><?php printf( __("от %d до %d","usam"),$contact_profile['from_year'],$contact_profile['to_year']); ?></div>
					<?php } ?>		
					<div class="contact_profile__totalprice"><?php printf( __("%s от продаж","usam"), $p.'%'); ?></div>					
				</div>
			</div>		
			<div class="contact_profile_accuracy"><?php printf( __("Точность %s (соберайте больше данных клиентов)","usam"), $t.'%'); ?></div>			
			<?php 
		}
	}
	
	//Пользователи онлайн
	public function online_contacts_report_box()
	{	
		return array( __('Контакт','usam'), __('Город','usam') );
	}
	
	public function city_contacts_report_box()
	{	
		return [__('Города','usam'), __('Клиентов','usam')];
	}
		
	//Пользователи онлайн
	public function rating_the_number_requests_contacts_report_box()
	{	
		return array( __('Контакт','usam'), __('Обращений','usam') );
	}
	
	public function low_satisfaction_contacts_report_box()
	{	
		return array( __('Контакт','usam'), __('Статус','usam') );
	}	
}
?>