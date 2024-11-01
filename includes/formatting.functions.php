<?php
// Описание файла: Форматирование данных

// Описание: Конвертирование веса
function usam_convert_weight($in_weight, $out_unit = 'kg', $in_unit = '', $raw = false) 
{	
	if ( empty($in_weight) )
		return 0;
	
	if ( $in_unit == '' )		
		$in_unit = get_option( 'usam_weight_unit', 'kg' );	
	
	switch( $in_unit ) 
	{
		case "kilogram":
		case "kg":
			$intermediate_weight = $in_weight * 1000;
		break;
		case "gram":
		case "g":
			$intermediate_weight = $in_weight;
		break;
		case "ounce":
		case "oz":
			$intermediate_weight = ($in_weight / 16) * 453.59237;
		break;
		case "pound":
		case "lbs":
		default:
			$intermediate_weight = $in_weight * 453.59237;
		break;
	}
	switch( $out_unit ) 
	{
		case "kilogram":
		case "kg":
			$weight = $intermediate_weight / 1000;
		break;
		case "gram":
		case "g":
			$weight = $intermediate_weight;
		break;
		case "ounce":
		case "oz":
			$weight = ($intermediate_weight / 453.59237) * 16;
		break;
		case "pound":
		case "lbs":
		default:
			$weight = $intermediate_weight / 453.59237;
		break;
	}
	if($raw)
		return $weight;
	return round($weight, 4);
}

// Описание: Конвертирование объема
function usam_convert_volume( $in_volume, $out_unit = 'm' ) 
{		
	if ( empty($in_volume) )
		return 0;
		
	$dimension_unit = get_option('usam_dimension_unit'); 
	switch( $dimension_unit ) 
	{
		case "mm":
			$intermediate_weight = $in_volume / 1000000000;
		break;
		case "cm":
			$intermediate_weight = $in_volume / 1000000;
		break;	
		case "yd":		
			$intermediate_weight = $in_volume / 1.3080;
		break;
		case "in":
			$intermediate_weight = $in_volume / 61023.744094732;		
		break;
		case "m":
		default:
			$intermediate_weight = $in_volume;
		break;
	}	
	switch( $out_unit ) 
	{
		case "mm":
			$volume = $intermediate_weight * 1000000000;
		break;
		case "cm":
			$volume = $intermediate_weight * 1000000;
		break;	
		case "yd":		
			$volume = $intermediate_weight * 1.3080;
		break;
		case "in":
			$volume = $intermediate_weight * 61023.744094732;		
		break;
		case "m":
		default:
			$volume = $intermediate_weight;
		break;
	}	
	return $volume;
}

function usam_convert_dimension($in_dimension, $in_unit, $out_unit = '', $raw = false) 
{	
	if ( empty($in_dimension) )
		return 0;
			
	switch( $in_unit ) 
	{
		case "mm":
			$intermediate_weight = $in_dimension / 1000;
		break;
		case "cm":
			$intermediate_weight = $in_dimension / 100;
		break;	
		case "yd":		
			$intermediate_weight = $in_dimension / 1.09361;
		break;
		case "in":
			$intermediate_weight = $in_dimension / 39.3701;		
		break;
		case "m":
		default:
			$intermediate_weight = $in_dimension;
		break;
	}
	if ( $out_unit == '' )
		$out_unit = get_option( 'usam_dimension_unit', '' );
	switch( $out_unit ) 
	{
		case "mm":
			$volume = $intermediate_weight * 1000;
		break;
		case "cm":
			$volume = $intermediate_weight * 100;
		break;	
		case "yd":		
			$volume = $intermediate_weight * 1.09361;
		break;
		case "in":
			$volume = $intermediate_weight * 39.3701;		
		break;
		case "m":
		default:
			$volume = $intermediate_weight;
		break;
	}	
	return $volume;
}

function usam_convert_time($in_time, $in_unit, $out_unit = 's', $raw = false) 
{	
	if ( empty($in_time) )
		return 0;
	switch( $in_unit ) 
	{
		case "µs":
			$intermediate = $in_time / 1000000;
		break;
		case "ms": //милисекунда
			$intermediate = $in_time / 1000;
		break;	
		case "min":
			$intermediate = $in_time * 60;
		break;		
		case "hour":
			$intermediate = $in_time * 3600;
		break;
		case "s":		
		default:
			$intermediate = $in_time;
		break;
	}
	switch( $out_unit ) 
	{
		case "µs":
			$time = $intermediate * 1000000;
		break;
		case "ms": //милисекунда
			$time = $intermediate * 1000;
		break;	
		case "min":
			$time = $intermediate / 60;
		break;		
		case "hour":
			$time = $intermediate / 3600;
		break;
		case "s":		
		default:
			$time = $intermediate;
		break;
	}	
	if( $raw )
		return $time;
	return round($time, 1);
}

