<?php
/*
 *	Форматирование цены по типу цены
 */
function usam_get_formatted_price( $price, $args = null )
{			
	$args = apply_filters( 'usam_toggle_formatted_price', $args );
		
	$price = (double)$price;
	if ( empty($args['type_price']) )
		$args['type_price'] = usam_get_customer_price_code();	

	$price_setting = usam_get_setting_price_by_code( $args['type_price'] );
	if ( !empty($price_setting) )
	{ 	
		$price = round($price, $price_setting['rounding'] );	
		$args['currency'] = $price_setting['currency'];		
		$args['decimal_point'] = (bool)$price_setting['rounding']; 		
	}		
	return usam_currency_display( $price, $args );
}

/*
 *	Форматирование цены, добавление нулей, валюты
 */
function usam_currency_display( $price_in, $args = null )
{	 
	$args = apply_filters( 'usam_toggle_currency_code', $args );
	$args = shortcode_atts(['currency_symbol' => true, 'decimal_point' => true, 'currency_code' => false, 'wrap' => false, 'currency' => null, 'decimal_separator' => get_option('usam_decimal_separator', ','), 'thousands_separator' => get_option('usam_thousands_separator', '.')], $args ); 
	$price_in = str_replace(",", ".", $price_in);
	$price_in = (float)$price_in;	
	$args['decimal_point'] == false ? $decimals = 0 : $decimals = 2; 
	$price_out = number_format( $price_in, $decimals, $args['decimal_separator'], $args['thousands_separator'] );
	
	if ( empty($args['currency']) )
		return $price_out;
	
	$currency_data = usam_get_currency( $args['currency'] );

	if ( empty($currency_data) )
		return $price_out;
	
	// Выясните, код валюты
	$currency_code = $args['currency_code']?$currency_data['code']:'';
	
	// Выясните, знак валюты
	$currency_sign = '';	
	if ( $args['currency_symbol'] ) 
	{
		if ( !empty( $currency_data['symbol'] ) ) 
			$currency_sign = !empty($currency_data['symbol_html']) ? $currency_data['symbol_html'] : $currency_data['symbol'];
		else 
		{
			$currency_sign = $currency_data['code'];
			$currency_code = '';
		}
	}
	$currency_sign = $args['wrap']?"<span class='currency'>".$currency_sign."</span>":$currency_sign;
	$price_out = $args['wrap']?"<span class='price_number'>".$price_out."</span>":$price_out;
	$currency_sign_location = get_option( 'usam_currency_sign_location', 2 );
	// расположение знака валюты
	switch ( $currency_sign_location ) 
	{
		case 1:
			$format_string = '%3$s%1$s%2$s';
		break;
		case 2:
			$format_string = '%3$s %1$s%2$s';
		break;
		case 4:
			$format_string = '%1$s%2$s  %3$s';
		break;
		case 3:
		default:
			$format_string = '%1$s %2$s%3$s';
		break;
	}
	$output = trim( sprintf($format_string, $currency_code, $currency_sign, $price_out) );	
	return apply_filters( 'usam_currency_display', $output );
}

function usam_format_price( $amt, $currency_code = false )
{
	$currencies_without_fractions = array( 'JPY', 'HUF' );
	if ( ! $currency_code )
	{
		$currency = new USAM_Currency( );
		$currency_code = $currency->get( 'code' );
	}
	$dec = in_array( $currency_code, $currencies_without_fractions ) ? 0 : 2;
	return number_format( $amt, $dec );
}

function usam_format_convert_price( $amt, $from_currency = false, $to_currency = false ) 
{
	return usam_format_price( usam_convert_currency( $amt, $from_currency, $to_currency ), $to_currency );	
}

// Округление цены в зависимости от настроек
function usam_round_price( $price, $price_code )
{   
	$type_prices = usam_get_setting_price_by_code( $price_code );   
	if ( isset($type_prices['rounding']) )
		return round($price, $type_prices['rounding']);	
	else
		return $price;	
}

