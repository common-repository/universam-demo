<div class="property_selectlist" :class ="property.field_type+'_property'" v-if="property.field_type=='select'">
	<select-list @change="property.value=$event.id" :lists="property.options" :selected="property.value"></select-list>
</div>
<div :class ="property.field_type+'_property'" v-else>
	<input type='text' v-model="property.value" v-mask="property.mask"/>			
</div>