function usam_get_unit_measure( $value, $key = 'code' )
{
	$units = usam_get_list_units();		
	foreach ( $units as $unit )
	{
		if ( $value == $unit[$key] )
			return $unit;
	}
	return false;
}

// Получить Список единиц измерения
function usam_get_list_units( )
{		
	$option = get_option('usam_units_measure', []);
	return maybe_unserialize( $option );
}

function usam_get_dimension_units(  ) 
{		
	$dimension_units = [	
		'mm'  => [ 'title' => __('Миллиметры','usam'), 'short' => __('мм','usam'), 'in' => __('в миллиметрах','usam')], 
		'cm'  => [ 'title' => __('Сантиметры','usam'), 'short' => __('см','usam'), 'in' => __('в сантиметрах','usam')], 
		'm'   => [ 'title' => __('Метры','usam'), 'short' => __('м','usam'), 'in' => __('в метрах','usam')],
		'in'  => [ 'title' => __('Дюймы','usam'), 'short' => __('д','usam'), 'in' => __('в дюймах','usam')],
		'yd'  => [ 'title' => __('Ядры','usam'), 'short' => __('я','usam'), 'in' => __('в ядрах','usam')],		
	];	
	return $dimension_units;
}

function usam_get_weekday( )
{     
	$weekday = ['1' => __('Понедельник','usam'), '2' => __('Вторник','usam'), '3' => __('Среда','usam'), '4' => __('Четверг','usam'), '5' => __('Пятница','usam'), '6' => __('Суббота','usam'), '0' => __('Воскресение','usam')];		
	return $weekday;
}   

function usam_get_weight_units(  ) 
{		
	$weight_units = array(		
		'g'      => __('граммы', 'usam'),
		'kg'     => __('килограммы', 'usam'),
		'lbs'    => __('фунты', 'usam'),
		'oz'     => __('унции', 'usam')
	);
	return $weight_units;
}

function usam_get_name_weight_units( $weight_unit = '' ) 
{		
	$weight_units = array(		
		'g'      => __('гр', 'usam'),
		'kg'     => __('кг', 'usam'),
		'lbs'    => __('фунты', 'usam'),
		'oz'     => __('унции', 'usam')
	);
	if ( $weight_unit == '' )
		$weight_unit = get_option('usam_weight_unit');
	return isset($weight_units[$weight_unit])?$weight_units[$weight_unit]:'';
}

/*Дата, измененная по часовому поясу сайта*/
function usam_local_date( $date, $date_format = '' ) 
{		
	if ( $date == '' || $date == '0000-00-00 00:00:00' )
		return '';	
	
	$date = is_numeric($date)?date("Y-m-d H:i:s",$date):$date;
	if ( $date_format == '' )
	{
		$date_format = get_option('date_format', 'Y/m/j')." H:i";
		if ( date('Y') == date('Y', strtotime($date)) )
			$date_format = trim(str_replace(['.Y', ' Y', '-Y', '/Y'], '', $date_format));
	}	
	return date_i18n( $date_format, strtotime(get_date_from_gmt( $date )) );
}

function usam_local_formatted_date( $date, $date_format = '' ) 
{
	$timestamp = is_numeric($date)?$date:strtotime( $date );	
	$time_diff = time() - $timestamp;
	if ( $time_diff < 10 && $time_diff >= 0 )
		return __('сейчас','usam');
	elseif ( $time_diff > 0 && $time_diff < 86400 ) // 24 * 60 * 60
		return sprintf( __('%s назад' ), human_time_diff( $timestamp, time() ) );
	elseif ( date('Y') == date('Y', $timestamp) )
		return usam_local_date( $date, 'j M' );
	else
		return usam_local_date( $date, $date_format );
}

