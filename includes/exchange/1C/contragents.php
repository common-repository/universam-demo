<?php
if (!defined('ABSPATH')) exit;


require_once(USAM_FILE_PATH . '/includes/exchange/1C/document_handler.php' );
class USAM_contragents_Element_Handler extends USAM_Document_Handler
{
	private $contragent = [];
	function start_element_handler($is_full, $names, $depth, $name, $attrs) 
	{	
		$this->is_full = $is_full;
		if (@$names[$depth - 1] == 'Контрагенты' && @$names[$depth] == 'Контрагент' )
			$this->contragent = ['РасчетныйСчет' => [0 => []], 'Контакты' => [], 'Представители' => []];			
	}

	function character_data_handler($is_full, $names, $depth, $name, $data)
	{		
		if (@$names[$depth - 2] == 'РасчетныеСчета' && @$names[$depth - 1] == 'РасчетныйСчет' || @$names[$depth - 2] == 'РасчетныйСчет' && @$names[$depth - 1] == 'Банк' || @$names[$depth - 2] == 'Банк' && @$names[$depth - 1] == 'Адрес' )
		{
			$i = count($this->contragent['РасчетныйСчет']) - 1;
			foreach (['number' => 'НомерСчета', 'name' => 'Наименование', 'bank_ca' => 'СчетКорреспондентский', 'bic' => 'БИК', 'address' => 'Представление'] as $k => $value ) 
			{
				if ( $value == $name )
					@$this->contragent['РасчетныйСчет'][$i][$k] .= $data;
			} 
		}	
		elseif (@$names[$depth - 2] == 'Контакты' && @$names[$depth - 1] == 'Контакт')
		{  
			$i = count($this->contragent['Контакты']);
			$i = $names[$depth] == 'Тип'?$i:$i-1;	
			@$this->contragent['Контакты'][$i][$name] .= $data;
		}	
		elseif (@$names[$depth - 2] == 'Представители' && @$names[$depth - 1] == 'Представитель')
		{
			$i = count($this->contragent['Представители']);
			$i = $names[$depth] == 'Отношение'?$i:$i-1;	  
			@$this->contragent['Представители'][$i][$name] .= $data;
		}		
		elseif (@$names[$depth - 2] == 'Контрагенты' && @$names[$depth - 1] == 'Контрагент' && !in_array($name, ['Контакты', 'Представители', 'РасчетныеСчета']))
		{
			@$this->contragent[$name] .= $data;
		}			
	}

	function end_element_handler($is_full, $names, $depth, $name)
	{		
		if (@$names[$depth - 1] == 'Контрагенты' && $name == 'Контрагент') 
		{ 
			$this->set_contragents( $this->contragent );
		}
	}
	
	function set_contragents( $contragent ) 
	{ 							
		$communication = [];			
		if ( !empty($contragent['Контакты']) ) 
		{
			foreach ( $contragent['Контакты'] as $k => $value ) 
			{
				if ( isset($value['Тип']) && isset($value['Значение']) )
				{
					if( $value['Тип'] == 'Электронная почта' )
						$communication['email'] = $value['Значение'];
					elseif( $value['Тип'] == 'Телефон рабочий' )
						$communication['phone'] = $value['Значение'];	
					elseif( $value['Тип'] == 'Мобильный телефон' )
						$communication['mobilephone'] = $value['Значение'];	
				}
			}
		}
		if ( !empty($contragent['ИНН']) || !empty($contragent['ОфициальноеНаименование']) ) 
		{		
			$data = [];	
			$metas = $communication;
			$id = usam_get_company_id_by_meta( 'code_1c', $this->contragent['Ид'] );  					
			$new_accounts = [];			
			if ( !empty($contragent['ОфициальноеНаименование']) ) 
				$data['name'] = $metas['full_company_name'] = trim($contragent['ОфициальноеНаименование']);
			if ( !empty($contragent['Наименование']) ) 
				$data['name'] = $metas['company_name'] = trim($contragent['Наименование']);	
			if ( !empty($contragent['ИНН']) ) 
				$metas['inn'] = trim($contragent['ИНН']);			
			if ( !empty($contragent['КПП']) ) 
				$metas['ppc'] = trim($contragent['КПП']);		
			if ( !empty($contragent['РасчетныйСчет']) ) 
			{
				foreach ( $contragent['РасчетныйСчет'] as $k => $account ) 
				{
					if( !empty($account['number']) )
						$new_accounts[] = $account;	
				}				
			}		
			if ( $id )
			{
				usam_update_company( $id, $data, $metas, $new_accounts );
				$this->update++;
			}
			else
			{
				$metas['code_1c'] = $this->contragent['Ид'];
				$id = usam_insert_company( $data, $metas, $new_accounts );
				if ( $id )
					$this->insert++;
			}
			if ( $id && !empty($contragent['Контакты']) ) 
			{
				foreach( $contragent['Представители'] as $k => $value ) 
				{
					$contact = ['full_name' => $value['Наименование'], 'company_id' => $id, 'code_1c' => $value['Ид']];	  
					usam_insert_contact( $contact );
				}
			}
		}
		else
		{ 
			$data = $communication;			
			$id = usam_get_contact_id_by_meta( 'code_1c', $this->contragent['Ид'] );	
			if ( !empty($contragent['ПолноеНаименование']) ) 
				$data['full_name'] = $contragent['ПолноеНаименование'];
			elseif ( !empty($contragent['Наименование']) ) 
				$data['full_name'] = $contragent['Наименование'];			 
			if ( $id )
			{
				usam_update_contact( $id, $data );
				$this->update++;
			}
			else
			{				
				$data['code_1c'] = $this->contragent['Ид'];
				if ( usam_insert_contact( $data ) )
					$this->insert++;
			}		
		}		
	}
}