<?php
class USAM_Theme_Interface_Filters
{					
	protected $search_box = true;
	protected $filters_saved = false;		
	protected $groupby_date = '';
	protected $period = '';
	protected $time_calculation_interval_start;
	protected $time_calculation_interval_end;	
	protected $start_date_interval = '';
	protected $end_date_interval = '';
	protected static $filters = [];
	protected static $selected_filters = [];	
	protected static $js_filters = [];
	protected $filter_data = [];
	protected $window = 'default';
	protected static $filters_settings = [];
	
	function __construct( $filter = [] )
	{
		self::$selected_filters = $filter;		
		self::$filters = $this->get_filters();				
		$this->filters_settings();
	}
	
	protected function get_filters( ) {}	
		
	protected function get_properties( $type ) 
	{
		$filters = [];
		$properties = usam_get_cache_properties( $type );
		foreach ( $properties as $property )
		{			
			if ( $property->field_type == 'file' || $property->field_type == 'files' || $property->field_type == 'none' || $property->field_type == 'button' || $property->field_type == 'files' )
				continue;
				
			$filter = ['title' => $property->name, 'type' => 'string_meta'];
			switch ( $property->field_type )
			{							
				case "select":
				case "checkbox":
				case "radio":				
					$filter['type'] = 'select';
					$filter['options'] = usam_get_property_metadata($property->id, 'options');
				break;
				case "rating":
					$filter['type'] = 'select';
					$filter['options'] = [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5];
				break;
				case "shops":				
					$filter['type'] = 'autocomplete'; 
					$filter['request'] = 'storages';
				break;
				case "one_checkbox":
				case "personal_data":
					$filter['type'] = 'select';
					$filter['options'] = [0 => __('Не выбрано','usam'), 1 => __('Выбрано','usam')];
				break;
				case "location":
					$filter['type'] = 'autocomplete'; 
					$filter['request'] = 'locations';
				break;
			}
			$filters['property_'.$property->id] = $filter;
		}
		return $filters;
	}
	
	protected function get_placeholder_filter_save( ) 
	{	
		return __('Название', 'usam');
	}
	
	protected function get_filter_save_title( ) 
	{	
		return __('Мои фильтры', 'usam');
	}
	
