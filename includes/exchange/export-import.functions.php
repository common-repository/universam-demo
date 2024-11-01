<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\{Font, Border, Alignment};

// загрузка из Exel
function usam_read_exel_file( $filepath, $args = [] )
{ 
	$args = array_merge(['sheetname' => '', 'start_line' => 0, 'count' => 0], $args );	
	$results = array();	
	if (  file_exists($filepath) )
	{
		require_once(USAM_FILE_PATH . "/resources/PhpSpreadsheet/vendor/autoload.php");
		$reader = IOFactory::createReaderForFile( $filepath );
		$reader->setReadDataOnly( false );
        $excel = $reader->load( $filepath ); 
		$results = array();		
		foreach ($excel->getWorksheetIterator() as $worksheet)
		{
			if( $args['sheetname'] && $worksheet->getTitle() != $args['sheetname'] )
				continue;
			
			$images = [];
			$i = 0;				 
			/*
			foreach ($worksheet->getDrawingCollection() as $drawing) 
			{				 
				if ( $drawing instanceof MemoryDrawing ) 				
				{
					ob_start();
					call_user_func($drawing->getRenderingFunction(), $drawing->getImageResource() );
					$imageContents = ob_get_contents();
					ob_end_clean();
					switch ($drawing->getMimeType()) 
					{
						case MemoryDrawing::MIMETYPE_PNG :
							$extension = 'png';
						break;
						case MemoryDrawing::MIMETYPE_GIF:
							$extension = 'gif';
						break;
						case MemoryDrawing::MIMETYPE_JPEG :
							$extension = 'jpg';
						break;
					}
					$myFileName = '00_Image_'.++$i.'.'.$extension;
					$images[$drawing->getCoordinates()] = $imageContents;		
				}
				else
				{					
					$zipReader = fopen($drawing->getPath(), 'r');
					$imageContents = '';
					while (!feof($zipReader)) {
						$imageContents .= fread($zipReader, 1024);
					}
					fclose($zipReader);
					$extension = $drawing->getExtension();					
					//$images[$drawing->getCoordinates()] = $imageContents;					
					$drawing->getFilename();						
				}				
			}	*/	
			$i = 0;
			foreach ($worksheet->getRowIterator() as $row) 
			{				
				$i++;
				if ( $args['start_line'] > 1 && $args['start_line'] > $i )
					continue;
				
				$cellIterator = $row->getCellIterator();
				$cellIterator->setIterateOnlyExistingCells( false ); // true не выбирать пустые ячейки
				$data_row = [];				
				foreach ($cellIterator as $key => $cell) 
				{		
					$value = $cell->getValue();
					if( is_string($value) && !str_contains($value, '=HYPERLINK') )
					{
						$url = $cell->getHyperlink()->getUrl();
						if ( $url )
							$data_row[] = $url;
					}
			//		$coordinate = $cell->getCoordinate();						
			//		$data_row[] = isset($images[$coordinate]) ? $images[$coordinate] : $value;
			
					if( $value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText ) 
					{
						$s = '';
						foreach ($value->getRichTextElements() as $richTextElement) 
						{
							$s .= $richTextElement->getText();
						}						
						$data_row[] = $s;
					}					
					else
						$data_row[] = $cell->getFormattedValue();
				}	
				if ( $i < 30 )
					$results[] = $data_row;
				elseif ( array_filter($data_row, function($value) { return !is_null($value); }) )
					$results[] = $data_row;
				if ( $args['count'] && $args['count'] <= $i )
					break 2;
			}
		}
	}		
	return $results;
}

