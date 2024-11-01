<?php
// Класс обновления */
class USAM_Save_Object_Settings
{	
	private function create( $item, $tag = 'data', $n = 0 ) 
	{					
		$out = "";
		$n++;
		foreach( $item as $key => $value )	
		{
			if ( is_numeric($key) )
				$key = $tag.'_'.$key;
			if ( is_array($value) )
			{
				$r = $this->create( $value, $key, $n );
				if ( !is_numeric($key) )
					$r = "<$key>$r</$key>";
				$out .= $r;
			}
			elseif ( is_object($value) )				
				$out .= "<$tag>".$this->create( $value, $key, $n )."</$tag>";
			else
				$out .= str_repeat(' ', $n )."<$key>".htmlspecialchars($value)."</$key>\n";
		}
		return $out;
	}
	
	public function get_xml_file( $object ) 
	{
		if ( isset($object['id']) )
			unset($object['id']);
		$xml = $this->create( $object );		
		$xml = "<data>$xml</data>\n";
		$dom_xml= new DOMDocument();
		$dom_xml->loadXML($xml);
		return $dom_xml->saveXML();		
	}
	
	public function save_xml_file( $object, $file ) 
	{
		if ( isset($object['id']) )
			unset($object['id']);
		$xml = $this->create( $object );		
		$xml = "<data>$xml</data>\n";			
		$dom_xml= new DOMDocument();
		$dom_xml->loadXML($xml);
		$dom_xml->save( $file );		
	}
}


class USAM_Read_Object_Settings
{		
	public function get_settings( $path ) 
	{
		if( file_exists($path) )
		{
			$xml = file_get_contents($path, true);			
			$s = new SimpleXMLElement( $xml );
			return $this->xml2array( $s );
		}
		return [];
	}
		
	private function xml2array( $xmlObject, $key = null, $out = array () )
	{			
		foreach( (array) $xmlObject as $i => $node )
		{			
			if( is_object($node) )
				$node = empty($node) ? '' : $this->xml2array( $node, $i );
			elseif( is_array($node) )
				$node = $this->xml2array( $node, $i );	
			elseif( $node === 'true' )				
				$node = true;
			elseif( $node === 'false' )				
				$node = false;
			elseif( is_float($node) )				
				$node = (float)$node;
			elseif( is_numeric($node) )				
				$node = (int)$node;
			if ( $key && str_contains($i, $key) )
				$out[] = $node;
			else
				$out[$i] = $node;	
		}		
		return $out;
	}
}