	public function display() 
	{
		?>
		<interface-filters @change="requestData" @calculation="filtersData=$event" :ifilters='filters' :s='search' :period='period' :range="daterange" :page_sorting='page_sorting' :sort_options='sort_options' inline-template>
		<div class='interface_filters'>
		<div class="interface_filters__search_filters">
				<div class="interface_filters__search_sort_container">
					<div class="selected_filters">
						<div class="selected_filters__filters" v-if="selectedFilters.length">
							<div class="selected_filters__filter" v-for="(property, k) in selectedFilters" v-if="k < 3">
								<div class="selected_filter__name">{{property.title}}:</div>
								<div class="selected_filter__option" v-if="property.type=='checklists'">									
									<div class="selected_filter__checked_name" v-for="(list, i) in getCheckedOptions(property)" v-html="list.name" v-if="i<1"></div>
									<span class="selected_filter__checked" v-if="property.value.length>1 || request">{{property.value.length}}</span>
								</div>
								<div class="selected_filter__option" v-if="property.type=='autocomplete' || property.type=='counterparty'">									
									<div class="selected_filter__checked_name" v-for="(list, i) in property.options" v-html="list.name" v-if="i<1"></div>
									<span class="selected_filter__checked" v-if="property.options.length>1 || request">{{property.options.length}}</span>
								</div>
								<div class="selected_filter__option" v-if="property.type=='objects'">	
									<span class="selected_filter__checked">{{property.options.length}}</span>
								</div>
								<div class="selected_filter__option" v-else-if="property.type=='select' && property.value">
									<span class="selected_filter__checked_name" v-for="list in property.options" v-if="list.id==property.value" v-html="list.name"></span>									
								</div>
								<div class="selected_filter__option" v-else-if="(property.type=='numeric' || property.type=='date') && (property.from || property.to)">
									<span class="selected_filter__checked_data" v-if="property.from">{{property.from}}</span> - <span class="selected_filter__checked_data" v-if="property.to">{{property.to}}</span>
								</div>
								<div class="selected_filter__option" v-else-if="property.type=='string' && property.checked">									
									<span class="selected_filter__checked_name">{{property.value}}</span>
								</div>	
								<div class="selected_filter__option" v-else-if="property.type=='string_meta' && property.checked">									
									<span class="selected_filter__checked" v-if="property.options!== undefined">{{property.value.length}}</span>
									<span class="selected_filter__checked_name" v-else>{{property.value}}</span>
								</div>									
								<div class="selected_filter__option" v-else-if="property.type=='period' && property.period">
								
								</div>
								<a class='button_delete' @click="cancel_selected_filter(k, property.code)"></a>
							</div>
							<div class="selected_filters__filter" v-if="selectedFilters.length>3"><?php _e('и еще', 'usam') ?> {{selectedFilters.length-3}}</div>						
						</div>
					</div>
					<?php
					if ( $this->search_box )
					{
						?><button class="dashicons dashicons-search interface_filters__search_button" @click="search_button"></button><?php
					}
					?>
					<div class="interface_filters__search js-open-filters" @click="open_filters">	
						<?php
						if ( $this->search_box )
						{
							$placeholder = !empty(self::$filters)?__('Поиск + Фильтр', 'usam'):__('Поиск', 'usam');
							?><div class="interface_filters__search_editor" ref="search" @input="search_item" @paste="search_paste" @keydown="search_enter" contenteditable="true" placeholder="<?php echo $placeholder; ?>">{{search}}</div><?php
						}
						if ( !empty(self::$filters) )
						{					
							if ( !$this->search_box )
							{
								?><div class="interface_filters__title"><?php _e('Фильтр', 'usam') ?></div><?php
							}
							?><span class="interface_filters__icon dashicons dashicons-sort" title="<?php _e('Нажми, чтобы открыть фильтр', 'usam') ?>"></span><?php
						}
						?>
					</div>
					<div class="interface_filters__sort" v-if="Object.keys(sort_options).length != 0">			
						<select @change="filterPageData" v-model="sorting">
							<option v-for="(title, code) in sort_options" :value="code" v-html="title"></option>
						</select>
					</div>	
				</div>
				<div class="interface_filters__container js-page-filters" :class="{'show_filters':show_filters}" v-show="filters.length">			
					<div class = "filters_columns">
						<div class = "filters">
							<div v-for="(property, k) in filters" class ="filters__row">
								<div class ="filters__name">{{property.title}}:</div>
								<div class ="filters__option">
									<?php include( usam_get_filepath_admin('templates/template-parts/filter-fields.php') ); ?>	
								</div>
							</div>												
						</div>						
					</div>
					<div class = "filters_buttons">					
						<div class = "filters_buttons__action">							
							<button class="button" @click="startFilter"><?php _e( 'Найти', 'usam') ?></button>
							<button class="button filters_buttons__cancel" @click="cancelFilter"><?php _e( 'Отменить', 'usam') ?></button>
						</div>						
					</div>	
				</div>			
			</div>	
		</div>
		</interface-filters>		
		<?php  			
		add_action('wp_footer', array($this, 'display_footer'), 100);
	}		
			
	public function default_dropdown( $filter ) {	}
			
	public function get_sort() 
	{ 
		return [];
	}		
		
	public function get_active_options() 
	{	
		return [['id' => '1', 'name' => __('Активен', 'usam')], ['id' => '0', 'name' => __('Отключен', 'usam')]];
	}
		
	protected function get_filter_value( $key, $value = null ) 
	{		
		if ( isset(self::$selected_filters[$key]) )
			$value = self::$selected_filters[$key];
		elseif ( isset($_REQUEST[$key]) )
		{ 
			if ( is_array($_REQUEST[$key]) )
				self::$selected_filters[$key] = stripslashes_deep($_REQUEST[$key]);
			elseif ( stripos($_REQUEST[$key], ',') !== false )
				self::$selected_filters[$key] = explode(',',stripslashes($_REQUEST[$key]));
			else
				self::$selected_filters[$key] = stripslashes($_REQUEST[$key]);
			$value = self::$selected_filters[$key];			
		}			
		if ( is_array($value) && !empty($value) || $value || $value === '0' )
			self::$js_filters[$key] = array( 'select' => $value );
		return $value;
	}
	
