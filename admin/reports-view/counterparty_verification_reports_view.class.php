<?php 
require_once( USAM_FILE_PATH . '/admin/includes/reports-view.class.php' );
class USAM_Counterparty_Verification_Reports_View extends USAM_Reports_View
{		
	private $registration_data = [];
	private $currency_args = ['currency' => 'RUB', 'decimal_point' => 0];	
	
	protected function get_report_widgets( ) 
	{	
		$this->registration_data = apply_filters( 'usam_company_registration_data', [], $this->id );		
		return apply_filters( 'usam_counterparty_verification_reports', $reports, $this->id );
	}	
		
	public function contacts_report_box( ) 
	{		
		$registration_data = isset($this->registration_data['ЮЛ'])?$this->registration_data['ЮЛ']:$this->registration_data['ИП'];
		$results = array();
		if( isset($registration_data['E-mail']) )
			$results[] = array( 'title' => __('Эл. почта', 'usam'), 'value' => $registration_data['E-mail'] );		
		if( isset($registration_data['НомТел']) )
			$results[] = array( 'title' => __('Номер телефона', 'usam'), 'value' => $registration_data['НомТел'] );
		return $results;
	}
	
	public function requisites_report_box( ) 
	{				
		$results = array();
		if( isset($this->registration_data['ИП']) ) 
		{
			$results[] = array( 'title' => __('ФИО индивидуального предпринимателя', 'usam'), 'value' => $this->registration_data['ИП']['ФИОПолн'] );			
			$results[] = array( 'title' => __('Адрес', 'usam'), 'value' => $this->registration_data['ИП']['Адрес']['Индекс'].', '.$this->registration_data['ИП']['Адрес']['АдресПолн'] );
			$results[] = array( 'title' => __('ОГРН ИП', 'usam'), 'value' => $this->registration_data['ИП']['ОГРНИП'] );
			$results[] = array( 'title' => __('Способ образования', 'usam'), 'value' => $this->registration_data['ИП']['СпОбрЮЛ'] );
			$results[] = array( 'title' => __('Пол', 'usam'), 'value' => $this->registration_data['ИП']['Пол'] );
			$results[] = array( 'title' => __('Вид гражданства', 'usam'), 'value' => $this->registration_data['ИП']['ВидГражд'] );
			$results[] = array( 'title' => __('ИНН физического лица', 'usam'), 'value' => $this->registration_data['ИП']['ИННФЛ'] );	
		}
		else
		{
			$results[] = array( 'title' => __('Наименование полное', 'usam'), 'value' => $this->registration_data['ЮЛ']['НаимПолнЮЛ'] );	
			$results[] = array( 'title' => __('Наименование краткое', 'usam'), 'value' => $this->registration_data['ЮЛ']['НаимСокрЮЛ'] );	
			$results[] = array( 'title' => __('ОКОПФ', 'usam'), 'value' => $this->registration_data['ЮЛ']['ОКОПФ'] );			
			$results[] = array( 'title' => __('Адрес', 'usam'), 'value' => $this->registration_data['ЮЛ']['Адрес']['Индекс'].', '.$this->registration_data['ЮЛ']['Адрес']['АдресПолн'] );			
			$results[] = array( 'title' => __('ИНН', 'usam'), 'value' => $this->registration_data['ЮЛ']['ИНН'] );
			$results[] = array( 'title' => __('КПП', 'usam'), 'value' => $this->registration_data['ЮЛ']['КПП'] );
			$results[] = array( 'title' => __('ОГРН', 'usam'), 'value' => $this->registration_data['ЮЛ']['ОГРН'] );
			$results[] = array( 'title' => __('Способ образования', 'usam'), 'value' => $this->registration_data['ЮЛ']['СпОбрЮЛ'] );
		}		
		return $results;
	}	
	
