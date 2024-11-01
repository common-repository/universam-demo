<div class ="object_property" v-if="property.field_type=='D'">			
	<input type='text' placeholder="<?php _e('дд.мм.гггг','usam'); ?>" v-model="property.value" v-mask="'##.##.####'" autocomplete="off" :class="[property.value?'active':'']"/>
</div>	
<select-list @change="property.value=$event.id" :lists="property.options" :selected="property.value" :search="1" v-else-if="property.field_type=='S' || property.field_type=='PRICES' || property.field_type=='N' || property.field_type=='COLOR' || property.field_type=='A' || property.field_type=='select' || property.field_type=='radio' || property.field_type=='checkbox'"></select-list>
<selector v-model="property.value" :items="property.options" v-else-if="property.field_type=='BUTTONS'"></selector>
<autocomplete :code="k" :selected="property.search" v-else-if="property.field_type=='AUTOCOMPLETE'" @change="property.value = $event.id; property.search=$event.name" :request="property.request" :query="property.request_parameters"></autocomplete>
<select-list @change="property.value=$event.id" :lists="property.options" :selected="property.value" :multiple="1" v-else-if="property.field_type=='COLOR_SEVERAL' || property.field_type=='M'"></select-list>
<input type="checkbox"  v-else-if="property.field_type=='C' || property.field_type=='one_checkbox'" v-model="property.value" :class="{'active':property.value}" value="1"/>
<input type="text" v-model="property.value" autocomplete="off" :class="[property.value?'active':'']" v-else>