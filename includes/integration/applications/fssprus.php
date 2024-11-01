<?php
/*
	Name: Федеральная служба судебных приставов
	Description: Возможность проверки контрагента	
	Price: free
	Group: counterparty-justice
	Closed: yes
	http://emulators.fssp.tprs.ru/#operation/search_group
*/
require_once( USAM_FILE_PATH . '/includes/application.class.php' );		
class USAM_Application_Fssprus extends USAM_Application
{	
	protected $API_URL = "https://api-ip.fssprus.ru/swagger/api";
	protected $version = "1.0";
	protected $expiration = 432000; //DAY_IN_SECONDS
	public function check_company( $id )
	{ 						
		$tasks = get_transient( 'justice_check_company_turn_'.$id );
		if ( empty($tasks) )
		{
			$string = usam_get_company_metadata( $id, 'company_name' );		
			if ( $string )
			{
				$location_id = usam_get_company_metadata( $id, 'contactlocation' ); 				
				$address = '';
				if ( $location_id )
				{
					$location = usam_get_location( $location_id );		
					$address = 'г. '.$location['name'];	
				}					
				$tasks = [];				
				$params = ['name' => $string, 'address' => $address];				
				$regions = $this->get_regions();				
				$request = [];	
				$i = 0;
				foreach ( $regions as $key => $region )
				{ 
					$params['region'] = $region[0];
					$request[] = array( "type" => 2, 'params' => $params );						
					$i++;	
					if ( $i == 48 )
					{			
						$result = $this->send_request( "search/group", ['request' => $request], "POST" );		
						if ( isset($result['task']) )
						{							
							$tasks[] = $result['task'];								
						}	
					}		
				}	
			}
		}
		$results = [];
		if ( !empty($tasks) )
		{				
			sleep(10);
			$results = $this->get_results( $tasks );		
			if ( !empty($results) )
				delete_transient( 'justice_check_company_turn_'.$id );
			else
				set_transient( 'justice_check_company_turn_'.$id, $results, $this->expiration );	 // Сохранить очередь с данными	
		}
		return $results;
	}	
	
//запрос на получение результата выполнения задачи
	public function get_results( $tasks )
	{ 			
		$results = [];
		foreach ( $tasks as $key => $task )
		{
			$result = $this->get_result( $task );	
			if ( $result )
				$results = array_merge( $results, $result );	
		}
		return $results;
	}		
	
	public function get_result( $task )
	{ 		
		$result = $this->send_request( "result", ['task' => $task]);			
		if ( isset($result['status']) && $result['status'] == 0 )
		{			
			$return = [];
			foreach ( $result['result'] as $key => $items )
			{
				if ( $items['status'] == 0 )
				{
					if ( isset($items['result']) )
					{
						foreach ( $items['result'] as $key => $item )
						{
							$strings = explode(" ",$item['exe_production']);						
							$item['number'] = $strings[0];
							$item['date_insert'] = $strings[2];
							$return[] = $item;
						}
					}
				}
			}			
			return $return;
		}
		return false;
	}		
	
	public function search_ip( $number )
	{ 
		$result = $this->send_request( "search/ip", ['number' => $number]);		
		if ( isset($result['task']) )
		{		
			sleep(10);	
			return $this->get_result( $result['task'] );
		}
		return false;
	}		
	
	public function filter_check_company_justice( $results, $query_vars )
	{
		$id = absint($_POST['id']);	
		$data = get_transient( 'justice_check_company_'.$id );
		if ( empty($data) )
			$data = $this->check_company( $id );
		$results = [];
		if ( !empty($data) )
		{
			foreach ( $data as $result )
			{
				$results[] = ['primary' => "<div class='list_row__name'>".$result['subject']."</div><div class='list_row__description'>№".$result['exe_production']."</div><div class='list_row__description'>".$result['name']."</div>" , 'column' => usam_local_date($result['date_insert'], get_option( 'date_format', 'Y/m/d' )), 'id' => $result['number']];
			}
		}
		return $results;
	}
	
	public function counterparty_verification_reports( $reports, $id )
	{
		$reports[] = [['title' => __('Федеральная служба судебных приставов', 'usam'), 'key' => 'fssp', 'view' => 'loadable_table']];	
		return $reports;
	}
	
