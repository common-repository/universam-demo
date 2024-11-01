<select-list @change="property.value=$event.id" :lists="property.options" multiple='1' :selected="property.value" v-if="property.type=='checklists'"></select-list>
<select class="filter_select" v-model="property.value" v-if="property.type=='select'" :class="{'active':property.value}">
	<option v-for="list in property.options" v-bind:value="list.id" v-html="list.name"></option>
</select>
<div class="filter_checkbox" v-if="property.type=='checkbox'">
	<input type="checkbox" v-model="property.value" :class="{'active':property.value}" value="1"/>
</div>
<div class="filter_intervals" v-if="property.type=='numeric'">
	<input type="text" class="digital_interval" v-model="property.from" :class="[property.from?'active':'']" autocomplete="off"/> - 
	<input type="text" class="digital_interval" v-model="property.to" :class="[property.to?'active':'']" autocomplete="off"/>	
</div>
<div class="filter_intervals" v-if="property.type=='date'">
	<input type='text' class="date_interval" :class="{'active':property.from}" placeholder="<?php _e('дд.мм.гггг','usam'); ?>" v-model="property.from" v-mask="'##.##.####'" autocomplete="off"/> - 
	<input type='text' class="date_interval" :class="{'active':property.to}" placeholder="<?php _e('дд.мм.гггг','usam'); ?>" v-model="property.to" v-mask="'##.##.####'" autocomplete="off"/>
</div>
<div class="string_filter" v-if="property.type=='string'">
	<input type="text" v-model="property.value" autocomplete="off" :class="[property.value?'active':'']">
	<select v-model="property.checked" :class="[property.checked!=''?'active':'']">			
		<?php
		foreach (usam_get_conditions('meta') as $key => $value) 
		{				
			printf('<option value="%s">%s</option>', $key, $value);	
		}
		?>
	</select>
</div>
<div class="string_filter" v-if="property.type=='string_meta'">
	<div class="string_filter__value" v-if="property.checked!=='exists' && property.checked!=='not_exists'">
		<?php include( usam_get_filepath_admin('templates/template-parts/filter-meta-fields.php') ); ?>	
	</div> 
	<select v-model="property.checked" :class="[property.checked?'active':'']" v-if="property.field_type=='M' || property.field_type=='S' || property.field_type=='N' || property.field_type=='COLOR' || property.field_type=='BUTTONS' || property.field_type=='AUTOCOMPLETE' || property.field_type=='COLOR_SEVERAL' || property.field_type=='select' || property.field_type=='radio' || property.field_type=='checkbox'">		
		<?php
		foreach (['in' => __('Выбрать','usam'),'not in' => __('Исключить','usam'), 'exists' => __('Существует', 'usam'), 'not_exists' => __('Не существует', 'usam')] as $key => $value) 
		{				
			printf('<option value="%s">%s</option>', $key, $value);	
		}
		?>
	</select>	
	<select v-model="property.checked" :class="[property.checked?'active':'']" v-else>			
		<?php
			foreach (usam_get_conditions('string') as $key => $value) 
			{				
				printf('<option value="%s">%s</option>', $key, $value);	
			}			
			?>
	</select>	
</div>
<div class="autocomplete_filter checklist" v-if="property.type=='counterparty'">
	<div class="checklist__search_selected">										
		<div class="filter_counterparty usam_autocomplete">
			<select v-model="property.request" class='select_customer_type' @change="property.options=[]">
				<?php
				foreach (['companies' => __('Компания','usam'),'contacts' => __('Контакт','usam')] as $key => $value) 
				{				
					printf('<option value="%s">%s</option>', $key, $value);	
				}
				?>
			</select>
			<autocomplete :clearselected="1" @change="addOptions(k, $event)" :request="property.request"></autocomplete>
		</div>
		<div class="checklist__selected" v-show="property.options.length">
			<div class="checklist__selected_name" v-for="(list, i) in property.options"><span v-html="list.name"></span><a class='button_delete' @click="deleteOptions(property, i)"></a></div>
		</div>
	</div>									
</div>	
<div class="autocomplete_filter" v-if="property.type=='autocomplete'">
	<autocomplete :clearselected="1" @change="addOptions(k, $event)" :request="property.request"></autocomplete>
	<div class="checklist__selected" v-show="property.options.length">
		<div class="checklist__selected_name" v-for="(list, i) in property.options">
			<span v-html="list.name"></span>
			<a class='button_delete' @click="$delete(property.options,i)"></a>
		</div>
	</div>
</div>			
<div class="objects_filte" v-show="property.type=='objects'">
	<a class="open_sidebar" @click="sidebar('objects', k)"><?php _e('выбрать','usam'); ?></a>
	<div class="checklist__search_selected">
		<div class="checklist__selected" v-show="property.value">
			<div class="checklist__selected_name" v-for="(item, i) in property.options">
				<?php include( usam_get_filepath_admin('templates/template-parts/objects.php') ); ?>
				<a class='button_delete' @click="deleteOptions(property, i)"></a>
			</div>
		</div>
	</div>					
</div>					
<div class="period_filter" v-if="property.type=='period'">
	<select v-model="property.period" class="period_filter__select_period">
		<option value='current_month'><?php _e( 'Текущий месяц', 'usam'); ?></option>
		<option value='current_quarter'><?php _e( 'Текущий квартал', 'usam'); ?></option>
		<option value='current_week'><?php _e( 'Текущая неделя', 'usam'); ?></option>			
		<option value='last_7_day'><?php _e( 'Последние 7 дней', 'usam'); ?></option>	
		<option value='last_30_day'><?php _e( 'Последние 30 дней', 'usam'); ?></option>		
		<option value='last_60_day'><?php _e( 'Последние 60 дней', 'usam'); ?></option>	
		<option value='last_90_day'><?php _e( 'Последние 90 дней', 'usam'); ?></option>	
		<option value='last_365_day'><?php _e( 'Последние 365 дней', 'usam'); ?></option>
		<option value='month'><?php _e( 'Месяц', 'usam'); ?></option>			
		<option value='quarter'><?php _e( 'Квартал', 'usam'); ?></option>						
		<option value='year'><?php _e( 'Год', 'usam'); ?></option>			
		<option value=''><?php _e( 'Весь период', 'usam'); ?></option>						
	</select>	
	<select v-model="property.interval" v-show="property.period=='month'">
		<?php
		$month_name = [1 => __("Январь",'usam'), 2 => __("Февраль",'usam'), 3 => __("Март",'usam'), 4 => __("Апрель",'usam'), 5 => __("Май",'usam'), 6 => __("Июнь",'usam'), 7 => __("Июль",'usam'), 8 => __("Август",'usam'), 9 => __("Сентябрь",'usam'), 10 => __("Октябрь",'usam'), 11 => __("Ноябрь",'usam'), 12 => __("Декабрь",'usam')]; 
		foreach( $month_name as $i => $name )
		{					
			?><option value='<?php echo $i; ?>'><?php echo $name; ?></option><?php
		}
		?>	
	</select>	
	<select v-model="property.interval" v-show="property.period=='quarter'">
		<option value='1'>I</option>
		<option value='2'>II</option>
		<option value='3'>III</option>
		<option value='4'>IV</option>
	</select>		
	<select v-model="property.year" v-show="property.period=='year' || property.period=='quarter' || property.period=='month'">
		<?php
		for( $i = date("Y"); $i>=2014; $i--)
		{
			?><option value='<?php echo $i; ?>'><?php echo $i; ?></option><?php
		}
		?>	
	</select>	
</div>