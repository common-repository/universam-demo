<?php
/**
 * Производит все комбинации вариаций, выбранных для данного продукта этого класса, базируется на примере кода здесь:
 * http://www.php.net/manual/en/ref.array.php#94910
 */
class USAM_Variation_Combinator 
{
	private $variation_sets = array();
	private $variation_values = array();
	private $reprocessed_array = array();
	private $combinations= array();

	public function __construct( $variation_sets )
	{
		if( !empty($variation_sets) ) 
		{
			foreach($variation_sets as $variation_set_id => $variation_set) 
			{
				$this->variation_sets[] = absint($variation_set_id);
				$new_variation_set = [];
				if( $variation_set ) 
				{
					foreach($variation_set as $variation => $active) 
					{
						if( $active ) 
						{
							$new_variation_set[] = [ absint($variation) ];
							$this->variation_values[] = $variation;
						}
					}
				}
				$this->reprocessed_array[] = $new_variation_set;
			}
			$this->get_combinations(array(), $this->reprocessed_array, 0);
		}
	}
	
	function get_combinations($batch, $elements, $i)
	{
        if ($i >= count($elements))
            $this->combinations[] = $batch;
        else 
		{
            foreach ($elements[$i] as $element)
                $this->get_combinations(array_merge($batch, $element), $elements, $i + 1);
        }
	}

	function return_variation_sets() 
	{
		return $this->variation_sets;
	}

	function return_variation_values() 
	{
		return $this->variation_values;
	}

	function return_combinations() 
	{
		return $this->combinations;
	}
}