function usam_string_to_float( $string ) 
{	
	//$decimal_separator = get_option( 'usam_decimal_separator' );		
	$string = preg_replace( '/[^0-9\\.,E+-]/', '', $string );	
	//$string = str_replace( $decimal_separator, '.', $string );
	if( stristr($string, '.') !== false && stristr($string, ',') !== false ) 
	{ // Если найдено и . и ,		
		$point = strpos($string, '.'); 
		$comma = strpos($string, ','); 
		if ( $point < $comma )
		{
			$string = str_replace( '.', '', $string );	
			$string = str_replace( ',', '.', $string );	
		}
		else
			$string = str_replace( ',', '', $string );	
	}	
	else
		$string = str_replace( ',', '.', $string );
		
	return (float)$string;
}

// formats a floating point number string in decimal notation, supports signed floats, also supports non-standard formatting e.g. 0.2e+2 for 20
// e.g. '1.6E+6' to '1600000', '-4.566e-12' to '-0.000000000004566', '+34e+10' to '340000000000'
// Author: Bob
function usam_exp_to_dec( $float_str )
{
    $float_str = (string)((float)($float_str));
    if(($pos = strpos(strtolower($float_str), 'e')) !== false)
    {
        $exp = substr($float_str, $pos+1);
        $num = substr($float_str, 0, $pos);
       
        if((($num_sign = $num[0]) === '+') || ($num_sign === '-')) 
			$num = substr($num, 1);
        else
			$num_sign = '';
        if($num_sign === '+')
			$num_sign = '';
       
        if((($exp_sign = $exp[0]) === '+') || ($exp_sign === '-'))
			$exp = substr($exp, 1);
        else
			trigger_error("Could not convert exponential notation to decimal notation: invalid float string '$float_str'", E_USER_ERROR);
       
        $right_dec_places = (($dec_pos = strpos($num, '.')) === false) ? 0 : strlen(substr($num, $dec_pos+1));
        $left_dec_places = ($dec_pos === false) ? strlen($num) : strlen(substr($num, 0, $dec_pos));
       
        if($exp_sign === '+')
			$num_zeros = $exp - $right_dec_places;
        else 
			$num_zeros = $exp - $left_dec_places;
       
        $zeros = str_pad('', $num_zeros, '0');       
        if($dec_pos !== false) 
			$num = str_replace('.', '', $num);       
        if($exp_sign === '+') 
			return $num_sign.$num.$zeros;
        else 
			return $num_sign.'0.'.$zeros.$num;
    }
    else return $float_str;
}

function usam_get_formatted_document_number( $format_document_number, $number = 0 ) 
{
	$str_number = preg_replace( '/[^0-9]/', '', $format_document_number );
	$str_number_strlen = strlen($str_number);
	$strlen = strlen($format_document_number) - $str_number_strlen;
	$code = substr($format_document_number, 0, $strlen);
	if ( $number == 0 )
		$number = preg_replace('/^0+/', '', $str_number	);
	$number++;	
	$strlen_number = $str_number_strlen - strlen((string)$number);
	if ( $strlen_number < 0 )
		$strlen_number = 0;	
	$new_document_number = $code.str_repeat("0", $strlen_number).$number;
	return $new_document_number;
}

function usam_utf8_for_xml( $string )
{
	if ( $string === false )
		return '';
	$string = htmlspecialchars($string);
	return preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', ' ', $string);
}
			

/**
 * Получить отформатированный телефоны номер
 */
function usam_get_phone_format( $phone )
{
	$phones = explode(',',$phone);
	$results = [];
	foreach( $phones as $phone )
	{
		$phone = preg_replace("/[^0-9]/",'',$phone);
		$len = strlen($phone);
		switch ( $len ) 
		{
			case 10:
				$formats = array( '10' => '8 (999) 999 9999' );
				$results[] = usam_phone_format($phone, $formats);
			break;
			case 11:
				$formats = array( '11' => '+9 (999) 999 99 99');
				$results[] = usam_phone_format($phone, $formats);			
			break;
			default:
				$results[] = $phone;
			break;
		}
	}
    return implode(', ',$results); 
} 

/**
 * Форматирование телефонного номера по шаблону и маске для замены
 */
function usam_phone_format($phone, $format, $mask = '9')
{
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if ( is_array($format) ) 
	{
        if (array_key_exists(strlen($phone), $format))
            $format = $format[strlen($phone)];
        else 
            return $phone;
    } 
	$count = substr_count($format, $mask);
	$n = iconv_strlen($phone) - $count;
	$phone = substr($phone, $n);
	
    $pattern = '/' . str_repeat('([0-9])?', substr_count($format, $mask)) . '(.*)/';
    $format = preg_replace_callback( str_replace('9', $mask, '/([9])/'),  function () use (&$counter) {  return '${' . (++$counter) . '}';  },  $format );
    return ($phone) ? trim(preg_replace($pattern, $format, $phone, 1)) : false;
}

 // Русские в английские
