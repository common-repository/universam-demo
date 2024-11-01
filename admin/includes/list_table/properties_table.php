<?php
require_once( USAM_FILE_PATH .'/admin/includes/usam_list_table.class.php' );
class USAM_Properties_Table extends USAM_List_Table
{	
	protected $order = 'ASC';
	protected $orderby = ['group', 'sort'];
	protected $property_type = '';
	protected $status = '';
	private $group = [];
		
	function __construct( $args = [] )
	{			
		parent::__construct( $args );
		$this->group = usam_get_property_groups(['type' => $this->property_type, 'fields' => 'code=>name']);
		USAM_Admin_Assets::sort_fields( 'property' );	
    }
		
	function get_bulk_actions_display() 
	{	
		$actions = [
			'activate'  => __('Активировать', 'usam'),
			'deactivate' => __('Отключить', 'usam'),				
			'delete'    => __('Удалить', 'usam')
		];
		return $actions;
	}	
			
	public function single_row( $item ) 
	{		
		echo '<tr data-field-id="'.$item->id.'" id="property_'.$item->id.'" class="'.($item->active?'included':'turned_off').'">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}	
	
	function column_name( $item )
	{		
		echo $this->item_edit($item->id, $item->name, $this->property_type.'_property');
		?><div class="code_item js-copy-clipboard"><?php echo $item->code; ?></div><?php
	}
	
	function column_field_type( $item )
	{				
		$field_types = usam_get_field_types();
		echo isset($field_types[$item->field_type])?$field_types[$item->field_type]:'';
	}
	
	function column_group( $item )
	{		
		echo isset($this->group[$item->group])?$this->group[$item->group]:'';
	}
	
	function column_display_profile( $item )
	{		
		$profile = usam_get_property_metadata($item->id, 'profile');
		$this->logical_column( $profile );
	}
	
	function column_show_staff( $item )
	{		
		$this->logical_column( $item->show_staff );
	}
	
	function column_mandatory( $item )
	{		
		$this->logical_column( $item->mandatory );
	}	
		 
	function column_code( $item )
	{		
		?><span class="js-copy-clipboard"><?php echo $item->code; ?></span><?php
	}	

	function column_roles( $item )
	{
		$roles = usam_get_array_metadata($item->id, 'property', 'role');	
		$this->roles_name( $roles );
	}	
		 
	function get_sortable_columns()
	{
		$sortable = array(
			'name'        => array('name', false),
			'code'        => array('code', false),		
			'active'      => array('active', false),		
			'group'       => array('group', false),	
			'show_staff' => array('show_staff', false),
			'mandatory' => array('mandatory', false),			
			'field_type' => array('field_type', false),
		);
		return $sortable;
	}
	
	function get_columns()
	{		
        $columns = [          
			'cb'        => '<input type="checkbox" />',			
			'name'      => __('Название', 'usam'),						
			'mandatory' => __('Обязательно для клиента', 'usam'),	
			'field_type'=> __('Тип поля', 'usam'),			
			'group'     => __('Группа', 'usam'),	
			'roles'     => __('Видимость поля', 'usam'),					
			'drag'      => '&nbsp;',
        ];		
        return $columns;
    }
		
	function prepare_items() 
	{			
		$this->get_query_vars();			
		$this->query_vars['type'] = $this->property_type;
		$this->query_vars['active'] = 'all';
		if ( empty($this->query_vars['include']) )
		{							
			$selected = $this->get_filter_value( 'property_groups' );
			if ( $selected )
				$this->query_vars['group'] = array_map('sanitize_title', (array)$selected);
		}	
		$query = new USAM_Properties_Query( $this->query_vars );		
		$this->items = $query->get_results();		
		$total_items = $query->get_total();
		$this->set_pagination_args(['total_items' => $total_items, 'per_page' => $this->per_page]);
	}
}
?>