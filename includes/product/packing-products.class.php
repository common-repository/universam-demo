<?php
new USAM_Packing_Products();
class USAM_Packing_Products
{			
	public function __construct() 
	{				
		$products = array(  array('height' => 78, 'width'=> 220, 'length'=> 90, 'quantity' => 1),
							array('height' => 100, 'width'=> 36, 'length'=> 179, 'quantity' => 1),
							array('height' => 94, 'width'=> 70, 'length'=> 9, 'quantity' => 3),							
							array('height' => 98, 'width'=> 195, 'length'=> 50, 'quantity' => 1),
							array('height' => 98, 'width'=> 195, 'length'=> 50, 'quantity' => 3),
							array('height' => 98, 'width'=> 195, 'length'=> 50, 'quantity' => 1),
							array('height' => 98, 'width'=> 95, 'length'=> 50, 'quantity' => 1),
							array('height' => 98, 'width'=> 95, 'length'=> 50, 'quantity' => 5),
							array('height' => 98, 'width'=> 95, 'length'=> 50, 'quantity' => 2),
						/*	array('height' => 98, 'width'=> 95, 'length'=> 50, 'quantity' => 1),
							array('height' => 98, 'width'=> 95, 'length'=> 50, 'quantity' => 1),
							array('height' => 50, 'width'=> 50, 'length'=> 50, 'quantity' => 9),*/
							
		);
		$boxs = array( array('height' => 278,'width'=> 407,'length'=> 400),
					array('height' => 87,'width'=> 87,'length'=> 87)
		);
		$this->preparation($products, $boxs);
	}	
	
	function compare_products ($v1, $v2) 
	{		
		if ($v1["volume"] == $v2["volume"]) return 0;
		return ($v1["volume"] > $v2["volume"])? -1: 1;
	}
	
	function compare_empty_area ($v1, $v2) 
	{				
		if ($v1["volume"] == $v2["volume"]) return 0;
		return ($v1["volume"] < $v2["volume"])? -1: 1;
	}

	// Подготовка
	public function preparation( $products, $boxs ) 
	{	   
		foreach ( $products as $key => $product )
	    { 
		   $products[$key]['volume'] = $product['height']*$product['width']*$product['length'];
		   $products[$key]['ID'] = $key;
	    }			
		usort($products, array($this, 'compare_products'));	
		$items = array();
		foreach ( $products as $key => $product )
	    { 					
			for ($i = 1; $i <= $product['quantity']; $i++) 
			{			
				$items[] = $product;	
			}		
		//	unset($product['quantity']);
		//	unset($product['volume']);			
	    }		
		$result = false;		
		foreach ( $boxs as $box )
		{
		   print_r($box);
		   
		   echo "<br>---------------------------------------<br>";
		   $package_product = $this->package_selection( $items, $box );
		   if ( count($package_product) == count($items)  )
		   {
				$this->draw_packaged_goods( $package_product, $items, $box, $box );
				$result = true;
			   break;
		   }
		}	   
			echo "<br>package_product =      ";
		 print_r($package_product);
		 	echo "<br><br>products =      ";
		print_r($products);
		exit;
	}	

	private function randhtmlcolor()
	{
		$color = "";
		$sym = "0123456789ABCDEF";
		for($i = 0; $i < 6; $i++)
		{
			$color .= $sym[rand(0,15)];
		}
		return "#$color";
	}
	
	public function draw_packaged_goods( $package_product, $products, $box, $area ) 
	{
		$area = array('width' => 100,'length'=> 100);
		$width = $box['width']*100/$area['width'];
		$length = $box['length']*100/$area['length'];			
		?>
		<div style = "width: <?php echo $width; ?>px; height: <?php echo $length; ?>px; position: relative; background-color: #ffffff; border: 1px solid #000000;">
			<?php	
			foreach ( $package_product as $i => $product )
			{
				$width = round( $product['width'] *100/$area['width'], 2);
				$length = round( $product['length']*100/$area['length'],2);
				$top = round( $product['y'] *100/$area['width'],2);
				$left = round( $product['x']*100/$area['length'],2);			
				?>
				<div style = "width: <?php echo $width; ?>px; height: <?php echo $length; ?>px;  top: <?php echo $top; ?>px; left: <?php echo $left; ?>px; position: absolute; background-color: <?php echo $this->randhtmlcolor(); ?>; border: 1px solid #EEEEEE; text-align: center;">
				<?php echo "$i - (".$products[$i]['ID'].")"; ?>
				</div>
				<?php			
			}
			?>
		</div>
		<?php			
	}
	