function usam_sanitize_title_with_translit( $title, $rtl_standard = '' ) 
{	
	$gost = [
	   "Є"=>"EH","І"=>"I","і"=>"i","№"=>"#","є"=>"eh",
	   "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G","Д"=>"D",
	   "Е"=>"E","Ё"=>"JO","Ж"=>"ZH",
	   "З"=>"Z","И"=>"I","Й"=>"JJ","К"=>"K","Л"=>"L",
	   "М"=>"M","Н"=>"N","О"=>"O","П"=>"P","Р"=>"R",
	   "С"=>"S","Т"=>"T","У"=>"U","Ф"=>"F","Х"=>"KH",
	   "Ц"=>"C","Ч"=>"CH","Ш"=>"SH","Щ"=>"SHH","Ъ"=>"'",
	   "Ы"=>"Y","Ь"=>"","Э"=>"EH","Ю"=>"YU","Я"=>"YA",
	   "а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d",
	   "е"=>"e","ё"=>"jo","ж"=>"zh",
	   "з"=>"z","и"=>"i","й"=>"jj","к"=>"k","л"=>"l",
	   "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
	   "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"kh",
	   "ц"=>"c","ч"=>"ch","ш"=>"sh","щ"=>"shh","ъ"=>"",
	   "ы"=>"y","ь"=>"","э"=>"eh","ю"=>"yu","я"=>"ya",
	   "—"=>"-","«"=>"","»"=>"","…"=>""
	];
	$iso = [
	   "Є"=>"YE","І"=>"I","Ѓ"=>"G","і"=>"i","№"=>"#","є"=>"ye","ѓ"=>"g",
	   "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G","Д"=>"D",
	   "Е"=>"E","Ё"=>"YO","Ж"=>"ZH",
	   "З"=>"Z","И"=>"I","Й"=>"J","К"=>"K","Л"=>"L",
	   "М"=>"M","Н"=>"N","О"=>"O","П"=>"P","Р"=>"R",
	   "С"=>"S","Т"=>"T","У"=>"U","Ф"=>"F","Х"=>"X",
	   "Ц"=>"C","Ч"=>"CH","Ш"=>"SH","Щ"=>"SHH","Ъ"=>"'",
	   "Ы"=>"Y","Ь"=>"","Э"=>"E","Ю"=>"YU","Я"=>"YA",
	   "а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d",
	   "е"=>"e","ё"=>"yo","ж"=>"zh",
	   "з"=>"z","и"=>"i","й"=>"j","к"=>"k","л"=>"l",
	   "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
	   "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"x",
	   "ц"=>"c","ч"=>"ch","ш"=>"sh","щ"=>"shh","ъ"=>"",
	   "ы"=>"y","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya",
	   "—"=>"-","«"=>"","»"=>"","…"=>""
	]; 	
	switch ( $rtl_standard ) 
	{
		case 'off':
		    return $title;		
		case 'gost':
		    return strtr($title, $gost);
		default: 
		    return strtr($title, $iso);
	}
}

// Конвертирование 10M в число байт
function usam_filesize_to_bytes( $size )
{		
	$l 		= substr( $size, -1 );
	$ret 	= substr( $size, 0, -1 );
	switch( strtoupper( $l ) ) 
	{
		case 'P':
			$ret *= 1024;
		case 'T':
			$ret *= 1024;
		case 'G':
			$ret *= 1024;
		case 'M':
			$ret *= 1024;
		case 'K':
			$ret *= 1024;
	}
	return $ret;
}

// Получить текущий квартал
function usam_get_beginning_quarter( $m = null, $y = null ) 
{
	if ( $m == null )	
		$m = date('m' );
	
	if ( $y == null )	
		$y = date('Y' );
	
	$m = (int)$m;
	
	switch ( $m ) 
	{
		case 1:
		case 2:
		case 3:
			return mktime( 0,0,0,1,1,$y);
		break;
		case 4: 
		case 5: 
		case 6:
			return mktime( 0,0,0,4,1,$y);
		break;
		case 7: 
		case 8: 
		case 9:
			return mktime( 0,0,0,7,1,$y);
		break;
		case 10: 
		case 11: 
		case 12:
			return mktime( 0,0,0,10,1,$y);
		break;
			   
	}
}