// Выгрузка в Exel
function usam_write_exel_file( $data_export, $args = [] )
{ 	
	$args = array_merge(['load_file' => null, 'headers' => [], 'list_title' => __("Данные","usam"), 'header_data' => [], 'file_type' => 'xlsx'], $args );	
	require_once(USAM_FILE_PATH . "/resources/PhpSpreadsheet/vendor/autoload.php");	
	$spreadsheet = new Spreadsheet();		
	//	if ( $args['file_type'] == 'xlsx' )	
	$display_headers = true;
	$row = 1;
	if ( $args['load_file'] && file_exists($args['load_file']) )
	{	   	 
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load( $args['load_file'] );		
		$sheet = $spreadsheet->getActiveSheet();
		$row = $sheet->getHighestRow();	
		if ( $row )
			$display_headers = false;
		$row++;
	}
	else
	{
		$sheet = $spreadsheet->getActiveSheet()->setTitle($args['list_title']);
	}	
	$j = 1;	
	if ( $args['header_data'] && $display_headers )
	{			
		foreach( $args['header_data'] as $key => $data )
		{	
			$style = [];
			if ( !empty($data['font']) )
				$style['font'] = $data['font'];	
			
			if ( $style )
				$sheet->getStyleByColumnAndRow($j, $row)->applyFromArray( $style )->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
			else
				$sheet->getStyleByColumnAndRow($j, $row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);		
			$sheet->getStyleByColumnAndRow($j, $row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
			$sheet->setCellValueByColumnAndRow( $j, $row, $data['value'] );
			if ( isset($data['merge_cells']) )
				$sheet->mergeCells( $data['merge_cells']['start'].$row.':'.$data['merge_cells']['end'].$row );
			$row++;
		}
	}	
	if ( $args['headers'] )
	{
		foreach( $args['headers'] as $key => $item )
		{
			if ( isset($item['value']) )
				break;
			else
				$args['headers'][$key] = ['value' => $item]; 
		}
		if ( $display_headers )
		{
			$style = [
				// Шрифт
				'font' => ['name' => 'Times New Roman', 'size' => 12, 'color' => ['rgb' => '4b711d']],
				// Выравнивание
				'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
				// Заполнение цветом
				'fill' => ['fillType' => PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'e6f0c0']],
				'borders' => [
					'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => '4b711d']],
					'left' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => '4b711d']],
					'right' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => '4b711d']],
					'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => '4b711d']],
				]
			];
			foreach( $args['headers'] as $key => $header )
			{				
				$sheet->getStyleByColumnAndRow($j, $row)->applyFromArray( $style )->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);		
				$sheet->getStyleByColumnAndRow($j, $row)->getAlignment()->setWrapText(true);
				$sheet->setCellValueByColumnAndRow( $j, $row, stripcslashes($header['value']) );
				$j++;
			}	
		}
		$j = 1;			
		$row++;
	}
	elseif ( !empty($data_export[0]) )
	{
		foreach( $data_export[0] as $key => $item )
		{
			if ( isset($item['value']) )
				$args['headers'][$key] = $item;
			else
				$args['headers'][$key] = ['value' => $item];
		}		
	}	
	if ( !empty($data_export) )
	{	
		foreach($data_export as $item)
		{				
			$j = 1;				
			foreach($args['headers'] as $key => $header)
			{				
				if ( isset($item[$key]) )
					$data = $item[$key];
				elseif (isset($item[$header['value']]))
					$data = $item[$header['value']];
				else
					$data = ['value' => ''];				
				if ( !is_array($data) )
					$data = ['value' => $data];	
	
				$data['value'] = $data['value'] === false ? '' : trim(stripcslashes($data['value']));
				if ( isset($data['format']) )
				{
					switch ( $data['format'] ) 
					{
						case 'general':						
							$sheet->getStyleByColumnAndRow($j, $row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_GENERAL);	
						break;	
						case 'number':						
							$sheet->getStyleByColumnAndRow($j, $row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);	
						break;
						case 'fraction':	// дробное число					
							$sheet->getStyleByColumnAndRow($j, $row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);	
						break;		
						case 'procent':						
							$sheet->getStyleByColumnAndRow($j, $row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE);	
						break;							
						case 'text':														
						default:
							$sheet->getStyleByColumnAndRow($j, $row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);	
						break;
					}	
				}
				elseif ( !empty($data['value']) && $data['value'] === '=' )
					$sheet->getStyleByColumnAndRow($j, $row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_GENERAL);	
				else
					$sheet->getStyleByColumnAndRow($j, $row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);		
							
				$style = [];				
				if ( !empty($data['height']) )
				{
					$sheet->getRowDimension($row)->setRowHeight($data['height'], 'pt');					
				}				
				if ( !empty($data['font']) )
					$style['font'] = $data['font'];		//'bold' => true	
				if ( !empty($data['alignment']) )
				{
					$style['alignment'] = $data['alignment'];
					if ( !empty($data['alignment']['wrap']) )
						$sheet->getStyleByColumnAndRow($j, $row)->getAlignment()->setWrapText(true);
				}			
				if ( !empty($data['color']) )
				{
					$style['fill']['fillType'] = PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID;	
					$style['fill']['startColor']['rgb'] = $data['color']['rgb'];	
				}			
				if ( !empty($data['border']) )
				{
					$style['borders']['top']['color']['rgb'] = $data['border']['color'];		
					$style['borders']['right']['color']['rgb'] = $data['border']['color'];	
					$style['borders']['bottom']['color']['rgb'] = $data['border']['color'];	
					$style['borders']['left']['color']['rgb'] = $data['border']['color'];	

					$style['borders']['top']['borderStyle'] = Border::BORDER_THIN;					
					$style['borders']['right']['borderStyle'] = Border::BORDER_THIN;					
					$style['borders']['bottom']['borderStyle'] = Border::BORDER_THIN;					
					$style['borders']['left']['borderStyle'] = Border::BORDER_THIN;					
				}
				if ( !empty($data['borders']) )
				{
					if ( !empty($data['borders']['top']) )
					{
						$style['borders']['top']['borderStyle'] = Border::BORDER_THIN;	
						$style['borders']['top']['color']['rgb'] = $data['borders']['top']['color'];				
					}
					if ( !empty($data['borders']['right']) )
					{
						$style['borders']['right']['borderStyle'] = Border::BORDER_THIN;	
						$style['borders']['right']['color']['rgb'] = $data['borders']['right']['color'];				
					}
					if ( !empty($data['borders']['bottom']) )
					{
						$style['borders']['bottom']['borderStyle'] = Border::BORDER_THIN;	
						$style['borders']['bottom']['color']['rgb'] = $data['borders']['bottom']['color'];				
					}
					if ( !empty($data['borders']['left']) )
					{
						$style['borders']['left']['borderStyle'] = Border::BORDER_THIN;	
						$style['borders']['left']['color']['rgb'] = $data['borders']['left']['color'];				
					}					
				}
				if ( $key === 'exel_image' && !empty($data['value']) )
				{	
					$drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
					$drawing->setPath( $data['value'] );
					$drawing->setName('Image'  . $row);
					$drawing->setDescription('Image');
					$drawing->setHeight(80);					
					$coordinate = $sheet->getCellByColumnAndRow($j, $row)->getParent()->getCurrentCoordinate();
					$drawing->setCoordinates( $coordinate );
					$drawing->setWorksheet($sheet);
					$drawing->setOffsetX(6);
					$drawing->setOffsetY(6);
					$sheet->getRowDimension($row)->setRowHeight(65, 'pt');					
				}
				else
					$sheet->setCellValueByColumnAndRow( $j, $row, $data['value'] ); // записываем данные массива в ячейку	
				if ( !empty($style) )	
				{
					$sheet->getStyleByColumnAndRow($j, $row)->applyFromArray( $style );
				}			
				$j++;									
			}	
			$row++;
		}	
	}
	if( $args['headers'] && $display_headers )
	{ 			
		$count = count($args['headers']);		
		$j = 0;			
		for ($i = 'A'; $i <= $sheet->getHighestColumn(); $i++) 
		{			
			$header = current($args['headers']);
			if ( !empty($header['width']) )
			{
				$width = $header['width'];
				$sheet->getColumnDimension($i)->setWidth( $width );
			}
			elseif ( key($args['headers']) == 'exel_image' )
				$sheet->getColumnDimension($i)->setWidth(13, 'pt');
			else
				$sheet->getColumnDimension($i)->setAutoSize(TRUE);
			if ( $j >= $count )
				break;
			$j++;
			$header = next($args['headers']); 		
		}
	}
	$writer = new Xlsx( $spreadsheet );
	return $writer;	 
}