	public function filter_view_form_tabs_company( $tabs, $data )
	{
		$add = true;
		foreach( $tabs as $tab )
		{
			if ( $tab['slug'] == 'counterparty_verification' )
			{
				$add = false;
				break;
			}			
		}
		if ( $add )
			array_splice($tabs, count($tabs)-2, 0, [['slug' => 'counterparty_verification', 'title' => __('Риски','usam')]]);
		return $tabs;
	}
	
	public function service_load()
	{ 
		add_filter('usam_load_list_data_fssp', [$this,'filter_check_company_justice'], 10, 2);
		add_filter('filter_counterparty_verification_report_widgets', [$this,'counterparty_verification_reports'], 10, 2);
		add_filter('usam_view_form_tabs_company', [$this,'filter_view_form_tabs_company'], 10, 2);
	}
	
	function display_form( ) 
	{
		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='messenger_secret_key'><?php esc_html_e( 'Токен', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php usam_get_password_input( $this->option['access_token'], array('name' => 'access_token', 'id' => 'messenger_secret_key') ); ?>
				</div>
			</div>	
		</div>
		<?php
	}
	
	protected function send_request( $function, $params, $method = "GET" )
	{
		$params['token'] = $this->token;
		$url = $this->get_url( $function );
	//	$url = $url.'?'.http_build_query($params, null, '&');			
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_POSTFIELDS => json_encode( $params ),
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/json;charset=UTF-8'
			],
		]
		);				
		$data = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);				
		$resp = json_decode($data, true);	
		
		$Log = new USAM_Log_File( 'resp' ); 
