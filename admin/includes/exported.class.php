<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx; 
			
class USAM_Exported_Table
{
	private $name = 'exported';
	
    public function __construct( $name )
	{	
		$this->name = $name;
    }	
	
	// Выгрузка в csv-файл			
	function csv( $items, $header )
	{							
		$output = '';
		foreach($header as $key => $value)
		{		
			$output .= "\"" . $value . "\","; 	
		}
		$output .= "\n"; // Конец строки
		foreach($items as $item)
		{				
			foreach($header as $key => $val)
			{
				if ( isset($item->$key) )
					$output .= strip_tags($item->$key); 
				$output .= ",";
			}
			$output .= "\n"; // Конец строки
		}
		return $output;
	}
	
	function excel( $items, $header )
	{				
		require_once(USAM_FILE_PATH . "/resources/PhpSpreadsheet/vendor/autoload.php");
		
		$spreadsheet = new Spreadsheet();		
		$sheet = $spreadsheet->getActiveSheet();
					
		$writer_i = 1;	
		
		$sheet->getStyleByColumnAndRow(1, $writer_i)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
		$sheet->getStyleByColumnAndRow(1, $writer_i)->getFont()->setSize(20);
		$sheet->mergeCells('A1:Q1');
		$sheet->setCellValueByColumnAndRow(1, $writer_i, $this->name);	
		
		$writer_i++;
		$i = 1;				
		foreach($header as $key => $value)
		{				
			$sheet->getStyle($writer_i)->getAlignment()->setHorizontal( \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER); 			
			$sheet->getStyleByColumnAndRow($i, $writer_i)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
			$sheet->getStyleByColumnAndRow($i, $writer_i)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('81b1d9');
			$sheet->setCellValueByColumnAndRow($i, $writer_i, strip_tags($value));					
			$i++;			
		}		
		$writer_i++;			
		foreach($items as $ar)
		{
			$j = 1;					
			$sheet->getStyle($writer_i)->getAlignment()->setHorizontal( \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT); // выравнивание
			foreach($header as $key => $val)
			{	
				if ( isset($ar->$key) )
					$value = strip_tags($ar->$key);
				elseif ( isset($ar[$key]) )
					$value = strip_tags($ar[$key]);
				$sheet->getStyleByColumnAndRow($j, $writer_i)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
				$sheet->setCellValueByColumnAndRow($j, $writer_i, $value ); 						
				$j++;
			}			
			$writer_i++;
		}		
		$max_col = $sheet->getHighestColumn();
		/* автоширина */
		for ($col = 'A'; $col <= $max_col; $col++) {
		   $sheet->getColumnDimension($col)->setAutoSize(true);
		}		
		$writer = new Xlsx( $spreadsheet );		
		$writer->save('php://output');	
	}
}