<div ref="layers" class="layer_grid" :style="'width:'+data.settings.size[device].width+';'">
	<div class="layer_grid_top"></div>
	<div class="layer_grid_bootom"></div>
	<div class="layer_grid_left"></div>
	<div class="layer_grid_right"></div>
	<div :ref="'layer_'+i" class="slide_layer" v-for="(layer, i) in layers" v-if="layer.visibility && !layer.group" :class="{'active':i===layerActive, 'move_layer':moveLayer===i}" @click="selectLayer(i)" :style="layerStyles(layer, i)" @contextmenu="openMenuLayer($event, i)">
		<?php usam_system_svg_icon("rotation", ["@mousedown" => 'mousedown($event, i, `rotation`)', "@dragstart" => "dragstart", "v-if" => "layer.type==`element` || layer.type==`image`"]); ?>
		<div class="slide_layer_container" @mousedown="mousedown($event, i, 'move')" @dragstart="dragstart">
			
			<div class="slide_layer_content_group" :ref="'layer_content_'+i" v-if="layer.type=='group'" :style="layerStylesContent(layer)">
				<div class="slide_layer_content_group_content" v-for="(glayer, j) in layers" v-if="glayer.visibility && glayer.group==layer.id">
					<?php usam_system_svg_icon("rotation", ["@mousedown" => 'mousedown($event, i, `rotation`)', "@dragstart" => "dragstart", "v-if" => "glayer.type==`element` || glayer.type==`image`"]); ?>
					<div class="slide_layer_content_container" v-if="glayer.type=='image'" :style="layerStylesContent(glayer)">
						<img :src="glayer.object_url" v-if="glayer.object_url">						
					</div>
					<div class="slide_layer_content svg_icon" :ref="'layer_content_'+i" v-else-if="glayer.type=='element'" :style="layerStylesContent(glayer)">
						<svg shape-rendering="geometricPrecision" xmlns="http://www.w3.org/2000/svg"><use :xlink:href="'<?php echo USAM_SVG_ICON_URL; ?>#'+glayer.element"></use></svg>						
					</div>
					<div class="slide_layer_content_container" v-else :style="layerStylesContent(glayer)" v-html="glayer.content.replace(/\n/g, '<br>')"></div>
				</div>
			</div>
			<div class="slide_layer_content" :ref="'layer_content_'+i" v-else-if="layer.type=='image' || layer.type=='product-thumbnail' || layer.type=='product-day-thumbnail'" :style="layerStylesContent(layer)">
				<img :src="layer.object_url" v-if="layer.object_url">				
			</div>
			<div class="slide_layer_content svg_icon" :ref="'layer_content_'+i" v-else-if="layer.type=='element'" :style="layerStylesContent(layer)">
				<svg shape-rendering="geometricPrecision" xmlns="http://www.w3.org/2000/svg"><use :xlink:href="'<?php echo USAM_SVG_ICON_URL; ?>#'+layer.element"></use></svg>				
			</div>
			<div class="slide_layer_content" :ref="'layer_content_'+i" v-else v-html="layer.content.replace(/\n/g, '<br>')" :style="layerStylesContent(layer)"></div>
		</div>
		<div class="slide_layer_handle_top" @mousedown="handleDown($event, 'top')" mousemove="handleMove($event)" @dragstart="dragstart"></div>
		<div class="slide_layer_handle_bottom" @mousedown="handleDown($event, 'bottom')" mousemove="handleMove($event)" @dragstart="dragstart"></div>		
		<div class="slide_layer_handle_left" @mousedown="handleDown($event, 'left')" mousemove="handleMove($event)" @dragstart="dragstart"></div>
		<div class="slide_layer_handle_right" @mousedown="handleDown($event, 'right')"@mousemove="handleMove($event)" @dragstart="dragstart"></div>
	</div>
	<div ref="product" class="point_layer" v-for="(product, i) in products" :style="'inset:'+(product[device]!==undefined?product[device].inset:'50px auto auto 50px')+';'">
		<div class="slide_product_point" @mousedown="pointMousedown(i, $event)" @mousemove="pointMousemove(i, $event)" @dragstart="dragstart"></div>
		<div class="point_layer__prompt">
			<div class="point_layer__prompt_title" v-html="product.post_title"></div>
			<div class="point_layer__prompt_sku" v-html="product.sku"></div>
		</div>					
	</div>
</div>