	public function information_report_box( ) 
	{				
		$registration_data = isset($this->registration_data['ЮЛ'])?$this->registration_data['ЮЛ']:$this->registration_data['ИП'];
		$results = array();
		if( isset($registration_data['ДатаПрекр']) ) 
		{
			$results[] = array( 'title' => __('Дата прекращения деятельности', 'usam'), 'value' => date_i18n(get_option( 'date_format', 'Y/m/d' ),strtotime($registration_data['ДатаПрекр'])) );	
		}
		if( isset($registration_data['Статус']) ) 
		{
			$results[] = array( 'title' => __('Статус компании', 'usam'), 'value' => $registration_data['Статус'] );	
		}
		if( isset($this->registration_data['ЮЛ']) ) 
		{ 	
			$results[] = array( 'title' => __('Руководитель', 'usam'), 'value' => $this->registration_data['ЮЛ']['Руководитель']['ФИОПолн'] );		
			if( isset($this->registration_data['ЮЛ']['Учредители']) ) 				
			{ 
				$founders = array();					
				$minor = false;
				foreach ( $this->registration_data['ЮЛ']['Учредители'] as $data )
				{
					if ( $data['Процент'] >= 25 )
					{
						if( isset($data['УчрЮЛ']) ) 
						{ 
							$founders[] = $data['УчрЮЛ']['НаимСокрЮЛ'];
						}
						elseif( isset($data['УчрИН']) ) 
						{ 
							$founders[] = $data['УчрИН']['НаимПолнЮЛ'];
						}
						elseif( isset($data['УчрФЛ']) ) 
						{ 
							$founders[] = $data['УчрФЛ']['ФИОПолн'];
						}
						elseif( isset($data['СвОргОсущПр']) ) 
						{ 
							$founders[] = $data['СвОргОсущПр']['УчрЮЛ']['НаимСокрЮЛ'];
						}
						elseif( isset($data['УчрПИФ']) ) 
						{ 
							$founders[] = $data['УчрПИФ']['НаимСокрЮЛ'];
						}
						elseif( isset($data['Залогодержатели']) ) 
						{ 
							$founders[] = $data['Залогодержатели']['НаимСокрЮЛ'];
						}										
					}		
					else
						$minor = true;
				} 
				$text = $minor?__('Основные учредители', 'usam'):__('Учредители', 'usam');
				$results[] = array( 'title' => $text, 'value' => implode(', ',$founders) );		
			}
			if( isset($this->registration_data['ЮЛ']['Капитал']['ВидКап']) ) 	
				$results[] = array( 'title' => __('Вид капитала', 'usam'), 'value' => $this->registration_data['ЮЛ']['Капитал']['ВидКап'] );	
			if( isset($this->registration_data['ЮЛ']['Капитал']['СумКап']) ) 	
				$results[] = array( 'title' => __('Размер капитала', 'usam'), 'value' => $this->registration_data['ЮЛ']['Капитал']['СумКап'] );			
			if( isset($this->registration_data['ЮЛ']['ОткрСведения']['СведСНР']) ) 	
				$results[] = array( 'title' => __('Налоговый режим', 'usam'), 'value' => $this->registration_data['ЮЛ']['ОткрСведения']['СведСНР'] );		
			if( isset($this->registration_data['ЮЛ']['ОткрСведения']['ПризнУчКГН']) ) 	
				$results[] = array( 'title' => __('Участие в консолидированной группе налогоплательщиков', 'usam'), 'value' => $this->registration_data['ЮЛ']['ОткрСведения']['ПризнУчКГН'] );				
			if( isset($this->registration_data['ЮЛ']['Филиалы']) )
				$results[] = array( 'title' => __('Количество филиалов', 'usam'), 'value' => count($this->registration_data['ЮЛ']['Филиалы']) );	
			else
				$results[] = array( 'title' => __('Филиалы', 'usam'), 'value' => __("нет","usam") );				
			if( isset($this->registration_data['ЮЛ']['ОткрСведения']['ОтраслевыеПок']) )
			{
				if( isset($this->registration_data['ЮЛ']['ОткрСведения']['ОтраслевыеПок']['НалогНагрузка']) )
				{
					$results[] = array( 'title' => __('Налоговая нагрузка', 'usam'), 'value' => $this->registration_data['ЮЛ']['ОткрСведения']['ОтраслевыеПок']['НалогНагрузка'] );	
				}
				if( isset($this->registration_data['ЮЛ']['ОткрСведения']['ОтраслевыеПок']['Рентабельность']) )
				{
					$results[] = array( 'title' => __('Рентабельность', 'usam'), 'value' => $this->registration_data['ЮЛ']['ОткрСведения']['ОтраслевыеПок']['Рентабельность'] );	
				}
			}
		} 		
		return $results;
	}
	
	public function state_registration_report_box( ) 
	{				
		$registration_data = isset($this->registration_data['ЮЛ'])?$this->registration_data['ЮЛ']['НО']:$this->registration_data['ИП']['НО'];	
		$results =  array(		
			array( 'title' => __('Дата регистрации', 'usam'), 'value' => date_i18n(get_option( 'date_format', 'Y/m/d' ),strtotime($registration_data['РегДата'])) ),
			array( 'title' => __('Регистрирующий орган', 'usam'), 'value' => $registration_data['Рег'] ),
			array( 'title' => __('Налоговый орган, в котором состоит на учете', 'usam'), 'value' => $registration_data['Учет'] ),
			array( 'title' => __('Дата постановки на учет', 'usam'), 'value' => date_i18n(get_option( 'date_format', 'Y/m/d' ),strtotime($registration_data['УчетДата'])) ),			
		);	
		return $results;
	}		
	