function usam_convert_currency( $amt, $from, $to ) 
{
	if ( empty($from) || empty($to) || $from == $to )
		return $amt;
	
	require_once( USAM_FILE_PATH . '/includes/directory/currency_rates_query.class.php' );
	$cache_key = "usam_currency_rate_{$to}";
	$rate = wp_cache_get($from, $cache_key );		
	if( $rate === false )
	{	
		$rate = usam_get_currency_rates(['fields' => 'rate', 'basic_currency' => $to, 'currency' => $from, 'number' => 1]);		
		wp_cache_set( $from, $rate, $cache_key );
	}			
	if ( !$rate )
	{
		$cache_key = "usam_currency_rate_{$from}";
		$rate = wp_cache_get($to, $cache_key );	
		if( $rate === false )
		{	
			$rate = usam_get_currency_rates(['fields' => 'rate', 'basic_currency' => $from, 'currency' => $to, 'number' => 1]);			
			if ( $rate )
				$rate = 1/$rate;	
			wp_cache_set( $to, $rate, $cache_key );
		}			
	}	
	if ( !$rate )
		return $amt;	
	return $rate * $amt;
}


/**
* PHP-класс для получения курсов валют с сайта ЦБ РФ:
*/
class USAM_ExchangeRatesCBRF
{	
	public $rates = array('byChCode' => array(), 'byCode' => array());	
	public function __construct( $date = null )
	{
		if (!isset($date)) 
			$date = date("Y-m-d");
		
		if ( !extension_loaded('soap') || !class_exists('SoapClient') )
			return false;		
		
		try 
		{				
			$context = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]]);
			$client = new SoapClient("https://www.cbr.ru/DailyInfoWebServ/DailyInfo.asmx?WSDL", ['exceptions' => true, 'stream_context' => $context]);			
			$curs = $client->GetCursOnDate(["On_date" => $date]);
			$rates = new SimpleXMLElement($curs->GetCursOnDateResult->any);
			foreach ($rates->ValuteData->ValuteCursOnDate as $rate)
			{
				$r = (float)$rate->Vcurs / (int)$rate->Vnom;
				$this->rates['byChCode'][(string)$rate->VchCode] = $r;	
				$this->rates['byCode'][(int)$rate->Vcode] = $r;	
			}		
			// Adding an exchange rate of Russian Ruble 
			$this->rates['byChCode']['RUB'] = 1;	
			$this->rates['byCode'][643] = 1;	
		} 
		catch (PDOException $e) 
		{				
			 usam_log_file( $e->getMessage() );
		}				
	}

	public function GetRate( $code )
	{
		if (is_string($code))
		{
			$code = strtoupper(trim($code));
			return (isset($this->rates['byChCode'][$code])) ? $this->rates['byChCode'][$code] : false;
		
		}
		elseif (is_numeric($code))
		{
			return (isset($this->rates['byCode'][$code])) ? $this->rates['byCode'][$code] : false;
		}
		else 
		{
			return false;		
		}
	}
	
	public function GetCrossRate($CurCodeToSell, $CurCodeToBuy)
	{
		$CurToSellRate = $this->GetRate($CurCodeToSell);
		$CurToBuyRate = $this->GetRate($CurCodeToBuy);
		
		if ($CurToSellRate && $CurToBuyRate)
			return $CurToBuyRate / $CurToSellRate;
		else
			return false;
	}	

	public function GetRates()
	{
		return $this->rates;
	}	
}


/*
	конвертация валюты.
*/
Class USAM_CURRENCY_CONVERTER
{
	var $_amt=1;
	var $_to="";
	var $_from="";
	var $_error="";
	
	public function __construct( $amt=1, $to="", $from="" )
	{
		$this->_amt=$amt;
		$this->_to=$to;
		$this->_from=$from;
	}
	function error()
	{
		return $this->_error;
	}

	/**
	 * Конвертер валют
	 */
	function convert($amt = NULL, $to = "", $from = "")
	{
		$amount = urlencode(round($amt,2));
		$from_Currency = urlencode($from);
		$to_Currency = urlencode($to);

		$url = "https://www.google.com/finance/converter?hl=en&a={$amount}&from={$from_Currency}&to={$to_Currency}";

		$ch = curl_init();
		$timeout = 20;
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$rawdata = curl_exec($ch);
		curl_close($ch);
		if(empty($rawdata))
		{
			throw new Exception( __('не удалось подключиться к службе конвертации валюты', 'usam') );
		}		
		preg_match( '/bld[^"]+"([\d\s.,]+)/', $rawdata, $matches );
		if ( isset($matches[1] ) )
			$to_amount = (float) str_replace( array( ',', ' ' ), '', $matches[1] );
		else 
		{
			$rawdata = preg_replace( '/(\{|,\s*)([^\s:]+)(\s*:)/', '$1"$2"$3', $rawdata );
			$to_amount = json_decode( $rawdata );
		}
		$to_amount = round( $to_amount, 2 );
		return $to_amount;
	}
}