use Dompdf\Dompdf;


function usam_export_to_pdf( $html, $args = array() )
{
	if ( empty($html) )
	{
		$html = '
		<html>
			<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head>
			<body><p>'.__('Нет данных для отображения','usam').'</p></body>
        </html>';	
	}
	require_once(USAM_FILE_PATH.'/resources/dompdf/autoload.inc.php');	
	$dompdf = new DOMPDF(array('enable_remote' => true));	
	
	$paper = !empty($args['paper'])?$args['paper']:'a4';
	$orientation = !empty($args['orientation'])?$args['orientation']:'portrait'; //landscape portrait
		
	$dompdf->set_paper( $paper, $orientation );
	$dompdf->load_html( $html );

	$dompdf->render();	
	return $dompdf->output();
}

function usam_get_qr( $string, $args = [] ) 
{	
	$default = ['size' => 2, 'margin' => 2];
	$args = array_merge( $default, $args );		
	$text = '';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	$filename = wp_tempnam( 'qr' );	
	try 
	{
		require_once(USAM_FILE_PATH . "/resources/phpqrcode/qrlib.php");	
		QRcode::png($string,  $filename, 'L', $args['size'], $args['margin']);
		$text = file_get_contents( $filename );
		$text = 'data:image/png;base64,'.base64_encode($text);
	} 
	catch (Exception $e) {
		usam_log_file( $e->getMessage() );
	}
	unlink( $filename );
	return $text;
}