	public function kind_activity_report_box()
	{
		$registration_data = isset($this->registration_data['ЮЛ'])?$this->registration_data['ЮЛ']:$this->registration_data['ИП'];			
		?>	
		<table class = "usam_list_table">		
			<tbody>
			<tr>
				<td colspan='2'><h3><?php _e('Основной вид деятельности по ОКВЭД', 'usam'); ?></h3></td>
			</tr>
			<tr>
				<td><?php echo $registration_data['ОснВидДеят']['Код']; ?></td>
				<td><?php echo $registration_data['ОснВидДеят']['Текст']; ?></td>
			</tr>
			<tr>
				<td colspan='2'><h3><?php _e('Дополнительные виды деятельности', 'usam'); ?></h3></td>				
			</tr>
			<?php  			
			foreach ( $registration_data['ДопВидДеят'] as $data )
			{
				?>	
				<tr>
					<td><?php echo $data['Код']; ?></td>
					<td><?php echo $data['Текст']; ?></td>
				</tr>
				<?php  				
			}
			?>	
			</tbody>
		</table>	
		<?php			
	}	
		
	function risk_factors_report_box()
	{	
		
	}	
	
	public function positive_report_box( ) 
	{				
		$results = [];
		$result_check = apply_filters( 'usam_check_company', [], $this->id );		
		if ( $result_check )
		{
			$result_check = isset($result_check['ЮЛ'])?$result_check['ЮЛ']:$result_check['ИП'];			
			if( isset($result_check['Позитив']['РеестрМСП']['ДатаВклМСП']) )
				$results[] = ['title' => __('Дата включение в реестр','usam'), 'value' => date_i18n(get_option( 'date_format', 'Y/m/d' ),strtotime($result_check['Позитив']['РеестрМСП']['ДатаВклМСП']))];		
			if( isset($$result_check['Позитив']['РеестрМСП']['КатСубМСП']) )
				$results[] = array( 'title' => __('Тип предприятия','usam'), 'value' => $result_check['Позитив']['РеестрМСП']['КатСубМСП'] );	
			if( isset($$result_check['Позитив']['РеестрМСП']['ПризНовМСП']) )
				$results[] = array( 'title' => __('Признак создания','usam'), 'value' => $result_check['Позитив']['РеестрМСП']['ПризНовМСП'] );	
			if( isset($$result_check['Позитив']['Лицензии']) )
				$results[] = array( 'title' => __('Лицензии','usam'), 'value' => $result_check['Позитив']['Лицензии'] );	
			if( isset($$result_check['Позитив']['КапБолее50тыс']) )
				$results[] = array( 'title' => __('Капитал более 50 тысяч','usam'), 'value' => $result_check['Позитив']['КапБолее50тыс'] );	
			if( isset($$result_check['Позитив']['Текст']) )
				$results[] = array( 'title' => __('Информация','usam'), 'value' => $result_check['Позитив']['Текст'] );	
		}
		return $results;
	}	
	
	public function negative_report_box( ) 
	{				
		$results = [];
		$result_check = apply_filters( 'usam_check_company', [], $this->id );
		if ( $result_check )
		{
			$result_check = isset($result_check['ЮЛ'])?$result_check['ЮЛ']:$result_check['ИП'];	
			if( isset($$result_check['Негатив']['МассРук']) )
				$results[] = array( 'title' => __('Массовые юридические лица','usam'), 'value' => $result_check['Негатив']['МассРук'] );		
			if( isset($$result_check['Негатив']['РукЛиквКомп']) )
				$results[] = array( 'title' => __('Ликвидированные компании','usam'), 'value' => $result_check['Негатив']['РукЛиквКомп'] );	
			if( isset($$result_check['Негатив']['КолРаб']) )
				$results[] = array( 'title' => __('Количество рабочих','usam'), 'value' => $result_check['Негатив']['КолРаб'] );	
			if( isset($$result_check['Негатив']['НедоимкаНалог']) )
				$results[] = array( 'title' => __('Недоимка налога','usam'), 'value' => $result_check['Негатив']['НедоимкаНалог'] );	
			if( isset($$result_check['Негатив']['БлокСчета']) )
				$results[] = array( 'title' => __('Блокированные счета','usam'), 'value' => $result_check['Негатив']['БлокСчета'] );	
			if( isset($$result_check['Негатив']['Банкрот']) )
				$results[] = array( 'title' => __('Банкрот','usam'), 'value' => $result_check['Негатив']['Банкрот'] );	
			if( isset($$result_check['Негатив']['Текст']) )
				$results[] = array( 'title' => __('Информация','usam'), 'value' => $result_check['Негатив']['Текст'] );
		}
		return $results;
	}	
	
