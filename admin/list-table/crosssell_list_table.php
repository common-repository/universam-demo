<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_List_Table_crosssell extends USAM_List_Table
{	
    private $type_location;
	function __construct( $args = array() )
	{	
       parent::__construct( $args );			
    }	
	
	function get_bulk_actions_display() 
	{
		$actions = array(
			'activate'  => __('Активировать', 'usam'),
			'deactivate' => __('Отключить', 'usam'),
			'delete'    => __('Удалить', 'usam'),
		);
		return $actions;
	}
	
	function column_name( $item )
	{	
		$title = implode(',',$item['words']);
		$this->row_actions_table( $title, $this->standart_row_actions( $item['id'], 'crosssell' ) );		
	}		
	
	function column_conditions( $item )
	{	
		$count = count($item['conditions'])-1;
		foreach ( $item['conditions'] as $n => $condition )
		{					
			if ( $condition['logic_operator'] == 'AND' )
			{				
				$class_logic = 'condition_logic_and';
				$title_logic = __('И','usam');			
			}
			else
			{			
				$title_logic = __('ИЛИ','usam');	
				$class_logic = 'condition_logic_or';		
			}
			$text = '';
			switch ( $condition['type'] ) 
			{
				case 'name' :
					$type_text = __('Название товара','usam');				
					$value_text = $condition['value'];				
				break;
				case 'attr' :
					$term = get_term_by( 'slug', $condition['value'], 'usam-product_attributes' );	
					$type_text = __('Свойство товара','usam');	
					if ( isset($term->name) )
						$value_text = $term->name;		
				break;
				case 'category' :
					$term = get_term( $condition['value'], 'usam-category' );	
					$type_text = __('Категория товара','usam');					
					if ( isset($term->name) )
						$value_text = $term->name;				
				break;
			}				
			if ( isset($value_text) )
				echo '<div class="condition-row"><span class="condition-type_title">'.$type_text.'</span><span class="condition-logic_title">'.usam_get_logic_title( $condition['logic'] ).'</span><span class="condition-value_title">'.$value_text.'</span>';		
			if ( $n != $count )
				echo "<div class = 'condition-logic $class_logic' style='margin:0 auto;'><span>$title_logic</span></div>";			
			echo "</div>";								
		}
	}	
		   
	function get_sortable_columns()
	{
		$sortable = array(
			'name'      => array('name', false),			
			'type'       => array('type', false),			
			'sort'       => array('sort', false),			
			);
		return $sortable;
	}
		
	function get_columns()
	{
        $columns = array(           
			'cb'               => '<input type="checkbox" />',				
			'name'             => __('Слова', 'usam'),
			'active'           => __('Активность', 'usam'),		
			'conditions'       => __('Правило', 'usam'),	
			'date_insert'      => __('Дата создания', 'usam'),							
        );		
        return $columns;
    }	
	
	public function get_number_columns_sql()
    {       
		return array('sort' );
    }
	
	function prepare_items() 
	{	
		$option = get_site_option('usam_crosssell_conditions' );						
		$crosssell_conditions = maybe_unserialize($option);				
		if ( empty($crosssell_conditions) )
			$this->items = array();	
		else
			foreach( $crosssell_conditions as $key => $item )
			{	
				if ( empty($this->records) || in_array($item['id'], $this->records) )
				{					
					$this->items[] = $item;
				}
			}		
		$this->total_items = count($this->items);	
		$this->forming_tables();
	}
}
?>