function usam_read_file( $file_path, $_encoding = 'auto', $args = [] ) 
{		
	$extension = usam_get_extension( $file_path ); // узнать формат файла	
	switch ( $extension ) 
	{
		case 'xls':	
		case 'xlsx':
			$results = usam_read_exel_file( $file_path, $args );	
		break;
		case 'txt':
		case 'csv':									
			$results = usam_read_txt_file( $file_path, '', $args );
		break;							
		default:
			$results = array();	
		break;		
	}							
	if ( $_encoding != 'utf-8' && $_encoding != 'UTF-8' )
	{
		foreach ( $results as &$row)
		{		
			foreach ( $row as $key => $value)
			{
				switch( $_encoding )
				{				
					case 'windows-1256':
					case 'windows-1251':
						$row[$key] = iconv($_encoding, "utf-8//TRANSLIT//IGNORE", $value);
					break;
					case 'utf-8-bom': //Удалить BOM из строки
						if(substr($row[$key], 0, 3) == pack('CCC', 0xef, 0xbb, 0xbf))
							$row[$key] = substr($value, 3);	
					break;	
					case '': 					
					case 'auto': 						
						$encoding = mb_detect_encoding($value, "auto");		
						if ( $encoding == 'ASCII' )
							$row[$key] = mb_convert_encoding($value, 'UTF-8', 'windows-1251');	
						elseif ( $encoding && $encoding != 'UTF-8' )
							$row[$key] = mb_convert_encoding($value, 'UTF-8', $encoding);
					break;									
					default:
						$row[$key] = mb_convert_encoding($value, 'UTF-8', $_encoding);
					break;
				}
			}	
		}
	}
	return $results;
}