function usam_seconds_times( $seconds )
{
	$times = array();
	
	// считать нули в значениях
	$count_zero = false;	
	$periods = array(60, 3600, 86400, 31536000);	
	for ($i = 3; $i >= 0; $i--)
	{
		$period = floor($seconds/$periods[$i]);
		if (($period > 0) || ($period == 0 && $count_zero))
		{
			$times[$i+1] = $period;
			$seconds -= $period * $periods[$i];
			
			$count_zero = true;
		}
	}
	$times[0] = $seconds;
	$times_values = array('сек','мин','час','д','лет');
	$result = '';
	for ($i = count($times)-1; $i >= 0; $i--)
	{
		$result .= $times[$i] . ' ' . $times_values[$i] . ' ';
	}
	return $result;
}

// Обрезать строку до нужной длины
function usam_limit_words( $str, $len = 100, $more = true) 
{
   if ( !$str ) 
	   return '';	 
   $str = trim($str);
   $str = strip_tags(str_replace("\r\n", " ", $str));	
   if ( strlen($str) <= $len ) 
	   return $str;
   $str = mb_substr($str,0,$len);	  
   if ( $str != "" ) 
   {
		if ( !substr_count($str," ") )
		{
			if ( $more ) $str .= " ...";
				return $str;
		}
		while( strlen($str) && ($str[strlen($str)-1] != " ") ) 
		{
			$str = mb_substr($str,0,-1);
		}
		$str = mb_substr($str,0,-1);
		if ( $more ) 
			$str .= " ...";
	}
	return $str;
}

function usam_get_rating( $rating = 0, $class = "rating", $max = 5 ) 
{
	$out = "<div class='$class'>";		
	$selected_star_html = apply_filters( 'usam_selected_star_html', usam_get_svg_icon('star-selected', 'star rating__selected') );	
	$star_html = apply_filters( 'usam_star_html', usam_get_svg_icon('star', 'star') );		
	for ($i = 1; $i <= $max; $i++) 
	{
		if ( $rating >= $i )
			$out .= $selected_star_html;
		else
			$out .= $star_html;
	}
	$out .= '</div>';
	return $out;
}

//Преобразует первый символ строки в верхний регистр для UTF-8
function mb_ucfirst($text) 
{
    return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
}

function usam_encode_string( $string, $hex = false ) 
{
    $chars = str_split( $string );
    $seed = mt_rand( 0, (int) abs( crc32( $string ) / strlen( $string ) ) );

    foreach ( $chars as $key => $char ) 
	{
        $ord = ord( $char );
        if ( $ord < 128 ) 
		{ 
            $r = ( $seed * ( 1 + $key ) ) % 100;
            if ( $r > 75 && $char !== '@' && $char !== '.' );
            else if ( $hex && $r < 25 ) $chars[ $key ] = '%' . bin2hex( $char );
            else if ( $r < 45 ) $chars[ $key ] = '&#x' . dechex( $ord ) . ';'; 
            else $chars[ $key ] = "&#{$ord};";
        }
    }
    return implode( '', $chars );
}

function usam_encode_email( $string ) 
{		
	if ( ! is_string( $string ) || !is_email( $string ) ) 
		return $string;
	$method = 'usam_encode_string';	   
	$regexp = '{ (?:mailto:)? (?: [-!#$%&*+/=?^_`.{|}~\w\x80-\xFF]+  | ".*?" ) \@ (?:[-a-z0-9\x80-\xFF]+(\.[-a-z0-9\x80-\xFF]+)*\.[a-z]+| \[[\d.a-fA-F:]+\] ) }xi';
	$callback = function ( $matches ) use ( $method ) 
	{
		return $method( $matches[ 0 ] );
	};
	return preg_replace_callback( $regexp, $callback, $string );
}

function usam_remove_emoji( $string ) 
{
    $string = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $string);
    $string = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $string);
    $string = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $string);
    $string = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $string);
    $string = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $string);
    return $string;
}

