<?php
/*
 * PHP-Barcode encodes using genbarcode can encode EAN-13, EAN-8, UPC, ISBN, 39, 128(a,b,c),  I25, 128RAW, CBR, MSI, PLS
 */
class USAM_Barcode
{		
	private function gen_ean_sum( $ean )
	{
		$even = true; 
		$esum = 0; 
		$osum = 0;
		for ($i = strlen($ean)-1;$i >= 0;$i--)
		{
			if ($even) 
				$esum+=$ean[$i];	
			else 
				$osum+=$ean[$i];
			$even=!$even;
		}
		return (10-((3*$esum+$osum)%10))%10;
	}
	
	public function generator_ean13( $number )
	{
		$number = substr($number,0,12);
		$eansum = $this->gen_ean_sum($number);		
		$number .= $eansum;
		return $number;
	}
	
	private function encode_ean( $ean, $encoding = "EAN-13")
	{
		$digits = array(3211,2221,2122,1411,1132,1231,1114,1312,1213,3112);
		$mirror = array("000000","001011","001101","001110","010011","011001","011100","010101","010110","011010");
		$guards = array("9a1a","1a1a1","a1a");

		$ean = trim($ean);
		if (preg_match("#[^0-9]#i",$ean))
		{
			return array("text"=>"Invalid EAN-Code");
		}
		$encoding = strtoupper($encoding);
		if ($encoding=="ISBN")
		{
			if (!preg_match("#^978#", $ean)) 
				$ean="978".$ean;
		}
		if (preg_match("#^978#", $ean)) 
			$encoding="ISBN";
		if (strlen($ean)<12 || strlen($ean)>13)
		{
			return array("text"=>"Invalid $encoding Code (must have 12/13 numbers)");
		}
		$ean = $this->generator_ean13( $ean );
		$line=$guards[0];
		for ($i = 1;$i<13;$i++)
		{
			$str=$digits[$ean[$i]];
			if ($i<7 && $mirror[$ean[0]][$i-1]==1) $line.=strrev($str); else $line.=$str;
			if ($i==6) $line.=$guards[1];
		}
		$line.=$guards[2];

		$pos = 0;
		$text="";
		for ($a = 0;$a<13;$a++)
		{
			if ($a>0) 
				$text.=" ";
			$text.="$pos:12:{$ean[$a]}";
			if ($a==0) 
				$pos+=12;
			else 
				if ($a==6) 
					$pos+=12;
				else 
					$pos+=7;
		}
		return array( "encoding" => $encoding, "bars" => $line, "text" => $text	);
	}
	
	public function outimage($text, $bars, $scale  =  1, $mode  =  "png", $total_y  =  0, $space  =  '')
	{
		$bar_color  =  array(0,0,0);
		$bg_color   =  array(255,255,255);
		$text_color =  array(0,0,0);
		
		$font_loc  =  dirname(__FILE__)."/"."FreeSansBold.ttf";
		if ($scale<1) $scale = 2;
		$total_y=(int)($total_y);
		if ($total_y < 1) 
			$total_y = (int)$scale * 60;
		if (!$space)
		  $space = array('top'=>2*$scale,'bottom'=>2*$scale,'left'=>2*$scale,'right'=>2*$scale);
		
		$xpos = 0;
		$width = true;
		for ($i = 0; $i < strlen($bars); $i++)
		{
			$val = strtolower($bars[$i]);
			if ($width){
				$xpos+=$val*$scale;
				$width = false;
				continue;
			}
			if (preg_match("#[a-z]#", $val))
			{			
				$val = ord($val)-ord('a')+1;
			} 
			$xpos+=$val*$scale;
			$width = true;
		}
		$total_x=( $xpos )+$space['right']+$space['right'];
		$xpos=$space['left'];
		$im  =  imagecreate($total_x, $total_y);
		$col_bg = ImageColorAllocate($im,$bg_color[0],$bg_color[1],$bg_color[2]);
		$col_bar = ImageColorAllocate($im,$bar_color[0],$bar_color[1],$bar_color[2]);
		$col_text = ImageColorAllocate($im,$text_color[0],$text_color[1],$text_color[2]);
		$height = round($total_y-($scale*10));
		$height2 = round($total_y-$space['bottom']);

		$width = true;
		for ($i = 0;$i<strlen($bars);$i++)
		{
			$val = strtolower($bars[$i]);
			if ($width)
			{
				$xpos+=$val*$scale;
				$width = false;
				continue;
			}
			if (preg_match("#[a-z]#", $val))
			{ /* tall bar */
				$val = ord($val)-ord('a')+1;
				$h=$height2;
			} else $h=$height;
			imagefilledrectangle($im, $xpos, $space['top'], $xpos+($val*$scale)-1, $h, $col_bar);
			$xpos+=$val*$scale;
			$width = true;
		}
		$chars  =  explode(" ", $text);
		reset($chars);
		while (list($n, $v)=each($chars))
		{
			if (trim($v))
			{
				$inf = explode(":", $v);
				$fontsize=$scale*($inf[1]/1.8);
				$fontheight=$total_y-($fontsize/2.7)+2;
				@imagettftext($im, $fontsize, 0, $space['left']+($scale*$inf[0])+2,
				$fontheight, $col_text, $font_loc, $inf[2]);
			}
		}
		$mode  =  strtolower($mode);
		if ($mode=='jpg' || $mode=='jpeg')
		{
			header("Content-Type: image/jpeg; name=\"barcode.jpg\"");
			imagejpeg($im);
		} 
		else if ($mode=='gif')
		{
			header("Content-Type: image/gif; name=\"barcode.gif\"");
			imagegif($im);
		} 
		else 
		{
			header("Content-Type: image/png; name=\"barcode.png\"");
			imagepng($im);
		}
	}

