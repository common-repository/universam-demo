<div class="global_layers" v-if="layers.length && toolTabs=='layers'">
	<div class="slider_global_layers_row">
		<div class="slider_global_layers_title" v-if="!selectedlayers.length"><?php _e('Временная шкала слоев', 'usam'); ?></div>
		<div class="slider_global_layers_title" v-else>
			<span class="selected_layers"><?php _e('Выбрано', 'usam'); ?> ({{selectedlayers.length}})</span>
			<button class="button" @click="groupLevel"><?php _e('В группу', 'usam'); ?></button>
			<span class="mark_layers_selection" @click="markLayersSelection"><?php _e('Отменить', 'usam'); ?></span>
		</div>				
	</div>
	<sort-block @change="sortLayers">
		<template v-slot:body="slotProps">
			<div class="layer_list">
				<div class="layer" v-for="(layer, i) in layers" v-if="!layer.group || layer.type=='group'" draggable="true" @drop="slotProps.drop($event, i)" @dragover="slotProps.allowDrop($event, i)" @dragstart="slotProps.drag($event, i)" @dragend="slotProps.dragEnd($event, i)">						
					<div class="layer_left">
						<div class="layer_row" :class="{'active':i===layerActive}">
							<input type="checkbox" v-model="layer.selected" v-if="layer.selected">
							<span class="svg_icon layer_type_icon" v-else><svg shape-rendering="geometricPrecision" xmlns="http://www.w3.org/2000/svg"><use :xlink:href="'<?php echo USAM_SVG_ICON_URL; ?>#'+layer.type"></use></svg></span>
							<div class="layer_name" v-html="layer.name" v-if="!layer.editName" @click.ctrl="layer.selected=!layer.selected" @click="layerActive=i"></div>
							<input type="text" v-model="layer.name" v-else class="width100" @click.enter="layer.editName=false">
							<div class="layer_icons">
								<?php usam_system_svg_icon("lock", ['@click' => 'layer.lock=!layer.lock', ':class' => '[layer.lock?`selected`:``]']); ?>
								<?php usam_system_svg_icon("off_visibility", ['@click' => 'layer.visibility=!layer.visibility', ':class' => '[!layer.visibility?`selected`:``]']); ?>
								<?php usam_system_svg_icon("delete", ['@click' => 'deleteLayer(i)']); ?>			
							</div>
						</div>
						<div class="slide_layer_content_group" v-if="layer.type=='group'">
							<div class="layer_row" v-for="(glayer, j) in layers" v-if="glayer.group==layer.id" :class="{'active':j===layerActive}">
								<input type="checkbox" v-model="glayer.selected" v-if="glayer.selected">
								<span class="svg_icon layer_type_icon" v-else><svg shape-rendering="geometricPrecision" xmlns="http://www.w3.org/2000/svg"><use :xlink:href="'<?php echo USAM_SVG_ICON_URL; ?>#'+glayer.type"></use></svg></span>
								<div class="layer_name" v-html="glayer.name" v-if="!glayer.editName" @click="layerActive=j; tab='editor'"></div>
								<input type="text" v-model="glayer.name" v-else class="width100">
								<div class="layer_icons">
									<?php usam_system_svg_icon("lock", ['@click' => 'glayer.lock=!glayer.lock', ':class' => '[glayer.lock?`selected`:``]']); ?>
									<?php usam_system_svg_icon("off_visibility", ['@click' => 'glayer.visibility=!glayer.visibility', ':class' => '[!glayer.visibility?`selected`:``]']); ?>
								</div>
							</div>							
						</div>
					</div>
					<div class="layer_time">
				
					</div>
				</div>			
			</div>	
		</template>
	</sort-block>	
</div>