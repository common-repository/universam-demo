<template v-slot:title>
	<?php _e( 'Группа', 'usam'); ?><span v-if="data.groups.length">({{data.groups.length}})</span>
</template>			
<template v-slot:body>
	<group-management :id="data.id" :type="crm_type" @change="data.groups=$event" inline-template>
		<div class="checklist">
			<div class="checklist__search_block" v-if="groups.length > 9">
				<input type="text" class="checklist__search" placeholder="<?php _e("Поиск","usam"); ?>" v-model="search">					
			</div>	
			<div class="checklist__panel">							
				<div class="checklist__lists">
					<div v-for="(group, k) in groups" v-show="!search || group.name.toLowerCase().includes(search.toLowerCase())" draggable="true" @drop="drop($event, k)" @dragover="allowDrop($event, k)" @dragstart="drag($event, k)" @dragend="dragEnd($event, k)">
						<label class="selectit" v-show="group.editor==false" >
							<input @click="group.checked=group.checked?false:true" type="checkbox" name="groups[]" :value="group.id" v-model="group.checked">							
							<span class="checklist__list_name" v-on:dblclick="group_focus(k)">{{group.name}}</span>
							<a v-if="allowGroupСhanges" @click="group_delete($event, k)" class="delete_item"></a>
						</label>
						<div :ref="'checklist_editor'+group.id" contenteditable="true" v-show="group.editor" class="checklist__name_editor checklist_editor" v-html="group.name" v-on:keyup.enter="save($event, k)" @blur="save($event, k)"></div>
					</div>
				</div>
			</div>
			<div v-if="allowGroupСhanges" contenteditable="true" class="checklist__add checklist_editor" placeholder="<?php _e("Добавить группу","usam"); ?>" v-html="newGroup" v-on:keyup.enter="add"></div>
		</div>
	</group-management>
</template>