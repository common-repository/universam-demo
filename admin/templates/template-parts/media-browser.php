<div id="media-browser" class="media_viewer" :class="{'is-active':open}">	
	<div class="media_viewer__topbar">
		<div class="media_viewer__topbar_title" v-html="title"></div>
		<div class="media_viewer__topbar_buttons">
			<?php usam_system_svg_icon("fullscreen", ["@click" => "fullScreenChange", "v-if" => "!fullScreen"]); ?>
			<?php usam_system_svg_icon("fullscreen-exit", ["@click" => "fullScreenChange", "v-if" => "fullScreen"]); ?>
			<?php usam_system_svg_icon("close", ["class" => "media_viewer__topbar_buttons_close", "@click" => "open=!open"]); ?>	
		</div>		
	</div>
	<site-slider :items='images' :number='image_key' :amount="1" :classes="'media_viewer__image'">
		<template v-slot:body="sliderProps">
			<div class="media_viewer__nav media_viewer__nav_left" @click="sliderProps.prev" v-if="sliderProps.n!=0"><?php usam_system_svg_icon("chevron_left"); ?></div>
			<div class="media_viewer__nav media_viewer__nav_right" @click="sliderProps.next" v-if="sliderProps.n!=sliderProps.items.length-1"><?php usam_system_svg_icon("chevron_right"); ?></div>
			<div class='media_viewer__image_zoom' v-for="(image, i) in sliderProps.items" v-if="sliderProps.n==i" @click="sliderProps.changeZoom">
				<image-zoom :img-normal="image.full" :disabled="!sliderProps.zoom" @change="sliderProps.enable=!$event"></image-zoom>
			</div>	
		</template>
	</site-slider>	
	<site-slider :items='images' :number='image_key' :classes="'media_viewer__small_images'">
		<template v-slot:body="sliderProps">
			<div class='media_viewer__small_image' v-for="(image, i) in sliderProps.items" :class="{'active':sliderProps.n==i}">	
				<img :src='image.small_image' @click="image_key=i">	
			</div>	
		</template>
	</site-slider>
</div>