//Изменение раскладки
function usam_switcher($text, $arrow = 1)
{
	$str[0] = ['й' => 'q', 'ц' => 'w', 'у' => 'e', 'к' => 'r', 'е' => 't', 'н' => 'y', 'г' => 'u', 'ш' => 'i', 'щ' => 'o', 'з' => 'p', 'х' => '[', 'ъ' => ']', 'ф' => 'a', 'ы' => 's', 'в' => 'd', 'а' => 'f', 'п' => 'g', 'р' => 'h', 'о' => 'j', 'л' => 'k', 'д' => 'l', 'ж' => ';', 'э' => '\'', 'я' => 'z', 'ч' => 'x', 'с' => 'c', 'м' => 'v', 'и' => 'b', 'т' => 'n', 'ь' => 'm', 'б' => ',', 'ю' => '.','Й' => 'Q', 'Ц' => 'W', 'У' => 'E', 'К' => 'R', 'Е' => 'T', 'Н' => 'Y', 'Г' => 'U', 'Ш' => 'I', 'Щ' => 'O', 'З' => 'P', 'Х' => '[', 'Ъ' => ']', 'Ф' => 'A', 'Ы' => 'S', 'В' => 'D', 'А' => 'F', 'П' => 'G', 'Р' => 'H', 'О' => 'J', 'Л' => 'K', 'Д' => 'L', 'Ж' => ';', 'Э' => '\'', 'я' => 'Z', 'ч' => 'X', 'С' => 'C', 'М' => 'V', 'И' => 'B', 'Т' => 'N', 'Ь' => 'M', 'Б' => ',', 'Ю' => '.'];
	$str[1] = ['q' => 'й', 'w' => 'ц', 'e' => 'у', 'r' => 'к', 't' => 'е', 'y' => 'н', 'u' => 'г', 'i' => 'ш', 'o' => 'щ', 'p' => 'з', '[' => 'х', ']' => 'ъ', 'a' => 'ф', 's' => 'ы', 'd' => 'в', 'f' => 'а', 'g' => 'п', 'h' => 'р', 'j' => 'о', 'k' => 'л', 'l' => 'д', ';' => 'ж', '\'' => 'э', 'z' => 'я', 'x' => 'ч', 'c' => 'с', 'v' => 'м', 'b' => 'и', 'n' => 'т', 'm' => 'ь', ',' => 'б', '.' => 'ю','Q' => 'Й', 'W' => 'Ц', 'E' => 'У', 'R' => 'К', 'T' => 'Е', 'Y' => 'Н', 'U' => 'Г', 'I' => 'Ш', 'O' => 'Щ', 'P' => 'З', '[' => 'Х', ']' => 'Ъ', 'A' => 'Ф', 'S' => 'Ы', 'D' => 'В', 'F' => 'А', 'G' => 'П', 'H' => 'Р', 'J' => 'О', 'K' => 'Л', 'L' => 'Д', ';' => 'Ж', '\'' => 'Э', 'Z' => 'Я', 'X' => 'ч', 'C' => 'С', 'V' => 'М', 'B' => 'И', 'N' => 'Т', 'M' => 'Ь', ',' => 'Б', '.' => 'Ю'];
	return strtr($text,isset($str[$arrow] )? $str[$arrow] :array_merge($str[0],$str[1]));
}


function usam_format_data( $original, $updated ) 
{
	$new = array();
	$keys = array_keys( $original );	
	foreach ( $keys as $key ) 
	{	
		if ( is_array($original[$key]) )
		{	
			if ( !isset($updated[$key]) || !is_array($updated[$key]) )
				$new[$key] = $original[$key];
			elseif ( empty($original[$key]) )				
				$new[$key] = $updated[$key];
			else
				$new[$key] = usam_format_data( $original[$key], $updated[$key] );	
		}
		else
		{ 
			if ( isset($updated[$key]) )
			{ 
				if ( $updated[$key] === 'true' )
					$new[$key] = true;
				elseif ( $updated[$key] === 'false' )
					$new[$key] = false;
				elseif ( is_int($original[$key]) )
					$new[$key] = (int)$updated[$key];
				elseif ( is_float($original[$key]) )
					$new[$key] = (float)$updated[$key];
				else
					$new[$key] = $updated[$key];
			}				
			elseif ( isset($updated->$key) )
			{ 
				if ( $updated->$key === 'true' )
					$new->$key = true;
				elseif ( $updated->$key === 'false' )
					$new->$key = false;
				elseif ( is_int($original[$key]) )
					$new->$key = (int)$updated->$key;
				elseif ( is_float($original[$key]) )
					$new->$key = (float)$updated->$key;
				else
					$new->$key = $updated->$key;
			}
			else
				$new[$key] = $original[$key];
		}
	}
	return $new;
}
?>