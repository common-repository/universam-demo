<?php
class USAM_Interface_Filters
{					
	protected $search_box = true;
	protected $filters_saved = true;		
	protected $groupby_date = '';
	protected $period = '';
	protected $time_calculation_interval_start;
	protected $time_calculation_interval_end;	
	protected $start_date_interval = '';
	protected $end_date_interval = '';
	protected static $filters = [];
	protected static $selected_filters = [];
	protected $filter_data = [];
	protected $window = 'default';
	protected static $filters_settings = [];
	
	function __construct( $filter = [] )
	{
		self::$selected_filters = $filter;		
		self::$filters = $this->get_user_filters();		
		$this->filters_settings();		
    }
	
	protected function get_filters( ) {}	
	
	protected function get_user_filters( ) 
	{				
		$filters = $this->get_filters(); 
		if ( empty($filters) )
			return [];

		foreach( $filters as $k => $filter )
		{
			if ( !isset($filter['show']) )
				$filters[$k]['show'] = true;
			$filters[$k]['show_default'] = $filters[$k]['show'];
		}		
		$screen = get_current_screen();	
		$sort = get_user_option( 'usam_sort_interface_filters' );	
		if ( !empty($sort[$screen->id]) )
		{
			foreach( $filters as $k => $filter )		
			{
				$filters[$k]['show'] = in_array($k, $sort[$screen->id]);
			}
			$sort = $sort[$screen->id];					
			uksort($filters, function($a, $b) use ( $sort ) { return array_search($a, $sort) - array_search($b, $sort); });
		}
		return $filters;
	}
	
