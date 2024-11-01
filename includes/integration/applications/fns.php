<?php
/*
	Name: Федеральная налоговая служба
	Description: Возможность проверки контрагента
	Price: free
	Group: counterparty-verification
*/
require_once( USAM_FILE_PATH . '/includes/application.class.php' );		
class USAM_Application_fns extends USAM_Application
{	
	protected $API_URL = "https://api-fns.ru/api";
	protected $expiration = 432000; //DAY_IN_SECONDS
	public function check_company( $id )
	{ 		
		$string = usam_get_company_metadata( $id, 'ogrn' );			
		if ( $string )
		{
			$registration_data = get_transient( 'check_company_'.$string );
			if ( empty($registration_data) )
			{		
				$args = $this->get_args(['req' => $string]);
				$result = $this->send_request( "check", $args );				
				if ( isset($result['items']) )
				{ 
					set_transient( 'check_company_'.$string, $result['items'][0], $this->expiration );
					return $result['items'][0];
				}
			}
			else				
				return $registration_data;
		}
		return false;
	}	
	
	public function get_company_registration_data( $id )
	{ 
		$string = usam_get_company_metadata( $id, 'ogrn' );	
		if ( $string )
		{	
			$registration_data = get_transient( 'company_registration_data_'.$string );
			if ( empty($registration_data) )
			{ 
				$args = $this->get_args(['req' => $string]);					
				$result = $this->send_request( "egr", $args );			
				if ( isset($result['items']) )
				{ 
					set_transient( 'company_registration_data_'.$string, $result['items'][0], $this->expiration  );
					return $result['items'][0];
				}
			}
			else				
				return $registration_data;
		}
		return false;
	}	
	
	//Бухгалтерская отчетность
	public function get_company_finance( $id )
	{ 
		$string = usam_get_company_metadata( $id, 'ogrn' );	
		$finances = false;
		if ( $string )
		{
			require_once( USAM_FILE_PATH . '/includes/crm/company_finances_query.class.php'); 
			require_once( USAM_FILE_PATH . '/includes/crm/company_finance.php'); 
			$company_finances = usam_get_company_finances(['company_id' => $id]);				
			$year = date("Y");
			$result = true;
			$finances = [];
			foreach ($company_finances as $finance) 
			{
				if ($finance->year == $year)
					$result = false;
				$finances[$finance->year][$finance->code] = $finance->value;
			}
			if ( $result )
			{ 
				$args = $this->get_args(['req' => $string]);					
				$result = $this->send_request( "bo", $args );			
				if ( isset($result[$string]) )
				{ 		
					foreach( $result[$string] as $year => $values)
					{
						foreach( $values as $code => $value) 			
						{
							usam_insert_company_finance(['company_id' => $id, 'year' => $year, 'code' => $code, 'value' => $value]);							
						}
					}
					return $result[$string];
				}
			}
		}
		return $finances;
	}	
	
	public function search_company( $search )
	{ 	
		$args = $this->get_args(['q' => $search]);
		$result = $this->send_request( "search", $args );
		if ( isset($result['items']) )
		{ 
			return $result;
		}
		return false;
	}	

	protected function get_args( $params )
	{ 
		$params['key'] = $this->token;
		$headers["Content-type"] = 'application/json';
		$args = array(
			'method' => 'GET',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => $headers,
			'body' => $params,
		);	
		return $args;
	}
	