$Log->fwrite_array( $url );
$Log->fwrite_array( $data );
$Log->fwrite_array( $err );
$Log->fwrite_array( $resp );

		if ( isset($resp['error']) ) 
		{		
			if ( isset($resp['error'][0]) )
				$this->set_error( $resp['error'][0] );	
			else
				$this->set_error( $resp['error'] );	
			return false;
		}				
		return $resp['response'];	
	}	
	
	protected function get_regions( )	
	{ 
		return array(
			array(1,"УФССП России по Республике Адыгея", "Республика Адыгея"),
			array(2,"УФССП России по Республике Башкортостан", "Республика Башкортостан"),
			array(3,"УФССП России по Республике Бурятия", "Республика Бурятия"),
			array(4,"УФССП России по Республике Алтай", "Республика Алтай"),
			array(5,"УФССП России по Республике Дагестан", "Республика Дагестан"),
			array(6,"УФССП России по Республике Ингушетия", "Республика Ингушетия"),
			array(7,"УФССП России по Кабардино-Балкарской Республике", "Кабардино-Балкарская республика"),
			array(8,"УФССП России по Республике Калмыкия", "Республика Калмыкия"),
			array(9,"УФССП России по Карачаево-Черкесской Республике", "Карачаево-Черкесская республика"),
			array(10,"УФССП России по Республике Карелия", "Республика Карелия"),
			array(11,"УФССП России по Республике Коми", "Республика Коми"),
			array(12,"УФССП России по Республике Марий Эл", "Республика Марий Эл"),
			array(13,"УФССП России по Республике Мордовия", "Республика Мордовия"),
			array(14,"УФССП России по Республике Саха (Якутия)", "Республика Саха (Якутия)"),
			array(15,"УФССП России по Республике Северная Осетия – Алания", "Республика Северная Осетия-Алания"),
			array(16,"УФССП России по Республике Татарстан", "Республика Татарстан"),
			array(17,"УФССП России по Республике Тыва", "Республика Тыва"),
			array(18,"УФССП России по Удмуртской Республике", "Удмуртской республика"),
			array(19,"УФССП России по Республике Хакасия", "Республика Хакасия"),
			array(20,"УФССП России по Чеченской Республике", "Чеченская республика"),
			array(21,"УФССП России по Чувашской Республике", "Чувашская республика"),
			array(22,"УФССП России по Алтайскому краю", "Алтайский край"),
			array(23,"УФССП России по Краснодарскому краю", "Краснодарский край"),
			array(24,"УФССП России по Красноярскому краю", "Красноярский край"),
			array(25,"УФССП России по Приморскому краю", "Приморский край"),
			array(26,"УФССП России по Ставропольскому краю", "Ставропольский край"),
			array(27,"УФССП России по Хабаровскому краю и Еврейской АО", "Алтайский край"),
			array(28,"УФССП России по Амурской области", "Ивановская область"),
			array(29,"УФССП России по Архангельской области и Ненецкому АО", "Ненецкий автономный округ"),
			array(30,"УФССП России по Астраханской области", "Астраханская область"),
			array(31,"УФССП России по Белгородской области", "Белгородская область"),
			array(32,"УФССП России по Брянской области", "Брянская область"),
			array(33,"УФССП России по Владимирской области", "Ивановская область"),
			array(34,"УФССП России по Волгоградской области", "Ивановская область"),
			array(35,"УФССП России по Вологодской области", "Ивановская область"),
			array(36,"УФССП России по Воронежской области", "Ивановская область"),
			array(37,"УФССП России по Ивановской области", "Ивановская область"),
			array(38,"УФССП России по Иркутской области", "Иркутская область"),
			array(39,"УФССП России по Калининградской области", "Калининградская область"),
			array(40,"УФССП России по Калужской области", "Ивановская область"),
			array(41,"УФССП России по Камчатскому краю", "Ивановская область"),
			array(42,"УФССП России по Кемеровской области", "Ивановская область"),
			array(43,"УФССП России по Кировской области", "Ивановская область"),
			array(44,"УФССП России по Костромской области", "Ивановская область"),
			array(45,"УФССП России по Курганской области", "Ивановская область"),
			array(46,"УФССП России по Курской области", "Ивановская область"),
			array(47,"УФССП России по Ленинградской области", "Ивановская область"),
			array(48,"УФССП России по Липецкой области", "Ивановская область"),
			array(49,"УФССП России по Магаданской области", "Ивановская область"),
			array(50,"УФССП России по Московской области", "Ивановская область"),
			array(51,"УФССП России по Мурманской области", "Ивановская область"),
			array(52,"УФССП России по Нижегородской области", "Ивановская область"),
			array(53,"УФССП России по Новгородской области", "Ивановская область"),
			array(54,"УФССП России по Новосибирской области", "Ивановская область"),
			array(55,"УФССП России по Омской области", "Ивановская область"),
			array(56,"УФССП России по Оренбургской области", "Ивановская область"),
			array(57,"УФССП России по Орловской области", "Ивановская область"),
			array(58,"УФССП России по Пензенской области", "Ивановская область"),
			array(59,"УФССП России по Пермскому краю", "Ивановская область"),
			array(60,"УФССП России по Псковской области", "Ивановская область"),
			array(61,"УФССП России по Ростовской области", "Ивановская область"),
			array(62,"УФССП России по Рязанской области", "Ивановская область"),
			array(63,"УФССП России по Самарской области", "Самарская область"),
			array(64,"УФССП России по Саратовской области", "Ивановская область"),
			array(65,"УФССП России по Сахалинской области", "Ивановская область"),
			array(66,"УФССП России по Свердловской области", "Ивановская область"),
			array(67,"УФССП России по Смоленской области", "Ивановская область"),
			array(68,"УФССП России по Тамбовской области", "Ивановская область"),
			array(69,"УФССП России по Тверской области", "Ивановская область"),
			array(70,"УФССП России по Томской области", "Ивановская область"),
			array(71,"УФССП России по Тульской области", "Ивановская область"),
			array(72,"УФССП России по Тюменской области", "Ивановская область"),
			array(73,"УФССП России по Ульяновской области", "Ивановская область"),
			array(74,"УФССП России по Челябинской области", "Ивановская область"),
			array(75,"УФССП России по Забайкальскому краю", "Ивановская область"),
			array(76,"УФССП России по Ярославской области", "Ивановская область"),
			array(77,"УФССП России по Москве", "Ивановская область"),
			array(78,"УФССП России по Санкт-Петербургу", "Ивановская область"),
			array(82,"УФССП России по Республике Крым", "Ивановская область"),
			array(86,"УФССП России по Ханты-Мансийскому АО – Югре", "Ивановская область"),
			array(87,"УФССП России по Чукотскому АО", "Ивановская область"),
			array(89,"ОФССП России по Ямало-Ненецкому АО", "Ивановская область"),
			array(92,"УФССП России по Севастополю", "Ивановская область"),
		);
	}	
}
?>