	protected function get_properties( $type ) 
	{
		$filters = [];
		$properties = usam_get_cache_properties( $type );
		foreach ( $properties as $property )
		{			
			$filter = ['title' => $property->name, 'type' => 'string_meta', 'field_type' => $property->field_type, 'show' => false];
			switch ( $property->field_type )
			{							
				case "select":
				case "checkbox":
				case "radio":				
					$filter['options'] = [];
					$options = usam_get_property_metadata($property->id, 'options');
					if ( $options )
						foreach( $options as $option )
							$filter['options'][] = ['id' => $option['code'], 'name' => $option['name']];
				break;
				case "rating":
					$filter['field_type'] = 'checkbox';	
					$filter['options'] = [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5];
				break;				
				case "one_checkbox":
				case "personal_data":
					$filter['field_type'] = 'checkbox';	
					$filter['options'] = [0 => __('Не выбрано','usam'), 1 => __('Выбрано','usam')];
				break;
				case "shops":				 
					$filter['request'] = 'storages';
					$filter['request_parameters'] = new stdClass();;	
					$filter['field_type'] = 'AUTOCOMPLETE';	
				break;
				case "company":				 
					$filter['request'] = 'companies';
					$filter['request_parameters'] = new stdClass();;	
					$filter['field_type'] = 'AUTOCOMPLETE';	
				break;
				case "location":
					$filter['request'] = 'locations';
					$filter['request_parameters'] = new stdClass();;	
					$filter['field_type'] = 'AUTOCOMPLETE';	
				break;
			}
			$value = $this->get_filter_value( 'v_property_'.$property->id );
			if ( isset($filter['options']) )
				$filter['value'] = array_map('sanitize_text_field', (array)$value);
			else
				$filter['value'] = sanitize_text_field($value);	
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
	
	public function display( $interval_toolbar = false ) 
	{ //:title.sync="pageTitle"	
		?>
		<interface-filters @change="requestData" @calculation="filtersData=$event" :ifilters='filters' :groupby_date='groupby_date' :s='search' :filter_id='filter_id' :period='period' :range="daterange" :page_sorting='page_sorting' :sort_options='sort_options' :screen_id='screen_id' inline-template>		
		<div class='interface_filters'>
			<?php if ( $interval_toolbar ) { ?>
				<div class="date_interval_toolbar">
					<div class="period-selector date-selector__period">
						<span class="buttons_radio button_radio_theme">
							<span class="button_radio" v-if="groupby_date==''" :class="[dateperiod=='today'?'button_radio_checked_yes':'']" @click="selectFilterDate('today',$event)"><?php _e('Сегодня', 'usam') ?></span>
							<span class="button_radio" v-if="groupby_date==''" :class="[dateperiod=='yesterday'?'button_radio_checked_yes':'']" @click="selectFilterDate('yesterday',$event)"><?php _e('Вчера', 'usam') ?></span>
							<span class="button_radio" v-if="groupby_date=='day' || groupby_date==''" :class="[dateperiod=='last_7_day'?'button_radio_checked_yes':'']" @click="selectFilterDate('last_7_day',$event)"><?php _e('Неделя', 'usam') ?></span>
							<span class="button_radio" v-if="groupby_date!='year'" :class="[dateperiod=='last_30_day'?'button_radio_checked_yes':'']" @click="selectFilterDate('last_30_day',$event)"><?php _e('Месяц', 'usam') ?></span>
							<span class="button_radio" :class="[dateperiod=='last_365_day'?'button_radio_checked_yes':'']" @click="selectFilterDate('last_365_day',$event)"><?php _e('Год', 'usam') ?></span>
							<span class="button_radio" v-if="groupby_date!='day' && groupby_date!=''" :class="[dateperiod=='last_1825_day'?'button_radio_checked_yes':'']" @click="selectFilterDate('last_1825_day',$event)"><?php _e('5 лет', 'usam') ?></span>
							<span class="button_radio" v-if="groupby_date=='year' && groupby_date!=''" :class="[dateperiod=='last_3650_day'?'button_radio_checked_yes':'']" @click="selectFilterDate('last_3650_day',$event)"><?php _e('10 лет', 'usam') ?></span>
						</span>
					</div>		
					<div class="date-selector__period date-range-selector" :class="{'date_range_active':daterange.start || daterange.end}">						
						<v-date-picker is-range :columns="$screens({ default: 1, lg: 2 })" :step="1" :value="{start:daterange.start, end:daterange.end}" @input="changeDateRange" is24hr :model-config="{type:'string',mask:'YYYY-MM-DD HH:mm:ss'}">
							<template v-slot="{ inputValue, inputEvents }">
								<input :value="inputValue.start" v-on="inputEvents.start" @focus="changeDate=true" @blur="changeDate=false" placeholder="<?php _e('дд.мм.гггг','usam'); ?>"/>
								<span class="date-range-selector__separator">-</span>
								<input :value="inputValue.end" v-on="inputEvents.end" @focus="changeDate=true" @blur="changeDate=false" placeholder="<?php _e('дд.мм.гггг','usam'); ?>"/>
							</template>
						</v-date-picker>
					</div>
					<div class="date-selector__period" v-if="groupby_date">
						<select class="select__control" @change="filterPageData" v-model="groupby_date" tabindex="-1" aria-hidden="true" autocomplete="off">
							<option value="day"><?php _e('по дням', 'usam') ?></option>
							<option value="week"><?php _e('по неделям', 'usam') ?></option>
							<option value="month"><?php _e('по месяцам', 'usam') ?></option>
							<option value="year"><?php _e('по годам', 'usam') ?></option>
						</select>				
					</div>
				</div>
			<?php } ?>
			<div class="interface_filters__search_filters">
				<div class="interface_filters__search_sort_container">
					<div class="selected_filters">
						<div class="selected_filters__filter my_selected_filter" v-if="Object.keys(selected_filter).length">{{selected_filter.name}}<a class='button_delete' @click="cancelFilter"></a></div>
						<div class="selected_filters__filters" v-else-if="selectedFilters.length">
							<div class="selected_filters__filter" v-for="(property, k) in selectedFilters" v-if="k < 3">
								<div class="selected_filter__name">{{property.title}}:</div>
								<div class="selected_filter__option" v-if="property.type=='checklists'">									
									<div class="selected_filter__checked_name" v-for="(list, i) in getCheckedOptions(property)" v-html="list.name" v-if="i<1"></div>
									<span class="selected_filter__checked" v-if="property.value.length>1 || request">{{property.value.length}}</span>
								</div>
								<div class="selected_filter__option" v-if="property.type=='autocomplete' || property.type=='counterparty'">									
									<div class="selected_filter__checked_name" v-for="(list, i) in property.options" v-html="list.name" v-if="i<1"></div>
									<span class="selected_filter__checked" v-if="property.value.length>1 || request">{{property.value.length}}</span>
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
						<div class = "filters js-filters">
							<div v-for="(property, k) in filters" class ="filters__row" v-show="property.show">
								<div class ="filters__name js-drag">{{property.title}}:</div>
								<div class ="filters__option">
									<?php include( usam_get_filepath_admin('templates/template-parts/filter-fields.php') ); ?>		
									<span class="filters__option_close" @click="toggleFilter(k)">×</span>
								</div>
							</div>
							<div class="filters_add">
								<div class="filters_add__buttons">
									<span class="filters_add__button filters_add__button_add" @click="addList=!addList"><?php _e( 'Добавить поле', 'usam'); ?></span>
									<span class="filters_add__button filters_add__button_restore" @click="filtersRestore"><?php _e( 'Вернуть поля по умолчанию', 'usam'); ?></span>
								</div>
							</div>
							<div class="filters_add_list" v-show="addList">
								<div class="filters_add_list__title"><?php _e( 'Настройка полей фильтра', 'usam'); ?><span class="filters_add_list__close" @click="addList=!addList">×</span></div>
								<div class="search_field" :class="{'introduced':searchHiddenFilters}">	
									<input type="text" class="search_field__text" :class="[searchHiddenFilters?'active':'']" placeholder="<?php _e("Поиск","usam"); ?>" v-model="searchHiddenFilters">		
									<a class='button_delete search_field__delete' v-show="searchHiddenFilters!==''" @click="searchHiddenFilters==''"></a>
								</div>				
								<div class="filters_add_list__rows">
									<div v-for="(property, k) in filters" class ="filters__row" v-if="!searchHiddenFilters || property.title.toLowerCase().includes(searchHiddenFilters.toLowerCase())">
										<label><input type="checkbox" v-model="property.show" :value="k">{{property.title}}</label>
									</div>
								</div>
							</div>	
						</div>
						<?php  if ( $this->filters_saved ) { ?>	
						<div class="filters_saved" v-show="filters.length>2">	
							<div class ="filters_saved__title"><?php echo $this->get_filter_save_title(); ?></div>
							<div v-for="(filter, k) in save_filters" class="filters_saved__filter_name" :class="{'filter_active':selected_filter.k==k}">
								<span @click="open_filter(k,$event)">{{filter.name}}</span><a class='button_delete' @click="delete_filter(k)"></a>
							</div>					
						</div>	
						<?php  } ?>	
					</div>
					<div class = "filters_buttons">					
						<div class = "filters_buttons__action">							
							<button class="button" @click="startFilter"><?php _e( 'Найти', 'usam') ?></button>
							<button class="button filters_buttons__cancel" @click="cancelFilter"><?php _e( 'Отменить', 'usam') ?></button>
						</div>
						<?php  if ( $this->filters_saved ) { ?>	
						<div class = "filters_buttons__save" v-show="filters.length>2">
							<input type="text" v-model="filter_name" value="" placeholder="<?php echo $this->get_placeholder_filter_save(); ?>" autocomplete="off"/>	
							<button @click="add_filter" class="button"><?php _e( 'Сохранить', 'usam') ?></button>	
						</div>
						<?php  } ?>	
					</div>	
				</div>			
			</div>		
			<teleport to="body">			
				<?php include( usam_get_filepath_admin('templates/template-parts/modal-panel/modal-panel-objects.php') ); ?>	
			</teleport>			
		</div>
		</interface-filters>
		<?php		
		usam_vue_module('list-table');
		add_action('admin_footer', array($this, 'display_footer'), 100);
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
	
	public function get_status_subscriber_options() 
	{		
		$results = [];
		foreach( usam_get_newsletter_statuses() as $key => $status )
			$results[] = ['id' => $key, 'name' => $status];		
		return $results;
	}	
	
	protected function get_filter_value( $key, $value = null ) 
	{		
		if ( isset(self::$selected_filters[$key]) )
			$value = self::$selected_filters[$key];
		else
		{ 
			require_once( USAM_FILE_PATH . '/admin/includes/filter_processing.class.php' );
			$f = new Filter_Processing();	
			$value = $f->get_filter_value( $key, $value );				
		}		
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
			$page_sorting = get_user_option( 'usam_page_sorting' );						
			$screen = get_current_screen();		
			$orderby = is_array($page_sorting) && isset($page_sorting[$screen->id]) ? $page_sorting[$screen->id] : key($sort_options);
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
			if ( $filter['type']=='checklists' || $filter['type']=='objects' || $filter['type']=='autocomplete' || $filter['type']=='select' || $filter['type']=='counterparty' )
				$filter['options'] = [];			
			$method = "get_{$key}_options";	
			if ( method_exists($this, $method) )
				$filter['options'] = $this->$method();				
			if ( $filter['type'] == 'string_meta' || $filter['type'] == 'string' )
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
			elseif ( $filter['type'] == 'objects' )
			{
				$objects = array_keys(usam_get_details_documents());
				$objects[] = 'company';
				$objects[] = 'contact';
				$filter['value'] = [];
				foreach( $objects as $object_type )
				{
					$selected = $this->get_filter_value( 'o_'.$object_type );
					if ( $selected )		
					{
						$object_ids = array_map('intval', (array)$selected);
						foreach( $object_ids as $object_id )
						{
							$object = new stdClass;
							$object->object_id = $object_id;
							$object->object_type = $object_type;
							$result = usam_get_object( $object );
							$filter['options'][] = ['id' => $object_id, 'type' => '_'.$object_type, 'name' => $result['name'].' - '.$result['title']];
						}
					}
				}
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
			elseif ( $filter['type'] == 'checkbox' )
			{				
				$filter['value'] = $this->get_filter_value( $key ) == 1;			
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