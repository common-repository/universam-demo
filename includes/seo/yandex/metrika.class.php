<?php
/**
 * Передача данных яндекса
 */
require_once( USAM_FILE_PATH . '/includes/seo/yandex/yandex.class.php' );

class USAM_Yandex_Metrika extends USAM_Yandex
{	
	protected $version = '1';
	protected $format = '';
	protected $url_api = 'https://api-metrika.yandex.net/stat';

		
/*Основные из метрик:
ym:s:visits — количество визитов
ym:s:pageviews — суммарное количество просмотров страниц
ym:s:users — количество уникальных посетителей (за отчетный период)
ym:s:newUsers - Количество новых посетителей.
ym:s:newUserVisitsPercentage - Доля визитов новых посетителей
ym:s:percentNewVisitors - Процент уникальных посетителей, посетивших сайт в отчетном периоде, активность которых включала их самый первый за всю историю накопления данных визит на сайт.
ym:s:bounceRate — показатель отказов
ym:s:pageDepth — глубина просмотра
ym:s:avgVisitDurationSeconds — средняя продолжительность визитов в секундах
ym:s:goal<goal_id>reaches — количество достижений цели, где вместо надо подставить идентификатор цели (зайдите в настройки целей для конкретного счетчика, чтобы получить список их идентификаторов)
ym:s:sumGoalReachesAny — суммарное количество достижений всех целей
*/
	public function get_statistics( $params )
	{	 
		if ( !$this->auth() )
			return [];			//'metrics' => "ym:s:visits,ym:s:pageviews,ym:s:users,ym:s:avgVisitDurationSeconds", 	//	'filters' => "m:pv:datePeriodday"	
		$default = ['date1' => date('Y-m-d', strtotime($params['date1'])), 'date2' => "yesterday", "accuracy" => 'full'];				
		$params = array_merge( $default, $params );
		
		$params['metrics'] = 'ym:s:visits,ym:s:users,ym:s:pageviews,ym:s:percentNewVisitors,ym:s:bounceRate,ym:s:pageDepth,ym:s:avgVisitDurationSeconds,ym:s:newUsers';		 
//		$params['dimensions'] = 'ym:s:lastTrafficSource';
		
		$result = $this->prepare_request( "data/bytime", $params );	
		if ( empty($result) )
			return [];	
		$data = [];
		if ( !empty($result['query']['metrics']) )
		{
			$keys = [];
			foreach ( $result['query']['metrics'] as $key => $value )
			{
				switch ( $value ) 
				{
					case 'ym:s:visits':
						$keys[$key] = 'visits';
					break;
					case 'ym:s:users':
						$keys[$key] = 'users';
					break;
					case 'ym:s:pageviews': //суммарное количество просмотров страниц
						$keys[$key] = 'page_views';
					break;
					case 'ym:s:percentNewVisitors':
						$keys[$key] = 'new_visitors';
					break;
					case 'ym:s:bounceRate':
						$keys[$key] = 'bounce_rate';
					break;	
					case 'ym:s:pageDepth':
						$keys[$key] = 'page_depth';
					break;	
					case 'ym:s:avgVisitDurationSeconds':
						$keys[$key] = 'visit_duration';
					break;	
					case 'ym:s:newUsers':
						$keys[$key] = 'new_users';
					break;		
					case 'ym:s:newUserVisitsPercentage':
						$keys[$key] = 'new_user_visits_percentage';
					break;						
				}		
			}		
			foreach ( $result['time_intervals'] as $i => $value )
			{			
				$statistic = [];
				$statistic['from'] = $value[0];
				$statistic['to'] = $value[1];				
				foreach ( $result['data'][0]['metrics'] as $key => $metric )
				{										
					$statistic[$keys[$key]] = $metric[$i];					
				}			
				$data[] = $statistic;
			}				
		}	
		return $data;
	}
	
	public function auth( )
	{
		if ( empty($this->option['metrika']['counter_id']) )
		{
			$this->set_error('Счетчик не указан');
			return false;
		}		
		return true;
	}
	
	public function get_visits( $date1 = "TODAY", $date2 = "TODAY" )
	{	
		return $this->get_statistics(['preset' => "traffic", 'metrics' => "ym:s:visits", 'date1' => $date1, 'date2' => $date2]);		
	}
	
	protected function prepare_request( $resource, $params = [] )
	{
		if ( empty($params['ids']) )
		{
			$counter_id = !empty($this->option['metrika']['counter_id'])?$this->option['metrika']['counter_id']:0;			
			$params['ids'] = $counter_id;
		}	
		return $this->send_request( $resource, $params );
	}
}
?>