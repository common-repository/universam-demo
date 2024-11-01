<modal-panel ref="modalelements">
	<template v-slot:title><?php _e('Элементы', 'usam'); ?></template>
	<template v-slot:body>
		<div class ="grid_select elements">
			<a class="list" v-for="item in ['triangle', 'play', 'play2', 'element', 'snowflake', 'arrow2', 'chevron_left', 'close', 'circle', 'drag']" @click="addLayerElements(item)">
				<span class="svg_icon"><svg shape-rendering="geometricPrecision" xmlns="http://www.w3.org/2000/svg"><use :xlink:href="'<?php echo USAM_SVG_ICON_URL; ?>#'+item"></use></svg></span>
			</a>
		</div>
	</template>
</modal-panel>	