	public function filter_general_results_report( $results, $query_vars )
	{
		$id = absint($_POST['id']);
		$registration_data = $this->get_company_finance( $id );	
		if ( !empty($registration_data) )
		{	
			if ( isset($registration_data['ЮЛ']) )
			{
				$timestamp = strtotime($registration_data['ЮЛ']['НО']['РегДата']);
				$results = array( 					
					['title' => esc_html__('Чистая прибыль', 'usam'), 'value' => usam_currency_display( $registration_data['ЮЛ']['ОткрСведения']['СумДоход'] - $registration_data['ЮЛ']['ОткрСведения']['СумРасход'], ['currency' => 'RUB', 'decimal_point' => 0] ) ], 
					['title' => esc_html__('Выручка', 'usam'), 'value' => usam_currency_display( $registration_data['ЮЛ']['ОткрСведения']['СумДоход'], ['currency' => 'RUB', 'decimal_point' => 0] ) ], 
					['title' => esc_html__('Возраст компании', 'usam'), 'value' => human_time_diff( $timestamp, current_time('timestamp') ) ], 
					['title' => esc_html__('Персонал', 'usam'), 'value' => $registration_data['ЮЛ']['ОткрСведения']['КолРаб']] 
				);
			}
			else
			{
				$timestamp = strtotime($registration_data['ИП']['НО']['РегДата']);
				$results = array( 
					array('title' => esc_html__('Возраст компании', 'usam'), 'value' => human_time_diff( $timestamp, current_time('timestamp') ) ), 
					array('title' => esc_html__('Статус', 'usam'), 'value' => $registration_data['ИП']['Статус'] ), 
					array('title' => esc_html__('Код ОКВЭД', 'usam'), 'value' => $registration_data['ЮЛ']['ОснВидДеят']['Код'] ) 
				);
			}
		}
		else	
			$results = [['title' => esc_html__('Чистая прибыль', 'usam'), 'value' => ''], ['title' => esc_html__('Выручка', 'usam'), 'value' => 0], ['title' => esc_html__('Возраст', 'usam'), 'value' => ''], ['title' => esc_html__('Персонал', 'usam'), 'value' => '']];
			return $results;
	}
	
	public function filter_check_company( $results, $id )
	{			
		if ( !$results )
			return $this->check_company( $id );
		else
			return $results;		
	}
	
	public function filter_company_registration_data( $results, $id )
	{
		if ( !$results )
			return $this->get_company_registration_data( $id );	
		else
			return $results;
	}
	
	public function filter_view_form_tabs_company( $tabs, $data )
	{
		$verification = true;
		$financial = true;
		foreach( $tabs as $tab )
		{
			if ( $tab['slug'] == 'counterparty_verification' )
				$verification = false;
			elseif ( $tab['slug'] == 'financial' )
				$financial = false;
		}
		if ( $verification )
			array_splice($tabs, count($tabs)-2, 0, [['slug' => 'counterparty_verification', 'title' => __('Риски','usam')]]);
		if ( $financial )
			array_splice($tabs, count($tabs)-1, 0, [['slug' => 'financial', 'title' => __('Финансы','usam')]]);
		return $tabs;
	}
	