	public function registration_insured_report_box( ) 
	{				
		$registration_data = isset($this->registration_data['ЮЛ'])?$this->registration_data['ЮЛ']['ПФ']:$this->registration_data['ИП']['ПФ'];		
		$results =  array(		
			array( 'title' => __('Номер в ПФ', 'usam'), 'value' => $registration_data['РегНомПФ'] ),
			array( 'title' => __('Территориальный орган ПФ', 'usam'), 'value' => $registration_data['КодПФ'] ),
			array( 'title' => __('Дата регистрации в качестве страхователя', 'usam'), 'value' =>  date_i18n(get_option( 'date_format', 'Y/m/d' ),strtotime($registration_data['ДатаРегПФ'])) ),			
		);	
		return $results;
	}		
	
	public function FSS_report_box( ) 
	{				
		$registration_data = isset($this->registration_data['ЮЛ'])?$this->registration_data['ЮЛ']['ФСС']:$this->registration_data['ИП']['ФСС'];		
		$results =  array(		
			array( 'title' => __('Номер в ФСС', 'usam'), 'value' => $registration_data['РегНомФСС'] ),
			array( 'title' => __('Исполнительный орган ФСС', 'usam'), 'value' => $registration_data['КодФСС'] ),
			array( 'title' => __('Дата регистрации в качестве страхователя', 'usam'), 'value' =>  date_i18n(get_option( 'date_format', 'Y/m/d' ),strtotime($registration_data['ДатаРегФСС'])) ),			
		);	
		return $results;
	}
	