function usam_get_number_word( $source, $is_money = 1, $currency = "")
{
	$result = '';

	$currency = (string)$currency;
	if ($currency == '' || $currency == 'RUR')
		$currency = 'RUB';
	if ( $is_money )
	{
		if ($currency != 'RUB' && $currency != 'UAH')
			return $result;
	}

	$arNumericLang = array(
		"UAH" => array(
			"zero" => "нyль",
			"1c" => "сто ",
			"2c" => "двісті ",
			"3c" => "триста ",
			"4c" => "чотириста ",
			"5c" => "п'ятсот ",
			"6c" => "шістсот ",
			"7c" => "сімсот ",
			"8c" => "вісімсот ",
			"9c" => "дев'ятьсот ",
			"1d0e" => "десять ",
			"1d1e" => "одинадцять ",
			"1d2e" => "дванадцять ",
			"1d3e" => "тринадцять ",
			"1d4e" => "чотирнадцять ",
			"1d5e" => "п'ятнадцять ",
			"1d6e" => "шістнадцять ",
			"1d7e" => "сімнадцять ",
			"1d8e" => "вісімнадцять ",
			"1d9e" => "дев'ятнадцять ",
			"2d" => "двадцять ",
			"3d" => "тридцять ",
			"4d" => "сорок ",
			"5d" => "п'ятдесят ",
			"6d" => "шістдесят ",
			"7d" => "сімдесят ",
			"8d" => "вісімдесят ",
			"9d" => "дев'яносто ",
			"5e" => "п'ять ",
			"6e" => "шість ",
			"7e" => "сім ",
			"8e" => "вісім ",
			"9e" => "дев'ять ",
			"1e." => "один гривня ",
			"2e." => "два гривні ",
			"3e." => "три гривні ",
			"4e." => "чотири гривні ",
			"1e" => "один ",
			"2e" => "два ",
			"3e" => "три ",
			"4e" => "чотири ",
			"1et" => "одна тисяча ",
			"2et" => "дві тисячі ",
			"3et" => "три тисячі ",
			"4et" => "чотири тисячі ",
			"1em" => "один мільйон ",
			"2em" => "два мільйона ",
			"3em" => "три мільйона ",
			"4em" => "чотири мільйона ",
			"1eb" => "один мільярд ",
			"2eb" => "два мільярда ",
			"3eb" => "три мільярда ",
			"4eb" => "чотири мільярда ",
			"11k" => "11 копійок",
			"12k" => "12 копійок",
			"13k" => "13 копійок",
			"14k" => "14 копійок",
			"1k" => "1 копійка",
			"2k" => "2 копійки",
			"3k" => "3 копійки",
			"4k" => "4 копійки",
			"." => "гривень ",
			"t" => "тисяч ",
			"m" => "мільйонів ",
			"b" => "мільярдів ",
			"k" => " копійок",
		),
		"RUB" => array(
			"zero" => "ноль",
			"1c" => "сто ",
			"2c" => "двести ",
			"3c" => "триста ",
			"4c" => "четыреста ",
			"5c" => "пятьсот ",
			"6c" => "шестьсот ",
			"7c" => "семьсот ",
			"8c" => "восемьсот ",
			"9c" => "девятьсот ",
			"1d0e" => "десять ",
			"1d1e" => "одиннадцать ",
			"1d2e" => "двенадцать ",
			"1d3e" => "тринадцать ",
			"1d4e" => "четырнадцать ",
			"1d5e" => "пятнадцать ",
			"1d6e" => "шестнадцать ",
			"1d7e" => "семнадцать ",
			"1d8e" => "восемнадцать ",
			"1d9e" => "девятнадцать ",
			"2d" => "двадцать ",
			"3d" => "тридцать ",
			"4d" => "сорок ",
			"5d" => "пятьдесят ",
			"6d" => "шестьдесят ",
			"7d" => "семьдесят ",
			"8d" => "восемьдесят ",
			"9d" => "девяносто ",
			"5e" => "пять ",
			"6e" => "шесть ",
			"7e" => "семь ",
			"8e" => "восемь ",
			"9e" => "девять ",
			"1et" => "одна тысяча ",
			"2et" => "две тысячи ",
			"3et" => "три тысячи ",
			"4et" => "четыре тысячи ",
			"1em" => "один миллион ",
			"2em" => "два миллиона ",
			"3em" => "три миллиона ",
			"4em" => "четыре миллиона ",
			"1eb" => "один миллиард ",
			"2eb" => "два миллиарда ",
			"3eb" => "три миллиарда ",
			"4eb" => "четыре миллиарда ",
			"1e." => "один рубль ",
			"2e." => "два рубля ",
			"3e." => "три рубля ",
			"4e." => "четыре рубля ",
			"1e" => "один ",
			"2e" => "два ",
			"3e" => "три ",
			"4e" => "четыре ",
			"11k" => "11 копеек",
			"12k" => "12 копеек",
			"13k" => "13 копеек",
			"14k" => "14 копеек",
			"1k" => "1 копейка",
			"2k" => "2 копейки",
			"3k" => "3 копейки",
			"4k" => "4 копейки",
			"." => "рублей ",
			"t" => "тысяч ",
			"m" => "миллионов ",
			"b" => "миллиардов ",
			"k" => " копеек",
		)		
	);
	if ( $is_money )
	{
		$source = (string)((float)$source);
		$dotpos = strpos($source, ".");
		if ($dotpos === false)
		{
			$ipart = $source;
			$fpart = '';
		}
		else
		{
			$ipart = substr($source, 0, $dotpos);
			$fpart = substr($source, $dotpos + 1);
			if ($fpart === false)
				$fpart = '';
		}
		;
		if (strlen($fpart) > 2)
		{
			$fpart = substr($fpart, 0, 2);
			if ($fpart === false)
				$fpart = '';
		}
		$fillLen = 2 - strlen($fpart);
		if ($fillLen > 0)
			$fpart .= str_repeat('0', $fillLen);
		unset($fillLen);
	}
	else
	{
		$ipart = (string)((int)$source);
		$fpart = '';
	}

	if (is_string($ipart))
	{
		$ipart = preg_replace('/^[0]+/', '', $ipart);
	}

	$ipart1 = strrev($ipart);
	$ipart1Len = strlen($ipart1);
	$ipart = "";
	$i = 0;
	while ($i < $ipart1Len)
	{
		$ipart_tmp = substr($ipart1, $i, 1);
		// t - thousands; m - millions; b - billions;
		// e - units; d - scores; c - hundreds;
		if ($i % 3 == 0)
		{
			if ($i==0) $ipart_tmp .= "e";
			elseif ($i==3) $ipart_tmp .= "et";
			elseif ($i==6) $ipart_tmp .= "em";
			elseif ($i==9) $ipart_tmp .= "eb";
			else $ipart_tmp .= "x";
		}
		elseif ($i % 3 == 1) $ipart_tmp .= "d";
		elseif ($i % 3 == 2) $ipart_tmp .= "c";
		$ipart = $ipart_tmp.$ipart;
		$i++;
	}

	if ( $is_money )
	{
		$result = $ipart.".".$fpart."k";
	}
	else
	{
		$result = $ipart;
		if ($result == '')
			$result = $arNumericLang[$currency]['zero'];
	}

	if (substr($result, 0, 1) == ".")
		$result = $arNumericLang[$currency]['zero']." ".$result;

	$result = str_replace("0c0d0et", "", $result);
	$result = str_replace("0c0d0em", "", $result);
	$result = str_replace("0c0d0eb", "", $result);

	$result = str_replace("0c", "", $result);
	$result = str_replace("1c", $arNumericLang[$currency]["1c"], $result);
	$result = str_replace("2c", $arNumericLang[$currency]["2c"], $result);
	$result = str_replace("3c", $arNumericLang[$currency]["3c"], $result);
	$result = str_replace("4c", $arNumericLang[$currency]["4c"], $result);
	$result = str_replace("5c", $arNumericLang[$currency]["5c"], $result);
	$result = str_replace("6c", $arNumericLang[$currency]["6c"], $result);
	$result = str_replace("7c", $arNumericLang[$currency]["7c"], $result);
	$result = str_replace("8c", $arNumericLang[$currency]["8c"], $result);
	$result = str_replace("9c", $arNumericLang[$currency]["9c"], $result);

	$result = str_replace("1d0e", $arNumericLang[$currency]["1d0e"], $result);
	$result = str_replace("1d1e", $arNumericLang[$currency]["1d1e"], $result);
	$result = str_replace("1d2e", $arNumericLang[$currency]["1d2e"], $result);
	$result = str_replace("1d3e", $arNumericLang[$currency]["1d3e"], $result);
	$result = str_replace("1d4e", $arNumericLang[$currency]["1d4e"], $result);
	$result = str_replace("1d5e", $arNumericLang[$currency]["1d5e"], $result);
	$result = str_replace("1d6e", $arNumericLang[$currency]["1d6e"], $result);
	$result = str_replace("1d7e", $arNumericLang[$currency]["1d7e"], $result);
	$result = str_replace("1d8e", $arNumericLang[$currency]["1d8e"], $result);
	$result = str_replace("1d9e", $arNumericLang[$currency]["1d9e"], $result);

	$result = str_replace("0d", "", $result);
	$result = str_replace("2d", $arNumericLang[$currency]["2d"], $result);
	$result = str_replace("3d", $arNumericLang[$currency]["3d"], $result);
	$result = str_replace("4d", $arNumericLang[$currency]["4d"], $result);
	$result = str_replace("5d", $arNumericLang[$currency]["5d"], $result);
	$result = str_replace("6d", $arNumericLang[$currency]["6d"], $result);
	$result = str_replace("7d", $arNumericLang[$currency]["7d"], $result);
	$result = str_replace("8d", $arNumericLang[$currency]["8d"], $result);
	$result = str_replace("9d", $arNumericLang[$currency]["9d"], $result);

	$result = str_replace("0e", "", $result);
	$result = str_replace("5e", $arNumericLang[$currency]["5e"], $result);
	$result = str_replace("6e", $arNumericLang[$currency]["6e"], $result);
	$result = str_replace("7e", $arNumericLang[$currency]["7e"], $result);
	$result = str_replace("8e", $arNumericLang[$currency]["8e"], $result);
	$result = str_replace("9e", $arNumericLang[$currency]["9e"], $result);

	$result = str_replace("1et", $arNumericLang[$currency]["1et"], $result);
	$result = str_replace("2et", $arNumericLang[$currency]["2et"], $result);
	$result = str_replace("3et", $arNumericLang[$currency]["3et"], $result);
	$result = str_replace("4et", $arNumericLang[$currency]["4et"], $result);
	$result = str_replace("1em", $arNumericLang[$currency]["1em"], $result);
	$result = str_replace("2em", $arNumericLang[$currency]["2em"], $result);
	$result = str_replace("3em", $arNumericLang[$currency]["3em"], $result);
	$result = str_replace("4em", $arNumericLang[$currency]["4em"], $result);
	$result = str_replace("1eb", $arNumericLang[$currency]["1eb"], $result);
	$result = str_replace("2eb", $arNumericLang[$currency]["2eb"], $result);
	$result = str_replace("3eb", $arNumericLang[$currency]["3eb"], $result);
	$result = str_replace("4eb", $arNumericLang[$currency]["4eb"], $result);

	if ( $is_money )
	{
		$result = str_replace("1e.", $arNumericLang[$currency]["1e."], $result);
		$result = str_replace("2e.", $arNumericLang[$currency]["2e."], $result);
		$result = str_replace("3e.", $arNumericLang[$currency]["3e."], $result);
		$result = str_replace("4e.", $arNumericLang[$currency]["4e."], $result);
	}
	else
	{
		$result = str_replace("1e", $arNumericLang[$currency]["1e"], $result);
		$result = str_replace("2e", $arNumericLang[$currency]["2e"], $result);
		$result = str_replace("3e", $arNumericLang[$currency]["3e"], $result);
		$result = str_replace("4e", $arNumericLang[$currency]["4e"], $result);
	}

	if ( $is_money )
	{
		$result = str_replace("11k", $arNumericLang[$currency]["11k"], $result);
		$result = str_replace("12k", $arNumericLang[$currency]["12k"], $result);
		$result = str_replace("13k", $arNumericLang[$currency]["13k"], $result);
		$result = str_replace("14k", $arNumericLang[$currency]["14k"], $result);
		$result = str_replace("1k", $arNumericLang[$currency]["1k"], $result);
		$result = str_replace("2k", $arNumericLang[$currency]["2k"], $result);
		$result = str_replace("3k", $arNumericLang[$currency]["3k"], $result);
		$result = str_replace("4k", $arNumericLang[$currency]["4k"], $result);
	}

	if ( $is_money )
	{
		if (substr($result, 0, 1) == ".")
			$result = $arNumericLang[$currency]['zero']." ".$result;

		$result = str_replace(".", $arNumericLang[$currency]["."], $result);
	}

	$result = str_replace("t", $arNumericLang[$currency]["t"], $result);
	$result = str_replace("m", $arNumericLang[$currency]["m"], $result);
	$result = str_replace("b", $arNumericLang[$currency]["b"], $result);

	if ( $is_money )
		$result = str_replace("k", $arNumericLang[$currency]["k"], $result);
	
	return substr($result, 0, 1).substr($result, 1);
}