	public function filter_counterparty_verification_report_widgets( $reports, $id )
	{		
		$registration_data = $this->get_company_registration_data( $id );	
		if ( !empty($registration_data) )
		{
			$company_name = isset($registration_data['ЮЛ'])?$registration_data['ЮЛ']['НаимСокрЮЛ']:$registration_data['ИП']['ВидИП'];	
			$reports = [				
				[['title' => $company_name, 'key' => 'counterparty_verification_total', 'view' => 'transparent']],
				[['title' => __('Информация о компании', 'usam'), 'key' => 'information', 'view' => 'data_list'], ['title' => __('Реквизиты', 'usam'), 'key' => 'requisites', 'view' => 'data_list']],		
				[['title' => __('Контакты', 'usam'), 'key' => 'contacts', 'view' => 'data_list'], ['title' => __('Сведения о государственной регистрации', 'usam'), 'key' => 'state_registration', 'view' => 'data_list']],						
				[['title' => __('Факторы риска', 'usam'), 'key' => 'risk_factors', 'view' => 'box']],				
				[['title' => __('Сведения из ФСС', 'usam'), 'key' => 'FSS', 'view' => 'data_list'], ['title' => __('Сведения из ПФ', 'usam'), 'key' => 'registration_insured', 'view' => 'data_list']],
				[['title' => __('Виды деятельности', 'usam'), 'key' => 'kind_activity', 'view' => 'box']],
			];	
			$reports[] = [['title' => __('Позитивная информация', 'usam'), 'key' => 'positive', 'view' => 'data_list'], ['title' => __('Негативная информация', 'usam'), 'key' => 'negative', 'view' => 'data_list']];
			if ($registration_data['ЮЛ'])
			{				
				$reports[] = [['title' => __('Учредители', 'usam'), 'key' => 'founders', 'view' => 'box']];
				if ( isset($registration_data['ЮЛ']['Предшественники']) )
					$reports[] = [['title' => __('Предшественники', 'usam'), 'key' => 'predecessors', 'view' => 'box']];
				if ( isset($registration_data['ЮЛ']['Преемники']) )
					$reports[] = [['title' => __('Преемники', 'usam'), 'key' => 'successors', 'view' => 'box']];
				if ( isset($registration_data['ЮЛ']['УправлОрг']) )
					$reports[] = [['title' => __('Сведения о доверительном управляющем', 'usam'), 'key' => 'trustee_information', 'view' => 'box']];
				if ( isset($registration_data['ЮЛ']['ДержРеестрАО']) )
					$reports[] = [['title' => __('Сведения о держателе реестра акционеров', 'usam'), 'key' => 'shareholder_register_holder', 'view' => 'box']];
				if ( isset($registration_data['ЮЛ']['Участникивреорганизации']) )
					$reports[] = [['title' => __('Участники в реорганизации', 'usam'), 'key' => 'participants_reorganization', 'view' => 'box']];
				if ( isset($registration_data['ЮЛ']['Филиалы']) )
					$reports[] = [['title' => __('Филиалы', 'usam'), 'key' => 'affiliated_societies', 'view' => 'box']];
				if ( isset($registration_data['ЮЛ']['Лицензии']) )
					$reports[] = [['title' => __('Лицензии', 'usam'), 'key' => 'licenses', 'view' => 'box']];
				if ( isset($registration_data['ЮЛ']['Участия']) )
					$reports[] = [['title' => __('Сведения об организациях, в капитале которых участвует компания', 'usam'), 'key' => 'participation', 'view' => 'box']];
				if ( isset($registration_data['ЮЛ']['ОткрСведения']['Налоги']) )					
					$reports[] = [['title' => __('Сведения о уплате налогов', 'usam'), 'key' => 'tax_information', 'view' => 'box']];
			}	
			$reports[] = [['title' => __('Сведения о причинах внесения записей в реестр ЕГРИП', 'usam'), 'key' => 'egryl_records', 'view' => 'box']];
		}	
		return $reports;
	}
	
	public function tab_form_content( $form_name, $tab )
	{ 
		if ( $form_name == 'company' && $tab == 'financial' )
		{
			$codes_names = [__('Бухгалтерский баланс', 'usam'), __('Отчет о финансовых результатах', 'usam'), __('Отчет об изменении капитала', 'usam'), __('Отчет о движении денежных средств', 'usam'), //__('Отчет о целевом использовании полученных средств', 'usam')
			];		
			$id = absint($_GET['id']);
			$finance = $this->get_company_finance( $id );	
			if( $finance )
			{
				foreach ($codes_names as $number => $title)
				{				
					usam_add_box( 'usam_financial_form'.$number, $title, [$this, 'display_financial_form'], ['company_finance' => $finance, 'number' => $number] );
				}
			}
		}
	}
	