	public function draw_empty_area( $empty_area, $box, $area ) 
	{
		$area = array('width' => 100,'length'=> 100);
		$width = $box['width']*100/$area['width'];
		$length = $box['length']*100/$area['length'];			
		?>
		<div style = "width: <?php echo $width; ?>px; height: <?php echo $length; ?>px; position: relative; background-color: #ffffff; border: 1px solid #000000;">
			<?php	
			foreach ( $empty_area as $i => $product )
			{
				$width = round( $product['width'] *100/$area['width'], 2);
				$length = round( $product['length']*100/$area['length'],2);
				$top = round( $product['y'] *100/$area['width'],2);
				$left = round( $product['x']*100/$area['length'],2);			
				?>
				<div style = "width: <?php echo $width; ?>px; height: <?php echo $length; ?>px;  top: <?php echo $top; ?>px; left: <?php echo $left; ?>px; position: absolute; background-color: <?php echo $this->randhtmlcolor(); ?>; border: 1px solid #EEEEEE; text-align: center;">
				<?php echo $i; ?>
				</div>
				<?php			
			}
			?>
		</div>
		<?php		
	}
	
	
	
	// Подбор пустых областей
	private function get_empty_area( $graph, $box ) 
	{
		echo "<br>graph =      ";
		print_r($graph);
		$empty_area = array(); // массив пустых областей
		$count = count($graph);
		echo "<br>";
		for ($i = 0; $i < $count; $i+=2 )	
		{	
			$k = $i;
			$y     = $graph[$i]['y'];
			$y_max = $box['length'];
			$x     = $graph[$i]['x'];
			$x_max = $box['width'];			
					
			for ($j = 1; $j < $count; $j++)
			{	
				if ( $graph[$k]['x'] < $graph[$j]['x'] && $graph[$i]['y'] !== $graph[$i+1]['y']  )
				{					
					if ( $graph[$i]['y'] < $graph[$j]['y'] && $graph[$j]['y'] < $y_max )
					{
						$y_max = $graph[$j]['y'];		
					}					
				}
				else
				{
					if ( $graph[$i]['y'] > $graph[$j]['y'] && $graph[$j]['x'] < $x && $graph[$i]['y'] !== $graph[$i+1]['y'])
					{
						$x = $graph[$j]['x'];		
					}	
				}
				if ( $graph[$i]['y'] < $graph[$j]['y'] )
				{
					if ( $graph[$i]['x'] < $graph[$j]['x'] && $graph[$j]['x'] < $x_max )
					{					
						$x_max = $graph[$j]['x'];					
					}
				}
			}
			$width = $x_max - $x;
			$length = $y_max - $y;
			$empty_area[] = array( 'x' => $x, 'y' => $y, 'width' => $width, 'length' => $length, 'volume' => $length*$width );
		}		
		echo "<br>empty_area =   ";	print_r($empty_area);
		
		usort($empty_area, array($this, 'compare_empty_area'));				
			
		echo "<br>sort empty_area =  "; 	print_r($empty_area);
		echo "<br>========================================= <br>";
		
		return $empty_area;
	}
	
	// Добавить точки на граф, расчитать точки которые стоят рядом с пустой областью
	private function set_graph( $graph, $new_points ) 
	{		
		//$position = count($new_points);
		foreach ( $new_points as $key1 => $point1 )
		{ 
			foreach ( $graph as $key2 => $point2 )
			{ 
				if ( $point1['x'] == $point2['x'] && $point1['y'] == $point2['y'] )
				{
					unset($new_points[$key1]);
					unset($graph[$key2]);
					$position = $key2;
				}
			}
		}	
		array_splice($graph, $position, 0, $new_points);
		$graph = array_values($graph);
		
		echo "<br>graph";
		print_r($graph);
		$k = count($graph);
		foreach ( $new_points as $key1 => $point1 )
		{ 
			foreach ( $graph as $key2 => $point2 )
			{ 
				if ( $point1['x'] > $point2['x'] && $point1['y'] > $point2['y'] )
				{
					for ($i = $key1; $i < $k; $i++ )						
					{ 
					//	if ( $point2['x'] > $graph[$i]['x'] && $point2['y'] < $graph[$i]['y'] || $point2['x'] < $graph[$i]['x'] && $point2['y'] > $graph[$i]['y'] )
					//		unset($graph[$key2]);
					}
				}
			}
		}	
		echo "<br>graph";
		print_r($graph);
		$graph = array_values($graph);		
		
		$k = count($graph) - 2;
		for ($i = 0; $i < $k; $i++ )	
		{				
			echo "<br> $i ".$graph[$i]['x'] .'<= '.$graph[$i+1]['x'] .'&&'. $graph[$i+1]['x'] .'<= '.$graph[$i+2]['x'] .'&& '.$graph[$i]['y'].' ==='. $graph[$i+1]['y'].' &&'. $graph[$i]['y'].' ==='. $graph[$i+2]['y'];
			if ( $graph[$i]['x'] <= $graph[$i+1]['x'] && $graph[$i+1]['x'] <= $graph[$i+2]['x'] && $graph[$i]['y'] === $graph[$i+1]['y'] && $graph[$i]['y'] === $graph[$i+2]['y'] )
			{
				echo ' $i =	'.$i;
				unset($graph[$i+1]);
				$i++;
			}
		}		
		
		$graph = array_values($graph);
		return $graph;
	}
	
