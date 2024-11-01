<?php
/**
 * Популярные цвета на фото
 */
class GetMostCommonColors
{
	private $image;
	
	public function __construct( $image )
	{
		$this->image = $image;
	}
	
	/**
	 * Возвращает цвет изображения в массив, упорядоченный в порядке убывания, где коды цвета и количество пикселей занятых цветом.
	 */
	public function get_color( $colors_return = 0 )
	{
		$hexarray = array();
		if (isset($this->image) && file_exists($this->image) )
		{
			$PREVIEW_WIDTH    = 150;  //WE HAVE TO RESIZE THE IMAGE, BECAUSE WE ONLY NEED THE MOST SIGNIFICANT COLORS.
			$PREVIEW_HEIGHT   = 150;
			$size = GetImageSize( $this->image );
			$scale=1;
			if ($size[0]>0)
			$scale = min($PREVIEW_WIDTH/$size[0], $PREVIEW_HEIGHT/$size[1]);
			if ($scale < 1)
			{
				$width = floor($scale*$size[0]);
				$height = floor($scale*$size[1]);
			}
			else
			{
				$width = $size[0];
				$height = $size[1];
			}
			$image_resized = imagecreatetruecolor($width, $height);
			if ($size[2]==1)
			$image_orig=imagecreatefromgif($this->image);
			if ($size[2]==2)
			$image_orig=imagecreatefromjpeg($this->image);
			if ($size[2]==3)
			$image_orig=imagecreatefrompng($this->image);
			imagecopyresampled($image_resized, $image_orig, 0, 0, 0, 0, $width, $height, $size[0], $size[1]); //WE NEED NEAREST NEIGHBOR RESIZING, BECAUSE IT DOESN'T ALTER THE COLORS
			$im = $image_resized;
			$imgWidth = imagesx($im);
			$imgHeight = imagesy($im);
			for ($y=0; $y < $imgHeight; $y++)
			{
				for ($x=0; $x < $imgWidth; $x++)
				{
					$index = imagecolorat($im,$x,$y);
					$Colors = imagecolorsforindex($im,$index);
					$Colors['red']=intval((($Colors['red'])+15)/32)*32;    //ROUND THE COLORS, TO REDUCE THE NUMBER OF COLORS, SO THE WON'T BE ANY NEARLY DUPLICATE COLORS!
					$Colors['green']=intval((($Colors['green'])+15)/32)*32;
					$Colors['blue']=intval((($Colors['blue'])+15)/32)*32;
					if ($Colors['red']>=256)
					$Colors['red']=240;
					if ($Colors['green']>=256)
					$Colors['green']=240;
					if ($Colors['blue']>=256)
					$Colors['blue']=240;
					$hexarray[]=substr("0".dechex($Colors['red']),-2).substr("0".dechex($Colors['green']),-2).substr("0".dechex($Colors['blue']),-2);
				}
			}
			$hexarray=array_count_values($hexarray);
			natsort($hexarray);
			$hexarray=array_reverse($hexarray,true);
			
			if ( $colors_return )
				$hexarray = array_slice($hexarray, 0, $colors_return);
			
			return $hexarray;

		}
		else 
			new WP_Error( 'usam_get_color', __('Метод get_color класса GetMostCommonColors вернул ошибку. Файл не найден.', 'usam') );
		
		return $hexarray;
	}
	
	public function get_group_color( $colors_return = 0 )
	{
		global $group_rgb;
		$hexarray = $this->get_color( $colors_return );			
		$group_color = array();		
		require_once( USAM_FILE_PATH . '/includes/media/group_rgb.php' );
	
		foreach ( $hexarray as $hex => $pix ) 	
		{
			$rgb = hexToRgb( $hex );
		//	print_r( $rgb);
			$fi = array();
			foreach ( $group_rgb as $key => $value ) 	
			{
				$fi[$key] = 30*pow($value['red']-$rgb['red'],2)+59*pow($value['green']-$rgb['green'],2)+11*pow($value['blue']-$rgb['blue'],2);
			}
			asort($fi);
			$group_color[] = key($fi);
		}
		$group_color = array_unique($group_color);
		return $group_color;
	}
}


 // перевод цвета из HEX в RGB
function hexToRgb( $color ) 
{
    // проверяем наличие # в начале, если есть, то отрезаем ее
    if ($color[0] == '#') {
        $color = substr($color, 1);
    }   
	$color = (string)$color;
    // разбираем строку на массив
    if (strlen($color) == 6) 
	{ // если hex цвет в полной форме - 6 символов
        list($red, $green, $blue) = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
    } 
	elseif (strlen($color) == 3) 
	{ // если hex цвет в сокращенной форме - 3 символа
        list($red, $green, $blue) = array(
            $color[0]. $color[0],
            $color[1]. $color[1],
            $color[2]. $color[2]
        );
    }
	else
	{
        return false; 
    } 	 
    // переводим шестнадцатиричные числа в десятичные
    $red = hexdec($red); 
    $green = hexdec($green);
    $blue = hexdec($blue);
	
    return array(
        'red' => $red, 
        'green' => $green, 
        'blue' => $blue
    );
}


// перевод цвета из RGB в HEX
function rgbToHex( $color ) 
{      
	return "#" . dechex($color['red']) . dechex($color['green']) . dechex($color['blue']);
}
?>