	public function display_financial_form( $args )
	{	
		static $codes = null;
		if ( $codes === null )
		{
			$codes = [];	
			foreach (usam_read_txt_file( USAM_FILE_PATH . "/admin/db/db-install/accounting_code.csv", ';') as $code)			
			{
				$codes[$code[1]] = $code[0];
			}
		}
		$codes_names = [
			[1700 => '', 1100 => __('Внеоборотные активы', 'usam'), 1200 => __('Оборотные активы', 'usam'), 1300 => __('Капитал и резервы', 'usam'), 1400 => __('Долгосрочные обязательства', 'usam'), 1500 => __('Краткосрочные обязательства', 'usam')],
			[2200 => __('Доходы и расходы по обычным видам деятельности', 'usam'), 2400 => __('Прочие доходы и расходы', 'usam'), 2500 => __('Совокупный финансовый результат', 'usam')],
			[3327 => __('Итого', 'usam'), 3400 => __('Уставный капитал', 'usam'), 3500 => __('Резервный капитал', 'usam'),

//			3340 => __('Нераспределенная прибыль (непокрытый убыток)', 'usam'), 
3600 => __('Чистые активы', 'usam'), 
//3326 => __('Собственные акции', 'usam'), 3326 => __('Добавочный капитал', 'usam')
],
			[4100 => __('Денежные потоки от текущих операций', 'usam'), 4200 => __('Денежные потоки от инвестиционных операций', 'usam'), 4300 => __('Денежные потоки от финансовых операций', 'usam')],
			[6400 => '', 6200 => __('Поступило средств', 'usam'), 6300 => __('Использовано средств', 'usam')]
		];	
		$thead = true;
		?>
		<table class = "usam_list_table financial_table">				
			<thead>
				<tr>
					<td class="financial_table__name"><?php printf(__('Форма №%s', 'usam'),$args['number']+1); ?></td>
					<td class="financial_table__code"><?php _e('Код', 'usam'); ?></td>
					<?php foreach ($args['company_finance'] as $year => $values) { ?>
						<td class="financial_table__year"><?php echo $year; ?></td>
					<?php } ?>
				</tr>
			</thead>
			<tbody>
		<?php  	
		$colspan = 2 + count($args['company_finance']);
		foreach ($codes_names[$args['number']] as $end_code => $group_name)
		{ 
			if ( $group_name )
			{
				?><tr><td colspan='<?php echo $colspan; ?>'><h3><?php echo $group_name; ?></h3></td></tr><?php
			}					
			foreach ($codes as $code => $name)
			{												
				?>
				<tr class="financial_table__data">
					<td class="financial_table__name"><?php echo $name; ?></td>
					<td class="financial_table__code"><?php echo $code; ?></td>
					<?php 
					foreach ($args['company_finance'] as $year => $values)
					{
						?><td class="financial_table__year"><?php echo isset($values[$code])?usam_currency_display($values[$code], ['decimal_point' => false]):0; ?></td><?php 
					} 
					?>
				</tr>
				<?php
				unset($codes[$code]);
				if ( $end_code == $code )
					break;
			}					
		}	
		?>	
			</tbody>
		</table>	
		<?php	
	}
	
	public function service_load()
	{ 	
		add_filter('usam_general_results_report_counterparty_verification_total', [$this,'filter_general_results_report'], 10, 2);
		add_filter('usam_check_company', [$this,'filter_check_company'], 10, 2);
		add_filter('usam_company_registration_data', [$this,'filter_company_registration_data'], 10, 2);
		add_filter('usam_view_form_tabs_company', [$this,'filter_view_form_tabs_company'], 10, 2);		
		add_filter('usam_counterparty_verification_report_widgets', [$this,'filter_counterparty_verification_report_widgets'], 1, 2);
		add_action( 'usam_tab_form_content', [&$this, 'tab_form_content'], 10, 2 );		
	}
	
	function display_form( ) 
	{
		?>
		<div class="edit_form">
			<div class ="edit_form__item">
				<div class ="edit_form__item_name"><label for='messenger_secret_key'><?php esc_html_e( 'Токен', 'usam'); ?>:</label></div>
				<div class ="edit_form__item_option">
					<?php usam_get_password_input( $this->option['access_token'], ['name' => 'access_token', 'id' => 'messenger_secret_key']); ?>
				</div>
			</div>	
		</div>
		<?php
	}
}
?>