	function founders_report_box()
	{		
		?>		
		<table class = "usam_list_table">					
			<thead>
				<tr>
					<td><?php _e('Учредитель', 'usam'); ?></td>
					<td><?php _e('Стоимость доли', 'usam'); ?></td>
					<td><?php _e('Стоимость доли %', 'usam'); ?></td>
					<td><?php _e('Дата внесения в ЕГРЮЛ', 'usam'); ?></td>
				</tr>
			</thead>
			<tbody>
			<?php  			
			foreach ( $this->registration_data['ЮЛ']['Учредители'] as $data )
			{				
				?>	
				<tr>
					<?php if( isset($data['УчрЮЛ']) ) { ?>	
						<td>
							<div class="view_data">		
								<div class ="view_data__row">
									<div class ="view_data__name"><?php _e('Юридическое лицо', 'usam'); ?>:</div>
									<div class ="view_data__option">
										<div class = 'crm_customer'><?php echo $data['УчрЮЛ']['НаимСокрЮЛ']." (".__("ИНН","usam").': '.$data['УчрЮЛ']['ИНН'].")"; ?>
											<div class='crm_customer__info'>
												<div class='crm_customer__info_rows'>
													<div class='crm_customer__info_row'>
														<div class='crm_customer__info_row_name'><?php _e('ИНН', 'usam'); ?></div>
														<div class='crm_customer__info_row_option'><?php echo $data['УчрЮЛ']['ИНН']; ?></div>
													</div>
													<div class='crm_customer__info_row'>
														<div class='crm_customer__info_row_name'><?php _e('ОГРН', 'usam'); ?></div>
														<div class='crm_customer__info_row_option'><?php echo $data['УчрЮЛ']['ОГРН']; ?></div>
													</div>
													<div class='crm_customer__info_row'>
														<div class='crm_customer__info_row_name'><?php _e('Статус', 'usam'); ?></div>
														<div class='crm_customer__info_row_option'><?php echo $data['УчрЮЛ']['Статус']; ?></div>
													</div>
												</div>
											</div>
										</div>
									</div>																
								</div>	
							</div>	
						</td>						
					<?php } elseif( isset($data['УчрИН']) ) { ?>	
						<td>
							<div class="view_data">		
								<div class ="view_data__row">
									<div class ="view_data__name"><?php _e('Иностранное юридическое лицо', 'usam'); ?>:</div>
									<div class ="view_data__option">
										<div class = 'crm_customer'><?php echo $data['УчрИН']['НаимПолнЮЛ'] ?>
											<div class='crm_customer__info'>
												<div class='crm_customer__info_rows'>
													<div class='crm_customer__info_row'>
														<div class='crm_customer__info_row_name'><?php _e('Код ОКСМ', 'usam'); ?></div>
														<div class='crm_customer__info_row_option'><?php echo $data['УчрИН']['ОКСМ']; ?></div>
													</div>
													<div class='crm_customer__info_row'>
														<div class='crm_customer__info_row_name'><?php _e('Регистрационный номер', 'usam'); ?></div>
														<div class='crm_customer__info_row_option'><?php echo $data['УчрИН']['РегНомер']; ?></div>
													</div>													
													<div class='crm_customer__info_row'>
														<div class='crm_customer__info_row_name'><?php _e('Дата регистрации', 'usam'); ?></div>
														<div class='crm_customer__info_row_option'><?php echo date_i18n(get_option( 'date_format', 'Y/m/d' ),strtotime($data['УчрИН']['ДатаРег'])); ?></div>
													</div>
													<div class='crm_customer__info_row'>
														<div class='crm_customer__info_row_name'><?php _e('Адрес в стране происхождения', 'usam'); ?></div>
														<div class='crm_customer__info_row_option'><?php echo $data['УчрИН']['АдресПолн']; ?></div>
													</div>
												</div>
											</div>
										</div>
									</div>																
								</div>	
							</div>	
						</td>							
					<?php } elseif( !empty($data['УчрФЛ']) ) { ?>	
						<td>
							<div class="view_data">		
								<div class ="view_data__row">
									<div class ="view_data__name"><?php _e('Физ. лицо', 'usam'); ?>:</div>
									<div class ="view_data__option"><?php echo $data['УчрФЛ']['ФИОПолн'].(!empty($data['УчрФЛ']['ИННФЛ'])?" (".__("ИНН","usam").': '.$data['УчрФЛ']['ИННФЛ'].")":""); ?></div>
								</div>	
							</div>	
						</td>
					<?php } elseif( isset($data['УчрРФСубМО']) ) { ?>	
						<td>
							<div class="view_data">		
								<div class ="view_data__row">
									<div class ="view_data__name"><?php _e('Государство', 'usam'); ?>:</div>
									<div class ="view_data__option"><?php echo $data['УчрРФСубМО']; ?></div>																
								</div>	
							</div>	
						</td>
					<?php } elseif( isset($data['СвОргОсущПр']) ) { ?>	
						<td>
							<div class="view_data">		
								<div class ="view_data__row">
									<div class ="view_data__name"><?php _e('Орган государственной власти, органе местного самоуправления или о юридическом лице, осуществляющем права учредителя', 'usam'); ?>:</div>
									<div class ="view_data__option">
										<div class = 'crm_customer'><?php echo $data['СвОргОсущПр']['УчрЮЛ']['НаимСокрЮЛ'] ?>
											<div class='crm_customer__info'>
												<div class='crm_customer__info_rows'>
													<div class='crm_customer__info_row'>
														<div class='crm_customer__info_row_name'><?php _e('ОГРН', 'usam'); ?></div>
														<div class='crm_customer__info_row_option'><?php echo $data['СвОргОсущПр']['УчрЮЛ']['ОГРН']; ?></div>
													</div>
													<div class='crm_customer__info_row'>
														<div class='crm_customer__info_row_name'><?php _e('ИНН', 'usam'); ?></div>
														<div class='crm_customer__info_row_option'><?php echo $data['СвОргОсущПр']['УчрЮЛ']['ИНН']; ?></div>
													</div>	
													<div class='crm_customer__info_row'>
														<div class='crm_customer__info_row_name'><?php _e('Статус', 'usam'); ?></div>
														<div class='crm_customer__info_row_option'><?php echo $data['СвОргОсущПр']['УчрЮЛ']['Статус']; ?></div>
													</div>
												</div>
											</div>
										</div>
									</div>																
								</div>	
							</div>	
						</td>	
					<?php } elseif( isset($data['УчрПИФ']) ) { ?>	
						<td>
							<div class="view_data">		
								<div class ="view_data__row">
									<div class ="view_data__name"><?php _e('Паевой инвестиционный фонд', 'usam'); ?>:</div>
									<div class ="view_data__option">
										<div class = 'crm_customer'><?php echo $data['УчрПИФ']['НаимСокрЮЛ'] ?>
											<div class='crm_customer__info'>
												<div class='crm_customer__info_rows'>
													<div class='crm_customer__info_row'>
														<div class='crm_customer__info_row_name'><?php _e('ОГРН', 'usam'); ?></div>
														<div class='crm_customer__info_row_option'><?php echo $data['УчрПИФ']['ОГРН']; ?></div>
													</div>
													<div class='crm_customer__info_row'>
														<div class='crm_customer__info_row_name'><?php _e('ИНН', 'usam'); ?></div>
														<div class='crm_customer__info_row_option'><?php echo $data['УчрПИФ']['ИНН']; ?></div>
													</div>	
													<div class='crm_customer__info_row'>
														<div class='crm_customer__info_row_name'><?php _e('Статус', 'usam'); ?></div>
														<div class='crm_customer__info_row_option'><?php echo $data['УчрПИФ']['Статус']; ?></div>
													</div>
												</div>
											</div>
										</div>
									</div>																
								</div>	
							</div>	
						</td>	
						<?php } elseif( isset($data['Залогодержатели']) ) { ?>	
						<td>
							<div class="view_data">		
								<div class ="view_data__row">
									<div class ="view_data__name"><?php _e('Залогодержатель доли', 'usam'); ?>:</div>
									<div class ="view_data__option">
										<div class = 'crm_customer'><?php echo $data['Залогодержатели']['НаимСокрЮЛ'] ?>
											<div class='crm_customer__info'>
												<div class='crm_customer__info_rows'>
													<?php if( isset($data['Залогодержатели']['ОГРН']) ) { ?>	
														<div class='crm_customer__info_row'>
															<div class='crm_customer__info_row_name'><?php _e('ОГРН', 'usam'); ?></div>
															<div class='crm_customer__info_row_option'><?php echo $data['Залогодержатели']['ОГРН']; ?></div>
														</div>
														<div class='crm_customer__info_row'>
															<div class='crm_customer__info_row_name'><?php _e('ИНН', 'usam'); ?></div>
															<div class='crm_customer__info_row_option'><?php echo $data['Залогодержатели']['ИНН']; ?></div>
														</div>	
														<div class='crm_customer__info_row'>
															<div class='crm_customer__info_row_name'><?php _e('Статус юридического лица', 'usam'); ?></div>
															<div class='crm_customer__info_row_option'><?php echo $data['Залогодержатели']['Статус']; ?></div>
														</div>
													<?php } elseif( isset($data['Залогодержатели']['ФИОПолн']) ) { ?>	
														<div class='crm_customer__info_row'>
															<div class='crm_customer__info_row_name'><?php _e('ФИО', 'usam'); ?></div>
															<div class='crm_customer__info_row_option'><?php echo $data['Залогодержатели']['ФИОПолн']; ?></div>
														</div>
														<div class='crm_customer__info_row'>
															<div class='crm_customer__info_row_name'><?php _e('ИНН', 'usam'); ?></div>
															<div class='crm_customer__info_row_option'><?php echo $data['Залогодержатели']['ИНН']; ?></div>
														</div>	
														<div class='crm_customer__info_row'>
															<div class='crm_customer__info_row_name'><?php _e('ИНН физического лица', 'usam'); ?></div>
															<div class='crm_customer__info_row_option'><?php echo $data['Залогодержатели']['ИННФЛ']; ?></div>
														</div>
													<?php } elseif( isset($data['Залогодержатели']['НаимПолнЮЛ']) ) { ?>
														<div class='crm_customer__info_row'>
															<div class='crm_customer__info_row_name'><?php _e('Иностранное юридическое лицо', 'usam'); ?></div>
															<div class='crm_customer__info_row_option'><?php echo $data['Залогодержатели']['НаимПолнЮЛ']; ?></div>
														</div>
														<div class='crm_customer__info_row'>
															<div class='crm_customer__info_row_name'><?php _e('ОКСМ', 'usam'); ?></div>
															<div class='crm_customer__info_row_option'><?php echo $data['Залогодержатели']['ОКСМ']; ?></div>
														</div>	
														<div class='crm_customer__info_row'>
															<div class='crm_customer__info_row_name'><?php _e('Регистрационный номер', 'usam'); ?></div>
															<div class='crm_customer__info_row_option'><?php echo $data['Залогодержатели']['РегНомер']; ?></div>
														</div>													
														<div class='crm_customer__info_row'>
															<div class='crm_customer__info_row_name'><?php _e('Дата регистрации', 'usam'); ?></div>
															<div class='crm_customer__info_row_option'><?php echo date_i18n(get_option( 'date_format', 'Y/m/d' ),strtotime($data['Залогодержатели']['ДатаРег'])); ?></div>
														</div>
														<div class='crm_customer__info_row'>
															<div class='crm_customer__info_row_name'><?php _e('Адрес в стране происхождения', 'usam'); ?></div>
															<div class='crm_customer__info_row_option'><?php echo $data['Залогодержатели']['АдресПолн']; ?></div>
														</div>
													<?php } ?>	
													<div class='crm_customer__info_row'>
														<div class='crm_customer__info_row_name'><?php _e('Вид обременения', 'usam'); ?></div>
														<div class='crm_customer__info_row_option'><?php echo $data['Залогодержатели']['ВидОбременения']; ?></div>
													</div>
													<div class='crm_customer__info_row'>
														<div class='crm_customer__info_row_name'><?php _e('Срок обременения', 'usam'); ?></div>
														<div class='crm_customer__info_row_option'><?php echo $data['Залогодержатели']['СрокОбременения']; ?></div>
													</div>
													<div class='crm_customer__info_row'>
														<div class='crm_customer__info_row_name'><?php _e('Дата внесения в ЕГРЮЛ запись', 'usam'); ?></div>
														<div class='crm_customer__info_row_option'><?php echo $data['Залогодержатели']['Дата']; ?></div>
													</div>
												</div>
											</div>
										</div>
									</div>																
								</div>	
							</div>	
						</td>	
					<?php } ?>					
					<td><?php echo $data['СуммаУК']; ?></td>
					<td><?php echo $data['Процент']; ?></td>
					<td><?php echo date_i18n(get_option( 'date_format', 'Y/m/d' ),strtotime($data['Дата'])); ?></td>
				</tr>
				<?php  				
			}
			?>	
			</tbody>
		</table>	
		<?php			
	}
	