	/*
	 * Возвращает штрих-код, как обычный текст
	 */
	public function outtext($code, $bars)
	{
		$width = true;
		$xpos = $heigh2 = 0;
		$bar_line = "";
		for ($i = 0; $i < strlen($bars);$i++)
		{
			$val = strtolower($bars[$i]);
			if ($width)
			{
				$xpos+=$val;
				$width = false;
				for ($a = 0;$a<$val;$a++) 
					$bar_line.="-";
				continue;
			}
			if ( preg_match("#[a-z]#", $val) )
			{
				$val = ord($val)-ord('a')+1;
				$h=$heigh2;
				for ($a = 0;$a<$val;$a++) 
					$bar_line.="I";
			} 
			else 
				for ($a = 0;$a<$val;$a++) 
					$bar_line.="#";
			$xpos+=$val;
			$width = true;
		}
		return $bar_line;
	}

	public function outhtml($code, $bars, $scale  =  1, $total_y  =  0, $space  =  '' )
	{ 
		$path  =  USAM_URL."/admin/includes/barcode";
		$total_y  =  (int)($total_y);
		if ($scale < 1) 
			$scale  =  1;
		
		if ($total_y < 1) 
			$total_y =  (int)$scale * 40;
		if ( !$space )
		  $space = array( 'top' => 2*$scale, 'bottom' => 2*$scale, 'left' => 2*$scale, 'right' => 2*$scale );

		$height  =  round($total_y-($scale*10));
		$height2 = round($total_y)-$space['bottom'];
		$height_top  =  'style="height="'.$height.'"';	
		$out  =  "<table class=barcode style='border:0;margin:0;padding:0;width:104px;'>";
		$width  =  true;	
		$numeric = false;
		$j = 1;	
		for ($i = 0; $i < strlen($bars); $i++)
		{
			$val  =  strtolower($bars[$i]);
			if (preg_match("#[a-z]#", $val))
			{
				$val = ord($val)-ord('a')+1;
				$h  =  $height2;
			}
			else 
				$h = $height;				
			$w = $val*$scale;
			
			if ( $i == 0 )
			{
				$out .= "<td style='border:0;margin:0;padding:0;vertical-align: top'>";
			}
			elseif ( $i == 1 )
			{				
				$out .= "<div style='margin:0;vertical-align:bottom;font-size:13px;font-family:Times New Roman,Arial'>".$code[0]."</div></td><td style='border:0;margin:0;padding:0;vertical-align: top'>";
			}		
			elseif ( $i == 4 || $i == 32 )
			{
				$figures = '';
				$out .= "</td><td style='border:0;margin:0;padding:0;vertical-align: top'>";
				$numeric = true;				
			}
			elseif ( $numeric && $h == $total_y )
			{
				$out .= "<div style='margin:0;vertical-align: bottom;font-size:13px;font-family:Times New Roman,Arial'>".$figures."</div></td><td style='border:0;margin:0;padding:0;vertical-align: top'>";
				$numeric = false;	
			}	
			elseif ( $numeric && $i % 4 === 0 )
			{
				$figures .= $code[$j];
				$j++;
			}		
			if ( $i > 1 && $i < 4 || $i >= 30 && $i < 32 || $i > 56 )
				$h = $total_y;
			if ( $width )
			{			
				if ( $w > 0 ) 
					$out.='<img src="'.$path.'/white.png" height="'.$h.'" width="'.$w.'" align="top"/>';
				$width = false;
			}
			else
			{							
				if ($w > 0) 
					$out.='<img src="'.$path.'/black.png" height="'.$h.'" width="'.$w.'" align="top"/>';				
				$width  =  true;
			}
		}
		$out.= '</td></tr></table>';	
		return $out;
	}