// Загрузить данные из файла
function usam_read_txt_file( $file_path, $delimiter = '', $args = [] ) 
{		
	$args = array_merge(['start_line' => 0, 'count' => 0, 'encoding' => ''], $args );	
	ini_set( 'auto_detect_line_endings', 1 );		
	$results = array();
	if (file_exists($file_path)) 
	{ 
		$file = file_get_contents( $file_path );	
		if ( $args['encoding'] == '' )
			$args['encoding'] = mb_detect_encoding($file, "auto"); 							
		if ( $args['encoding'] != 'UTF-8' )
		{ 
			$encodings = array( 'ASCII' => 'windows-1251' );
			if ( isset($encodings[$args['encoding']]) )
				$args['encoding'] = $encodings[$args['encoding']];
			$file = iconv($args['encoding'], 'UTF-8//IGNORE', $file);	
		}
		elseif ( $args['encoding'] == 'UTF-8-BOM' )
		{					
			if(substr($file, 0, 3) == pack('CCC', 0xef, 0xbb, 0xbf))
				$file = substr($file, 3);
		}
	//	$data = explode(PHP_EOL, $file);			
		$data = preg_split('/\r\n|\r|\n/', $file);		
		if ( empty($delimiter) )
			$delimiter = usam_define_delimiter_file( $data );
		$i = 0;
		if ( empty($delimiter) )
		{
			foreach($data as $key => $row)
			{
				$i++;
				if ( $args['start_line'] > 1 && $args['start_line'] > $i )
					continue;
				$results[] = array( $row );
				unset($data[$key]);
				if ( $args['count'] && $args['count'] <= $i )
					break;
			}
		}
		else
		{
			foreach($data as $key => $row)
			{
				$i++;
				if ( $args['start_line'] > 1 && $args['start_line'] > $i )
					continue;
				$results[] = str_getcsv($row, $delimiter);	
				unset($data[$key]);
				if ( $args['count'] && $args['count'] <= $i )
					break;
			}
		}
	}	
	return $results;
}

function usam_get_doc_document_from_template( $template, $data, $result_file = 'result' )
{
	require_once(USAM_FILE_PATH . "/resources/phpoffice_phpword/vendor/autoload.php");								
	$phpWord = new \PhpOffice\PhpWord\PhpWord();
	$document = $phpWord->loadTemplate( $template ); 	
	$document->setValues( $data );
	$file = USAM_FILE_DIR.$result_file.'.docx';
	$document->saveAs( $file );
	return $file;
}

function usam_define_delimiter_file( $data )
{ 
	$delimiter = '';
	if ( !empty($data) )
	{ 
		$formats = array( ';','","',',','|','\t' );			
		foreach($formats as $format)
		{				
			$i = 0;
			$a = 0;
			$ok = true;
			foreach($data as $row)
			{							
				if ( empty($row) )
					continue;				
			
				$b = count(str_getcsv($row, $format));	
				if ( $b > 1 && ($a == $b || $a == 0 ) )
					$a = $b;
				else
				{				
					$ok = false;
					break;
				}				
				$i++;
				if ( $i > 30 )
					break;
			}	
			if ( $ok )
			{
				$delimiter = $format;
				break;
			}
		}	
		if ( $delimiter == '","' )
			$delimiter = ',';
	}	
	return $delimiter;
}

function usam_start_import_products()
{	
	add_filter( 'usam_update_post_category_sale_count', '__return_false' );						
	add_filter( 'http_request_host_is_external', '__return_false', 10, 3 );	
		
	if ( get_option('usam_website_type', 'store' ) == 'marketplace' )		
		usam_update_counter_sellers_products_quantity( false );		//Счетчик товаров продавца
			
	remove_action( 'set_object_terms', ['USAM_Product_Filters', 'usam_recalculate_price_product_set_terms'], 10, 6 );
	remove_action( 'transition_post_status', '_usam_action_transition_post_status', 10, 3 );	
	remove_action( 'transition_post_status', '__clear_multi_author_cache', 10 );
	
	wp_defer_term_counting( true );		//Позволяет отложить пересчет количества постов для термина
//	wp_suspend_cache_addition( true );	 //Временно приостанавливает добавление объектов в объектный кэш.
	wp_suspend_cache_invalidation( true );
}

function usam_end_import_products()
{
	wp_cache_flush();
//	wp_suspend_cache_addition( false );		
	wp_suspend_cache_invalidation( false );		
	wp_defer_term_counting( false );	
	clean_taxonomy_cache( 'usam-category' );
	
	if ( get_option('usam_website_type', 'store' ) == 'marketplace' )		
		usam_update_counter_sellers_products_quantity( true );		//Счетчик товаров продавца
}
?>