	function predecessors_report_box()
	{
		$this->company_participants( $this->registration_data['ЮЛ']['Предшественники'] );
	}
	
	function successors_report_box()
	{
		$this->company_participants( $this->registration_data['ЮЛ']['Преемники'] );
	}	
	
	function trustee_information_report_box()
	{
		$this->company_participants( $this->registration_data['ЮЛ']['УправлОрг'] );
	}
	
	function shareholder_register_holder_report_box()
	{
		$this->company_participants( $this->registration_data['ЮЛ']['ДержРеестрАО'] );
	}
	
	function participants_reorganization_report_box()
	{
		$this->company_participants( $this->registration_data['ЮЛ']['Участникивреорганизации'] );
	}					
	
	function company_participants( $participants )
	{
		?>
		<div class="view_data">		
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e('Количество', 'usam'); ?>:</div>
				<div class ="view_data__option"><?php echo count($participants); ?></div>
			</div>
		</div>		
		<table class = "usam_list_table">					
			<thead>
				<tr>
					<td><?php _e('Код', 'usam'); ?></td>
					<td><?php _e('ИНН', 'usam'); ?></td>
					<td><?php _e('ОГРН', 'usam'); ?></td>
					<td><?php _e('Статус', 'usam'); ?></td>
				</tr>
			</thead>
			<tbody>
			<?php  			
			foreach ( $participants as $participant )
			{
				?>	
				<tr>
					<td><?php echo $participant['НаимСокрЮЛ']; ?></td>
					<td><?php echo $participant['ИНН']; ?></td>
					<td><?php echo $participant['ОГРН']; ?></td>
					<td><?php echo $participant['Статус']; ?></td>
				</tr>
				<?php  				
			}
			?>	
			</tbody>
		</table>	
		<?php		
	}
	