	// Подбор упаковки
	private function package_selection( $products, $box ) 
	{	   
	   $package = array();
	   $packaged_products = array();// массив упакованных товаров
	   $empty_area = array(); // массив пустых областей
	   $graph = array(); // массив точек кривой	 
	   foreach ( $products as $i => $product )
	   {
			echo " <br><br>================== ТОВАР НОМЕР $i                                ================================= ";	print_r( $product);echo "<br>";
			if ( empty($graph) )
			{						
				$packaged_products[$i] = array( 'x' => 0, 'y' => 0, 'width' => $product['width'], 'length' => $product['length'] );			
				$graph[] = array( 'x' => 0,                  'y' => $product['length'] );
				$graph[] = array( 'x' => $product['width'], 'y' => $product['length'] );
				$graph[] = array( 'x' => $product['width'], 'y' => 0 );
				continue;
			}			
			$empty_area = $this->get_empty_area( $graph, $box );	
			$this->draw_packaged_goods( $packaged_products, $products, $box, $box );
			echo "<br>Пустые области<br>";
$this->draw_empty_area( $empty_area, $box, $box );			

			foreach ( $empty_area as $key => $point )
			{ 						
				$ok = false;	
				if ( $product['width'] <= $point['width'] && $product['length'] <= $point['length'] && $product['length'] <= $point['width'] && $product['width'] <= $point['length'] )
				{				 				
					$ok = true;	
					if ( $product['length'] < $product['width'] )
					{
						$width = $product['width'];
						$length = $product['length'];		
					}
					else
					{
						$width = $product['length'];
						$length = $product['width'];
					}
					if ( $i == 	9){
			echo 	$product['width'] ." <= ".$point['width']." && ".$product['length']." <= ".$point['length'] ." -&&-". $product['length'] ." <= ".$point['width']." && ".$product['width']." <= ".$point['length']; 
			echo '<br> '.$key;
		//	 exit;
			}
				}
				elseif ( $product['width'] <= $point['width'] && $product['length'] <= $point['length'] )
				{				 			
					$ok = true;				
					$width = $product['width'];
					$length = $product['length'];				
				}
				elseif ( $product['length'] <= $point['width'] && $product['width'] <= $point['length'] )
				{				
					 if ( $i == 	9){ //exit;

					}
					$ok = true;
					$width = $product['length'];
					$length = $product['width'];					
				}					
				echo 	 "key = $key<br>";	
				
				if ( $ok )
				{			echo 	"empty_area x = ". $point['x'].' y = '.$point['y']."---$width---$length-------------------- ok    --------------------------------<br>";
					$new_points = array();						
					$new_points[] = array( 'x' => $point['x'], 'y' => $point['y'] );	
					$new_points[] = array( 'x' => $point['x'], 'y' => $point['y'] + $length  );	
					$new_points[] = array( 'x' => $point['x'] + $width, 'y' => $point['y'] + $length );
					$new_points[] = array( 'x' => $point['x'] + $width, 'y' => $point['y'] );
					print_r($new_points);										
					
					$graph = $this->set_graph( $graph, $new_points );
					
					$packaged_products[$i] = array( 'x' => $point['x'], 'y' => $point['y'], 'width' => $width, 'length' => $length, 'empty_area' => $key );	
					break;
				}	
			}
			if ( !$ok )			{
				
				//$packaged_products = array();
				break;
			}
	   }	  	 
	   return $packaged_products;
	}
}
?>