	/*  Кодирует $code с помощью $encoding genbarcode	 */
	public function encode_genbarcode( $code, $encoding )
	{
		$file = USAM_FILE_PATH."/admin/includes/barcode/genbarcode";
		if ( !file_exists($file))
			return false;
		
		if ( preg_match("#^ean$#i", $encoding) && strlen($code)==13 ) 
			$code = substr($code,0,12);
		if ( !$encoding ) 
			$encoding = "ANY";
		$encoding  =  preg_replace("#[|\\\\]#", "_", $encoding);
		$code  =  preg_replace("#[|\\\\]#", "_", $code);
		$cmd  =  $file." ".escapeshellarg($code)." " .escapeshellarg(strtoupper($encoding))."";		
		$fp  =  popen($cmd, "r");
		if ($fp)
		{
			$bars = fgets($fp, 1024);
			$text = fgets($fp, 1024);
			$encoding = fgets($fp, 1024);
			pclose($fp);
		} 
		else 
			return false;
		$ret = array(	"encoding" => trim($encoding),	"bars" => trim($bars),	"text" => trim($text) );
		if (!$ret['encoding']) 
			return false;
		if (!$ret['bars']) 
			return false;
		if (!$ret['text']) 
			return false;
		return $ret;
	}

	/*   Вы можете использовать следующие кодировки:
	 *   ANY    choose best-fit (default)
	 *   EAN    8 or 13 EAN-Code
	 *   UPC    12-digit EAN 
	 *   ISBN   isbn numbers (still EAN-13) 
	 *   39     code 39 
	 *   128    code 128 (a,b,c: autoselection) 
	 *   128C   code 128 (compact form for digits)
	 *   128B   code 128, full printable ascii 
	 *   I25    interleaved 2 of 5 (only digits) 
	 *   128RAW Raw code 128 (by Leonid A. Broukhis)
	 *   CBR    Codabar (by Leonid A. Broukhis) 
	 *   MSI    MSI (by Leonid A. Broukhis) 
	 *   PLS    Plessey (by Leonid A. Broukhis)
	 */
	public function encode( $code, $encoding)
	{
		if (((preg_match("#^ean$#i", $encoding) && ( strlen( $code )==12 || strlen($code)==13))) || (($encoding) && (preg_match("#^isbn$#i", $encoding)) && (( strlen($code)==9 || strlen($code)==10) || (((preg_match("#^978#", $code) && strlen($code)==12) || (strlen($code)==13))))) || (( !isset($encoding) || !$encoding || (preg_match("#^ANY$#i", $encoding) )) && (preg_match("#^[0-9]{12,13}$#", $code)))	)
			$bars  = $this->encode_ean($code, $encoding);
		else 
			$bars = $this->encode_genbarcode($code, $encoding);
		return $bars;
	}

	public function print($code, $encoding="ANY", $scale = 2 ,$mode =  "png" )
	{ 
		$bars  = $this->encode($code, $encoding);	
		if (!$bars) 
			return;
		if (!$mode) 
			$mode="png";
		if (preg_match("#^(text|txt|plain)$#i", $mode)) 
			echo $this->outtext($bars['text'],$bars['bars']);
		elseif (preg_match("#^(html|htm)$#i", $mode)) 
			echo $this->outhtml($code, $bars['bars'], $scale,0, 0);
		else 
			$this->outimage($bars['text'],$bars['bars'],$scale, $mode);		
		return $bars;
	}
}
?>