	function affiliated_societies_report_box( )
	{		
		?>
		<div class="view_data">		
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e('Количество филиалов', 'usam'); ?>:</div>
				<div class ="view_data__option"><?php echo count($this->registration_data['Филиалы']); ?></div>
			</div>
		</div>		
		<table class = "usam_list_table">					
			<thead>
				<tr>
					<td><?php _e('Тип', 'usam'); ?></td>
					<td><?php _e('Адрес', 'usam'); ?></td>
				</tr>
			</thead>
			<tbody>
			<?php  			
			foreach ( $this->registration_data['ЮЛ']['Филиалы'] as $record )
			{
				?>	
				<tr>
					<td><?php echo $record['Тип']; ?></td>
					<td><?php echo $record['Адрес']; ?></td>					
				</tr>
				<?php  				
			}
			?>	
			</tbody>
		</table>	
		<?php		
	}
	
	function licenses_report_box( )
	{		
		?>
		<div class="view_data">		
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e('Количество лицензий', 'usam'); ?>:</div>
				<div class ="view_data__option"><?php echo count($this->registration_data['ЮЛ']['Лицензии']); ?></div>
			</div>
		</div>		
		<table class = "usam_list_table">					
			<thead>
				<tr>
					<td><?php _e('Серия и номер лицензии', 'usam'); ?></td>
					<td><?php _e('Вид деятельности', 'usam'); ?></td>
					<td><?php _e('Дата начала действия', 'usam'); ?></td>
					<td><?php _e('Дата окончания действия', 'usam'); ?></td>
					<td><?php _e('Сведения об адресах осуществления лицензируемого вида деятельности', 'usam'); ?></td>
				</tr>
			</thead>
			<tbody>
			<?php  			
			foreach ( $this->registration_data['ЮЛ']['Лицензии'] as $record )
			{
				?>	
				<tr>
					<td><?php echo $record['НомерЛиц']; ?></td>
					<td><?php echo $record['ВидДеятельности']; ?></td>			
					<td><?php echo $record['ДатаНачала']; ?></td>		
					<td><?php echo $record['ДатаОконч']; ?></td>		
					<td><?php echo $record['МестоДейств']; ?></td>							
				</tr>
				<?php  				
				//МестоДейств (если несколько, то адреса разделяются знаком вертикальной черты |
			}
			?>	
			</tbody>
		</table>	
		<?php		
	}	
	