	public function filters_settings(  ) 
	{	
		
		$groupby_date = isset($filter['groupby_date'])?$filter['groupby_date']:'';
		$groupby_date = $this->get_filter_value( 'groupby_date', $groupby_date );
		$date = $this->get_filter_value( 'date_from' );
		if( $date )
			$this->start_date_interval = date("Y-m-d H:i:s", strtotime($date));		
		$date = $this->get_filter_value( 'date_to' );
		if( $date )
			$this->end_date_interval = date("Y-m-d H:i:s", strtotime($date));	
		if ( !$this->end_date_interval && !$this->start_date_interval )
			$this->period = $this->get_filter_value( 'period', $this->period ); 		
		$sort_options = $this->get_sort();
		if ( !empty($sort_options) )
		{		
			$orderby = key($sort_options);
			if ( !empty($_REQUEST['page_sorting']) )
				$orderby = $_REQUEST['page_sorting'];
			elseif ( isset($_REQUEST['orderby']) )
				$orderby = $_REQUEST['orderby'].'-'.(isset($_REQUEST['order'])?$_REQUEST['order']:'desc');
		}
		else
		{
			$orderby = '';
			$sort_options = new stdClass();
		}		
		$filters = [];
		foreach( self::$filters as $key => &$filter )
		{
			$filter['code'] = $key;
			$filter['show'] = true;
			if ( $filter['type']=='checklists' || $filter['type']=='objects' || $filter['type']=='autocomplete' || $filter['type']=='select' || $filter['type']=='counterparty' )
				$filter['options'] = [];			
			$method = "get_{$key}_options";	
			if ( method_exists($this, $method) )
				$filter['options'] = $this->$method();				
			elseif ( $filter['type'] == 'string_meta' || $filter['type'] == 'string' )
			{				
				if( !isset($filter['value']) )
				{
					$filter['value'] = '';
					$value = $this->get_filter_value( 'v_'.$key );
					if ( $value )
						$filter['value'] = $value;				
				}
				$filter['checked'] = '';				
				$value = $this->get_filter_value( 'c_'.$key );
				if ( $value )
					$filter['checked'] = $value;
			}
			elseif ( $filter['type'] == 'numeric' || $filter['type'] == 'date' )
			{
				$filter['from'] = '';
				$filter['to'] = '';
				foreach( ['from', 'to'] as $v )
				{
					$value = $this->get_filter_value( $v );
					if ( $value )
						$filter[$v] = $value;				
				}
			}			
			elseif ( $filter['type'] == 'period' )
			{				
				$filter['period'] = $this->get_filter_value( $key, $this->period );				
				$filter['interval'] = '';
				$value = $this->get_filter_value( 'year' );
				if ( $value )
					$filter['year'] = $value;
				$value = $this->get_filter_value( 'month' );
				if ( $value )
					$filter['interval'] = $value;
				$value = $this->get_filter_value( 'quarter' );
				if ( $value )
					$filter['interval'] = $value;
			}			
			elseif ( $filter['type'] == 'counterparty' )
			{				
				$filter['request'] = 'companies';
				$filter['search'] = '';
				$filter['value'] = [];			
				$selected = $this->get_filter_value('companies');
				if ( $selected )
					$filter['value'] = array_map('intval', (array)$selected);		
				$selected = $this->get_filter_value('contacts');
				if ( $selected )
				{
					$filter['value'] = array_map('intval', (array)$selected);
					$filter['request'] = 'contacts';
				}
			}			
			else
			{ 
				$filter['value'] = $filter['type'] == 'checklists' || $filter['type'] == 'autocomplete'?[]:'';
				$selected = $this->get_filter_value( $key );				
				if ( $selected !== null )
				{
					if( $filter['type'] == 'checklists' || $filter['type'] == 'autocomplete' )
					{					
						foreach( (array)$selected as $id )
							if( is_numeric($id) )
								$filter['value'][] = intval($id);
							else
								$filter['value'][] = $id;
					}
					else
						$filter['value'] = $selected;
				}
			}
			$filters[] = $filter;
		}
		self::$filters_settings[$this->window] = ['groupby_date' => $groupby_date, 'period' => $this->period, 'daterange' => ['start' => $this->start_date_interval, 'end' => $this->end_date_interval], 'page_sorting' => $orderby, 'filters' => $filters, 'sort_options' => $sort_options];
	}
	
	public function display_footer(  ) 
	{				
		?>
		<script>			
			var filtersSettings = <?php echo json_encode( self::$filters_settings ); ?>;
		</script>
		<?php		
	}
}
?>