	function participation_report_box( )
	{		
		?>
		<div class="view_data">		
			<div class ="view_data__row">
				<div class ="view_data__name"><?php _e('Количество компаний', 'usam'); ?>:</div>
				<div class ="view_data__option"><?php echo count($this->registration_data['ЮЛ']['Участия']); ?></div>
			</div>
		</div>		
		<table class = "usam_list_table">					
			<thead>
				<tr>
					<td><?php _e('Код', 'usam'); ?></td>
					<td><?php _e('ИНН', 'usam'); ?></td>
					<td><?php _e('ОГРН', 'usam'); ?></td>
					<td><?php _e('Статус', 'usam'); ?></td>
					<td><?php _e('Размер доли', 'usam'); ?></td>
					<td><?php _e('Стоимость доли', 'usam'); ?></td>
				</tr>
			</thead>
			<tbody>
			<?php  			
			foreach ( $this->registration_data['ЮЛ']['Участия'] as $data )
			{
				?>	
				<tr>
					<td><?php echo $data['НаимСокрЮЛ']; ?></td>
					<td><?php echo $data['ИНН']; ?></td>
					<td><?php echo $data['ОГРН']; ?></td>
					<td><?php echo $data['Статус']; ?></td>
					<td><?php echo $data['Процент']."%"; ?></td>
					<td><?php echo $data['СуммаУК']; ?></td>
				</tr>
				<?php  				
			}
			?>	
			</tbody>
		</table>	
		<?php		
	}	

	function tax_information_report_box()
	{
		?>		
		<table class = "usam_list_table">					
			<thead>
				<tr>
					<td><?php _e('Сумма налога', 'usam'); ?></td>
					<td><?php _e('Наименование налога или сбора', 'usam'); ?></td>			
					<td><?php _e('Сумма недоимки', 'usam'); ?></td>					
				</tr>
			</thead>
			<tbody>
			<?php  			
			foreach ( $this->registration_data['ЮЛ']['ОткрСведения']['Налоги'] as $data )
			{
				?>	
				<tr>
					<td><?php echo usam_currency_display($data['СумУплНал'], $this->currency_args); ?></td>
					<td><?php echo $data['НаимНалог']; ?></td>
					<td><?php echo !empty($data['СумНедНалог'])?usam_currency_display($data['СумНедНалог'], $this->currency_args):0; ?></td>
				</tr>
				<?php  				
			}
			?>	
			</tbody>
		</table>	
		<?php	
	}

	function egryl_records_report_box( )
	{
		$registration_data = isset($this->registration_data['ЮЛ'])?$this->registration_data['ЮЛ']['СПВЗ']:$this->registration_data['ИП']['СПВЗ'];		
		?>			
		<table class = "usam_list_table">					
			<thead>
				<tr>
					<td><?php _e('Дата', 'usam'); ?></td>
					<td><?php _e('Текст', 'usam'); ?></td>
				</tr>
			</thead>
			<tbody>
			<?php  			
			foreach ( $registration_data as $record )
			{
				?>	
				<tr>
					<td><?php echo date_i18n(get_option( 'date_format', 'Y/m/d' ),strtotime($record['Дата'])); ?></td>
					<td><?php echo $record['Текст']; ?></td>					
				</tr>
				<?php  				
			}
			?>	
			</tbody>
		</table>	
		<?php		
	}	
	
	function fssp_report_box( )
	{
		return [__('Дело','usam'), __('Дата','usam')];
	}
}
?>